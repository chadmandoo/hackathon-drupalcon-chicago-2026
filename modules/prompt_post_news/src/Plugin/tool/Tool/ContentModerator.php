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
 * View and manage content moderation states.
 */
#[Tool(
  id: 'prompt_post_news:content_moderator',
  label: new TranslatableMarkup('Content Moderator'),
  description: new TranslatableMarkup('View content by moderation state. Lists content that is in draft, in review, published, or archived. Useful for editorial workflow management. Shows the current state of each piece of content with author and dates.'),
  operation: ToolOperation::Read,
  input_definitions: [
    'state' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Moderation state'),
      description: new TranslatableMarkup('Filter by state: "draft", "review", "published", "archived", or "all". Defaults to "all".'),
      required: FALSE,
    ),
    'content_type' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Content type'),
      description: new TranslatableMarkup('Filter by content type: "article", "event", or leave empty for all.'),
      required: FALSE,
    ),
  ],
)]
final class ContentModerator extends ToolBase {

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
    $state = $values['state'] ?? 'all';
    $content_type = $values['content_type'] ?? NULL;

    $query = $this->database->select('content_moderation_state_field_data', 'cms');
    $query->condition('cms.content_entity_type_id', 'node');
    $query->join('node_field_data', 'n', 'cms.content_entity_id = n.nid');
    $query->join('users_field_data', 'u', 'n.uid = u.uid');
    $query->fields('n', ['nid', 'title', 'type', 'created', 'changed']);
    $query->addField('u', 'name', 'author');
    $query->addField('cms', 'moderation_state', 'state');

    if ($state !== 'all') {
      $query->condition('cms.moderation_state', $state);
    }

    if ($content_type) {
      $query->condition('n.type', $content_type);
    }

    $query->orderBy('n.changed', 'DESC');
    $results = $query->execute()->fetchAll();

    $items = [];
    $state_counts = [];
    foreach ($results as $row) {
      $items[] = [
        'nid' => (int) $row->nid,
        'title' => $row->title,
        'type' => $row->type,
        'author' => $row->author,
        'state' => $row->state,
        'created' => date('Y-m-d H:i', (int) $row->created),
        'last_modified' => date('Y-m-d H:i', (int) $row->changed),
      ];
      $state_counts[$row->state] = ($state_counts[$row->state] ?? 0) + 1;
    }

    // Available transitions for reference.
    $workflow_info = [
      'states' => ['draft', 'review', 'published', 'archived'],
      'transitions' => [
        'draft -> review' => 'Submit for Review',
        'draft -> published' => 'Publish',
        'review -> published' => 'Publish',
        'review -> draft' => 'Send Back to Draft',
        'published -> archived' => 'Archive',
        'published -> draft' => 'Create New Draft',
        'archived -> draft' => 'Create New Draft',
      ],
    ];

    $filter_label = $state === 'all' ? 'all states' : $state;
    $summary = $this->t('@count content items in @filter. Breakdown: @counts', [
      '@count' => count($items),
      '@filter' => $filter_label,
      '@counts' => implode(', ', array_map(fn($s, $c) => "$s: $c", array_keys($state_counts), $state_counts)),
    ]);

    return ExecutableResult::success($summary, [
      'filter_state' => $state,
      'total_count' => count($items),
      'state_counts' => $state_counts,
      'content' => $items,
      'workflow' => $workflow_info,
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
