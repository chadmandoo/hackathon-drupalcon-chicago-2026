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
 * Tool that reports which users are currently online.
 */
#[Tool(
  id: 'prompt_post_news:whos_online',
  label: new TranslatableMarkup("Who's Online"),
  description: new TranslatableMarkup('Reports which users are currently online or have been recently active on the Drupal site. Returns usernames, roles, and last access times.'),
  operation: ToolOperation::Read,
  input_definitions: [
    'minutes' => new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Minutes'),
      description: new TranslatableMarkup('How many minutes back to look for active users. Defaults to 15.'),
      required: FALSE,
    ),
  ],
)]
final class WhosOnline extends ToolBase {

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
    $minutes = $values['minutes'] ?? 15;
    $threshold = \Drupal::time()->getRequestTime() - ($minutes * 60);

    // Query for authenticated users active within the threshold.
    $query = $this->database->select('users_field_data', 'u');
    $query->fields('u', ['uid', 'name', 'access']);
    $query->condition('u.access', $threshold, '>=');
    $query->condition('u.uid', 0, '>');
    $query->orderBy('u.access', 'DESC');
    $results = $query->execute()->fetchAll();

    $online_users = [];
    foreach ($results as $row) {
      // Load user to get roles.
      $user = \Drupal::entityTypeManager()->getStorage('user')->load($row->uid);
      $roles = $user ? $user->getRoles(TRUE) : [];

      $online_users[] = [
        'uid' => (int) $row->uid,
        'name' => $row->name,
        'roles' => $roles,
        'last_access' => date('Y-m-d H:i:s', (int) $row->access),
        'seconds_ago' => \Drupal::time()->getRequestTime() - (int) $row->access,
      ];
    }

    // Also count anonymous sessions if the session table exists.
    $anonymous_count = 0;
    try {
      $anonymous_count = (int) $this->database->select('sessions', 's')
        ->condition('s.uid', 0)
        ->condition('s.timestamp', $threshold, '>=')
        ->countQuery()
        ->execute()
        ->fetchField();
    }
    catch (\Exception $e) {
      // Sessions table may not exist.
    }

    $total_authenticated = count($online_users);
    $message = $this->t('@auth authenticated user(s) and @anon anonymous session(s) active in the last @min minutes.', [
      '@auth' => $total_authenticated,
      '@anon' => $anonymous_count,
      '@min' => $minutes,
    ]);

    return ExecutableResult::success(
      $message,
      [
        'authenticated_users' => $online_users,
        'authenticated_count' => $total_authenticated,
        'anonymous_count' => $anonymous_count,
        'threshold_minutes' => $minutes,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    $access_result = AccessResult::allowedIfHasPermission($account, 'access content');
    return $return_as_object ? $access_result : $access_result->isAllowed();
  }

}
