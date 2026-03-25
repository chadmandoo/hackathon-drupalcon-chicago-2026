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
 * Full-text content search across articles and events.
 */
#[Tool(
  id: 'prompt_post_news:content_search',
  label: new TranslatableMarkup('Content Search'),
  description: new TranslatableMarkup('Search content by keyword across titles and body text. Filter by content type and category. Returns matching articles and events with excerpts.'),
  operation: ToolOperation::Read,
  input_definitions: [
    'keyword' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Search keyword'),
      description: new TranslatableMarkup('The keyword or phrase to search for in titles and body text.'),
      required: TRUE,
    ),
    'content_type' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Content type'),
      description: new TranslatableMarkup('Filter by content type: "article", "event", or leave empty for all.'),
      required: FALSE,
    ),
    'category' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Category'),
      description: new TranslatableMarkup('Filter by category name (e.g., "Local News", "Sports"). Articles only.'),
      required: FALSE,
    ),
    'limit' => new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Limit'),
      description: new TranslatableMarkup('Maximum number of results to return. Defaults to 10.'),
      required: FALSE,
    ),
  ],
)]
final class ContentSearch extends ToolBase {

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
    $keyword = $values['keyword'];
    $content_type = $values['content_type'] ?? NULL;
    $category = $values['category'] ?? NULL;
    $limit = $values['limit'] ?? 10;
    $limit = min($limit, 50);

    $query = $this->database->select('node_field_data', 'n');
    $query->fields('n', ['nid', 'title', 'type', 'status', 'created', 'changed']);
    $query->join('users_field_data', 'u', 'n.uid = u.uid');
    $query->addField('u', 'name', 'author');

    // Join body field for text search.
    $query->leftJoin('node__body', 'b', 'n.nid = b.entity_id');

    // Search in title and body.
    $or = $query->orConditionGroup();
    $or->condition('n.title', '%' . $this->database->escapeLike($keyword) . '%', 'LIKE');
    $or->condition('b.body_value', '%' . $this->database->escapeLike($keyword) . '%', 'LIKE');
    $query->condition($or);

    // Filter by content type.
    if ($content_type) {
      $query->condition('n.type', $content_type);
    }

    // Filter by category.
    if ($category) {
      $query->join('node__field_category', 'fc', 'n.nid = fc.entity_id');
      $query->join('taxonomy_term_field_data', 't', 'fc.field_category_target_id = t.tid');
      $query->condition('t.name', $category);
    }

    $query->orderBy('n.changed', 'DESC');
    $query->range(0, $limit);

    $results = $query->execute()->fetchAll();

    $items = [];
    foreach ($results as $row) {
      $item = [
        'nid' => (int) $row->nid,
        'title' => $row->title,
        'type' => $row->type,
        'author' => $row->author,
        'status' => (int) $row->status ? 'published' : 'unpublished',
        'created' => date('Y-m-d H:i', (int) $row->created),
        'last_modified' => date('Y-m-d H:i', (int) $row->changed),
      ];

      // Get teaser if article.
      if ($row->type === 'article') {
        $teaser = $this->database->select('node__field_teaser', 'ft')
          ->fields('ft', ['field_teaser_value'])
          ->condition('ft.entity_id', $row->nid)
          ->execute()->fetchField();
        if ($teaser) {
          $item['teaser'] = $teaser;
        }
      }

      $items[] = $item;
    }

    $count = count($items);
    $summary = $this->t('Found @count result(s) for "@keyword".', [
      '@count' => $count,
      '@keyword' => $keyword,
    ]);

    return ExecutableResult::success($summary, [
      'keyword' => $keyword,
      'result_count' => $count,
      'results' => $items,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    $access_result = AccessResult::allowedIfHasPermission($account, 'access content');
    return $return_as_object ? $access_result : $access_result->isAllowed();
  }

}
