<?php

declare(strict_types=1);

namespace Drupal\prompt_post_news\Plugin\tool\Tool;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tool\Attribute\Tool;
use Drupal\tool\ExecutableResult;
use Drupal\tool\Tool\ToolBase;
use Drupal\tool\Tool\ToolOperation;
use Drupal\tool\TypedData\InputDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Site analytics and content statistics.
 */
#[Tool(
  id: 'prompt_post_news:site_analytics',
  label: new TranslatableMarkup('Site Analytics'),
  description: new TranslatableMarkup('Get content analytics: publishing trends over time, articles per category, most prolific authors, content age distribution, and upcoming events. Specify a time period in days.'),
  operation: ToolOperation::Read,
  input_definitions: [
    'days' => new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Days'),
      description: new TranslatableMarkup('Number of days to look back for analytics. Defaults to 30.'),
      required: FALSE,
    ),
  ],
)]
final class SiteAnalytics extends ToolBase {

  protected Connection $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->database = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    $days = $values['days'] ?? 30;
    $threshold = \Drupal::time()->getRequestTime() - ($days * 86400);
    $data = [];

    // Publishing trend: content created per day in the period.
    $query = $this->database->select('node_field_data', 'n');
    $query->addExpression("TO_CHAR(TO_TIMESTAMP(n.created), 'YYYY-MM-DD')", 'publish_date');
    $query->addExpression('COUNT(*)', 'count');
    $query->condition('n.created', $threshold, '>=');
    $query->groupBy('publish_date');
    $query->orderBy('publish_date', 'ASC');
    $results = $query->execute()->fetchAll();

    $publishing_trend = [];
    foreach ($results as $row) {
      $publishing_trend[$row->publish_date] = (int) $row->count;
    }
    $data['publishing_trend'] = $publishing_trend;

    // Content by type.
    $query = $this->database->select('node_field_data', 'n');
    $query->addField('n', 'type');
    $query->addExpression('COUNT(*)', 'count');
    $query->groupBy('n.type');
    $results = $query->execute()->fetchAll();

    $content_by_type = [];
    foreach ($results as $row) {
      $content_by_type[$row->type] = (int) $row->count;
    }
    $data['content_by_type'] = $content_by_type;

    // Category breakdown for articles.
    $query = $this->database->select('node__field_category', 'fc');
    $query->join('taxonomy_term_field_data', 't', 'fc.field_category_target_id = t.tid');
    $query->join('node_field_data', 'n', 'fc.entity_id = n.nid');
    $query->addField('t', 'name', 'category');
    $query->addExpression('COUNT(*)', 'count');
    $query->addExpression('MAX(n.created)', 'latest');
    $query->groupBy('t.name');
    $query->orderBy('count', 'DESC');
    $results = $query->execute()->fetchAll();

    $category_stats = [];
    foreach ($results as $row) {
      $category_stats[] = [
        'category' => $row->category,
        'article_count' => (int) $row->count,
        'latest_article' => date('Y-m-d', (int) $row->latest),
      ];
    }
    $data['category_stats'] = $category_stats;

    // Top authors.
    $query = $this->database->select('node_field_data', 'n');
    $query->join('users_field_data', 'u', 'n.uid = u.uid');
    $query->addField('u', 'name', 'author');
    $query->addExpression('COUNT(*)', 'total_content');
    $query->addExpression('SUM(CASE WHEN n.created >= ' . $threshold . ' THEN 1 ELSE 0 END)', 'recent_content');
    $query->addExpression('MAX(n.created)', 'last_published');
    $query->groupBy('u.name');
    $query->orderBy('total_content', 'DESC');
    $results = $query->execute()->fetchAll();

    $author_stats = [];
    foreach ($results as $row) {
      $author_stats[] = [
        'author' => $row->author,
        'total_content' => (int) $row->total_content,
        'content_in_period' => (int) $row->recent_content,
        'last_published' => date('Y-m-d H:i', (int) $row->last_published),
      ];
    }
    $data['author_stats'] = $author_stats;

    // Upcoming events.
    $now = date('Y-m-d\TH:i:s', \Drupal::time()->getRequestTime());
    $query = $this->database->select('node__field_event_date', 'ed');
    $query->join('node_field_data', 'n', 'ed.entity_id = n.nid');
    $query->fields('n', ['nid', 'title']);
    $query->addField('ed', 'field_event_date_value', 'event_date');
    $query->condition('ed.field_event_date_value', $now, '>=');
    $query->condition('n.status', 1);
    $query->orderBy('ed.field_event_date_value', 'ASC');
    $query->range(0, 10);
    $results = $query->execute()->fetchAll();

    $upcoming_events = [];
    foreach ($results as $row) {
      $upcoming_events[] = [
        'nid' => (int) $row->nid,
        'title' => $row->title,
        'event_date' => $row->event_date,
      ];
    }
    $data['upcoming_events'] = $upcoming_events;

    $total = array_sum($content_by_type);
    $summary = $this->t('@total total content items across @types content types. @events upcoming events.', [
      '@total' => $total,
      '@types' => count($content_by_type),
      '@events' => count($upcoming_events),
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
