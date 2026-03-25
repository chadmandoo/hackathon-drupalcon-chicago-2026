<?php

declare(strict_types=1);

namespace Drupal\prompt_post_news\Plugin\tool\Tool;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tool\Attribute\Tool;
use Drupal\tool\ExecutableResult;
use Drupal\tool\Tool\ToolBase;
use Drupal\tool\Tool\ToolOperation;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Editorial dashboard providing content stats and overview.
 */
#[Tool(
  id: 'prompt_post_news:editorial_dashboard',
  label: new TranslatableMarkup('Editorial Dashboard'),
  description: new TranslatableMarkup('Get an editorial overview of the site: content counts by type and status, articles by category, recent content, pending reviews, and author activity.'),
  operation: ToolOperation::Read,
)]
final class EditorialDashboard extends ToolBase {

  protected Connection $database;
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->database = $container->get('database');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    $data = [];

    // Content counts by type and moderation state.
    $query = $this->database->select('content_moderation_state_field_data', 'cms');
    $query->fields('cms', ['content_entity_type_id', 'moderation_state']);
    $query->addExpression('COUNT(*)', 'count');
    $query->groupBy('cms.content_entity_type_id');
    $query->groupBy('cms.moderation_state');
    $results = $query->execute()->fetchAll();

    $content_by_state = [];
    foreach ($results as $row) {
      $content_by_state[$row->content_entity_type_id][$row->moderation_state] = (int) $row->count;
    }
    $data['content_by_state'] = $content_by_state;

    // Articles by category.
    $query = $this->database->select('node__field_category', 'fc');
    $query->join('taxonomy_term_field_data', 't', 'fc.field_category_target_id = t.tid');
    $query->join('node_field_data', 'n', 'fc.entity_id = n.nid');
    $query->fields('t', ['name']);
    $query->addExpression('COUNT(*)', 'count');
    $query->condition('n.status', 1);
    $query->groupBy('t.name');
    $query->orderBy('count', 'DESC');
    $results = $query->execute()->fetchAll();

    $articles_by_category = [];
    foreach ($results as $row) {
      $articles_by_category[$row->name] = (int) $row->count;
    }
    $data['articles_by_category'] = $articles_by_category;

    // Recent content (last 10).
    $query = $this->database->select('node_field_data', 'n');
    $query->fields('n', ['nid', 'title', 'type', 'created', 'changed']);
    $query->join('users_field_data', 'u', 'n.uid = u.uid');
    $query->addField('u', 'name', 'author');
    $query->orderBy('n.changed', 'DESC');
    $query->range(0, 10);
    $results = $query->execute()->fetchAll();

    $recent_content = [];
    foreach ($results as $row) {
      $recent_content[] = [
        'nid' => (int) $row->nid,
        'title' => $row->title,
        'type' => $row->type,
        'author' => $row->author,
        'created' => date('Y-m-d H:i', (int) $row->created),
        'last_modified' => date('Y-m-d H:i', (int) $row->changed),
      ];
    }
    $data['recent_content'] = $recent_content;

    // Pending reviews.
    $query = $this->database->select('content_moderation_state_field_data', 'cms');
    $query->condition('cms.moderation_state', 'review');
    $query->condition('cms.content_entity_type_id', 'node');
    $query->join('node_field_data', 'n', 'cms.content_entity_id = n.nid');
    $query->fields('n', ['nid', 'title', 'type']);
    $query->join('users_field_data', 'u', 'n.uid = u.uid');
    $query->addField('u', 'name', 'author');
    $results = $query->execute()->fetchAll();

    $pending_reviews = [];
    foreach ($results as $row) {
      $pending_reviews[] = [
        'nid' => (int) $row->nid,
        'title' => $row->title,
        'type' => $row->type,
        'author' => $row->author,
      ];
    }
    $data['pending_reviews'] = $pending_reviews;

    // Author activity.
    $query = $this->database->select('node_field_data', 'n');
    $query->join('users_field_data', 'u', 'n.uid = u.uid');
    $query->addField('u', 'name', 'author');
    $query->addExpression('COUNT(*)', 'article_count');
    $query->condition('n.type', 'article');
    $query->groupBy('u.name');
    $query->orderBy('article_count', 'DESC');
    $results = $query->execute()->fetchAll();

    $author_stats = [];
    foreach ($results as $row) {
      $author_stats[$row->author] = (int) $row->article_count;
    }
    $data['author_stats'] = $author_stats;

    // Total counts.
    $total_published = $this->database->select('node_field_data', 'n')
      ->condition('n.status', 1)
      ->countQuery()->execute()->fetchField();
    $total_unpublished = $this->database->select('node_field_data', 'n')
      ->condition('n.status', 0)
      ->countQuery()->execute()->fetchField();

    $data['totals'] = [
      'published' => (int) $total_published,
      'unpublished' => (int) $total_unpublished,
      'total' => (int) $total_published + (int) $total_unpublished,
    ];

    $summary = $this->t('@total content items (@pub published, @unpub unpublished). @reviews pending review.', [
      '@total' => $data['totals']['total'],
      '@pub' => $data['totals']['published'],
      '@unpub' => $data['totals']['unpublished'],
      '@reviews' => count($pending_reviews),
    ]);

    return ExecutableResult::success($summary, $data);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    $access_result = AccessResult::allowedIfHasPermission($account, 'access content');
    return $return_as_object ? $access_result : $access_result->isAllowed();
  }

}
