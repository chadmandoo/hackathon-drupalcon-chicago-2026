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
 * Activity feed showing recent site events.
 */
#[Tool(
  id: 'prompt_post_news:recent_activity',
  label: new TranslatableMarkup('Recent Activity'),
  description: new TranslatableMarkup('Get a timeline of recent site activity: content changes, user logins, moderation state changes, and system events. Returns the most recent events from the Drupal watchdog log.'),
  operation: ToolOperation::Read,
  input_definitions: [
    'limit' => new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Limit'),
      description: new TranslatableMarkup('Maximum number of events to return. Defaults to 20.'),
      required: FALSE,
    ),
    'type' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Event type'),
      description: new TranslatableMarkup('Filter by event type: "content", "user", "system", or "all". Defaults to "all".'),
      required: FALSE,
    ),
  ],
)]
final class RecentActivity extends ToolBase {

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
    $limit = min($values['limit'] ?? 20, 100);
    $type_filter = $values['type'] ?? 'all';

    $activities = [];

    // Content activity: recent node revisions.
    if ($type_filter === 'all' || $type_filter === 'content') {
      $query = $this->database->select('node_revision', 'nr');
      $query->join('node_field_data', 'n', 'nr.nid = n.nid');
      $query->join('users_field_data', 'u', 'nr.revision_uid = u.uid');
      $query->fields('n', ['nid', 'title', 'type']);
      $query->addField('u', 'name', 'user');
      $query->addField('nr', 'revision_timestamp', 'timestamp');
      $query->addField('nr', 'revision_log', 'message');
      $query->orderBy('nr.revision_timestamp', 'DESC');
      $query->range(0, $limit);
      $results = $query->execute()->fetchAll();

      foreach ($results as $row) {
        $activities[] = [
          'type' => 'content',
          'timestamp' => date('Y-m-d H:i:s', (int) $row->timestamp),
          'timestamp_raw' => (int) $row->timestamp,
          'user' => $row->user,
          'summary' => $row->message ?: "Updated \"{$row->title}\"",
          'details' => [
            'nid' => (int) $row->nid,
            'title' => $row->title,
            'content_type' => $row->type,
          ],
        ];
      }
    }

    // User activity from watchdog.
    if ($type_filter === 'all' || $type_filter === 'user') {
      $query = $this->database->select('watchdog', 'w');
      $query->fields('w', ['wid', 'uid', 'type', 'message', 'variables', 'timestamp', 'severity']);
      $query->condition('w.type', 'user');
      $query->orderBy('w.timestamp', 'DESC');
      $query->range(0, $limit);
      $results = $query->execute()->fetchAll();

      foreach ($results as $row) {
        // Format the watchdog message.
        $message = $row->message;
        $variables = @unserialize($row->variables);
        if (is_array($variables)) {
          $message = strtr($message, $variables);
        }

        $activities[] = [
          'type' => 'user',
          'timestamp' => date('Y-m-d H:i:s', (int) $row->timestamp),
          'timestamp_raw' => (int) $row->timestamp,
          'user' => 'uid:' . $row->uid,
          'summary' => strip_tags($message),
          'details' => [
            'severity' => $this->mapSeverity((int) $row->severity),
          ],
        ];
      }
    }

    // System activity from watchdog.
    if ($type_filter === 'all' || $type_filter === 'system') {
      $query = $this->database->select('watchdog', 'w');
      $query->fields('w', ['wid', 'uid', 'type', 'message', 'variables', 'timestamp', 'severity']);
      $query->condition('w.type', ['system', 'cron', 'php', 'mcp_server', 'simple_oauth', 'prompt_post_news'], 'IN');
      $query->condition('w.severity', 4, '<=');
      $query->orderBy('w.timestamp', 'DESC');
      $query->range(0, $limit);
      $results = $query->execute()->fetchAll();

      foreach ($results as $row) {
        $message = $row->message;
        $variables = @unserialize($row->variables);
        if (is_array($variables)) {
          $message = strtr($message, $variables);
        }

        $activities[] = [
          'type' => 'system',
          'timestamp' => date('Y-m-d H:i:s', (int) $row->timestamp),
          'timestamp_raw' => (int) $row->timestamp,
          'user' => 'uid:' . $row->uid,
          'summary' => strip_tags($message),
          'details' => [
            'log_type' => $row->type,
            'severity' => $this->mapSeverity((int) $row->severity),
          ],
        ];
      }
    }

    // Sort all activities by timestamp descending.
    usort($activities, fn($a, $b) => $b['timestamp_raw'] - $a['timestamp_raw']);
    $activities = array_slice($activities, 0, $limit);

    // Remove raw timestamps from output.
    foreach ($activities as &$activity) {
      unset($activity['timestamp_raw']);
    }

    $summary = $this->t('@count recent activity events.', ['@count' => count($activities)]);

    return ExecutableResult::success($summary, [
      'filter' => $type_filter,
      'count' => count($activities),
      'activities' => $activities,
    ]);
  }

  /**
   * Map syslog severity to human-readable label.
   */
  protected function mapSeverity(int $severity): string {
    $map = [
      0 => 'emergency',
      1 => 'alert',
      2 => 'critical',
      3 => 'error',
      4 => 'warning',
      5 => 'notice',
      6 => 'info',
      7 => 'debug',
    ];
    return $map[$severity] ?? 'unknown';
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    $access_result = AccessResult::allowedIfHasPermission($account, 'access site reports');
    return $return_as_object ? $access_result : $access_result->isAllowed();
  }

}
