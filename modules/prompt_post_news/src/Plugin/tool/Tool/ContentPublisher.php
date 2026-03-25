<?php

declare(strict_types=1);

namespace Drupal\prompt_post_news\Plugin\tool\Tool;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tool\Attribute\Tool;
use Drupal\tool\ExecutableResult;
use Drupal\tool\Tool\ToolBase;
use Drupal\tool\Tool\ToolOperation;
use Drupal\tool\TypedData\InputDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Publish or unpublish content with moderation state transitions.
 *
 * Enforces Drupal's editorial workflow permissions:
 * - Writers can only create drafts and submit for review.
 * - Reviewers can publish from review or send back to draft.
 * - Admins can do all transitions including archive/restore.
 */
#[Tool(
  id: 'prompt_post_news:content_publisher',
  label: new TranslatableMarkup('Content Publisher'),
  description: new TranslatableMarkup('Change the moderation state of content. Writers can submit for review. Reviewers can publish or send back. Admins can archive and restore. Actions: "submit_for_review", "publish", "send_back", "archive", "restore_to_draft". Workflow: draft -> review -> published -> archived.'),
  operation: ToolOperation::Write,
  input_definitions: [
    'nid' => new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Node ID'),
      description: new TranslatableMarkup('The node ID of the content to modify.'),
      required: TRUE,
    ),
    'action' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Action'),
      description: new TranslatableMarkup('The moderation action: "submit_for_review", "publish", "send_back", "archive", "restore_to_draft".'),
      required: TRUE,
    ),
    'revision_log' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Revision log message'),
      description: new TranslatableMarkup('Optional message explaining the state change.'),
      required: FALSE,
    ),
  ],
)]
final class ContentPublisher extends ToolBase {

  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Map of actions to their workflow transition IDs and target states.
   */
  private const ACTION_MAP = [
    'submit_for_review' => [
      'transition' => 'submit_for_review',
      'target_state' => 'review',
      'permission' => 'use editorial transition submit_for_review',
      'valid_from' => ['draft'],
    ],
    'publish' => [
      'transition' => 'publish',
      'target_state' => 'published',
      'permission' => 'use editorial transition publish',
      'valid_from' => ['draft', 'review'],
    ],
    'send_back' => [
      'transition' => 'send_back',
      'target_state' => 'draft',
      'permission' => 'use editorial transition send_back',
      'valid_from' => ['review'],
    ],
    'archive' => [
      'transition' => 'archive',
      'target_state' => 'archived',
      'permission' => 'use editorial transition archive',
      'valid_from' => ['published'],
    ],
    'restore_to_draft' => [
      'transition' => 'archived_draft',
      'target_state' => 'draft',
      'permission' => 'use editorial transition archived_draft',
      'valid_from' => ['archived'],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    $nid = $values['nid'];
    $action = $values['action'];
    $revision_log = $values['revision_log'] ?? NULL;

    // Validate action.
    if (!isset(self::ACTION_MAP[$action])) {
      $valid = implode(', ', array_keys(self::ACTION_MAP));
      return ExecutableResult::failure($this->t('Invalid action "@action". Valid actions: @valid.', [
        '@action' => $action,
        '@valid' => $valid,
      ]));
    }

    $action_config = self::ACTION_MAP[$action];

    // Load content.
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    if (!$node) {
      return ExecutableResult::failure($this->t('Node @nid not found.', ['@nid' => $nid]));
    }

    $old_state = $node->get('moderation_state')->value ?? 'unknown';

    // Check if the transition is valid from the current state.
    if (!in_array($old_state, $action_config['valid_from'])) {
      return ExecutableResult::failure($this->t('Cannot "@action" content that is currently in "@state" state. This action is only valid from: @valid_from.', [
        '@action' => $action,
        '@state' => $old_state,
        '@valid_from' => implode(', ', $action_config['valid_from']),
      ]));
    }

    // Check if the current user has permission for this transition.
    $account = \Drupal::currentUser();
    if (!$account->hasPermission($action_config['permission'])) {
      return ExecutableResult::failure($this->t('Access denied. You do not have permission to "@action". Required permission: @perm. Your roles: @roles.', [
        '@action' => $action,
        '@perm' => $action_config['permission'],
        '@roles' => implode(', ', $account->getRoles()),
      ]));
    }

    // Perform the transition.
    $new_state = $action_config['target_state'];
    $node->setNewRevision(TRUE);
    $node->set('moderation_state', $new_state);

    if ($revision_log) {
      $node->setRevisionLogMessage($revision_log);
    }
    else {
      $node->setRevisionLogMessage("$action: $old_state -> $new_state (via MCP by {$account->getAccountName()})");
    }

    $node->setRevisionCreationTime(\Drupal::time()->getRequestTime());
    $node->save();

    $summary = $this->t('Content "@title" (nid: @nid) transitioned from @old to @new via "@action".', [
      '@title' => $node->getTitle(),
      '@nid' => $nid,
      '@old' => $old_state,
      '@new' => $new_state,
      '@action' => $action,
    ]);

    return ExecutableResult::success($summary, [
      'nid' => $nid,
      'title' => $node->getTitle(),
      'type' => $node->bundle(),
      'previous_state' => $old_state,
      'new_state' => $new_state,
      'action' => $action,
      'performed_by' => $account->getAccountName(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    // Base access: must be able to access content.
    // Transition-specific permission checks happen in doExecute()
    // so we can return actionable error messages.
    $access_result = AccessResult::allowedIfHasPermission($account, 'access content');
    return $return_as_object ? $access_result : $access_result->isAllowed();
  }

}
