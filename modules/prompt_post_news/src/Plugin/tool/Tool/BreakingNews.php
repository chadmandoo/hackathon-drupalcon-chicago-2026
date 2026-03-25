<?php

declare(strict_types=1);

namespace Drupal\prompt_post_news\Plugin\tool\Tool;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tool\Attribute\Tool;
use Drupal\tool\ExecutableResult;
use Drupal\tool\Tool\ToolBase;
use Drupal\tool\Tool\ToolOperation;
use Drupal\tool\TypedData\InputDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Flag an article as breaking news.
 */
#[Tool(
  id: 'prompt_post_news:breaking_news',
  label: new TranslatableMarkup('Breaking News'),
  description: new TranslatableMarkup('Flag or unflag an article as breaking news. When flagged, the article is marked as breaking, promoted to the front page, and published immediately. Also logs the action.'),
  operation: ToolOperation::Write,
  input_definitions: [
    'nid' => new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Node ID'),
      description: new TranslatableMarkup('The node ID of the article to flag as breaking news.'),
      required: TRUE,
    ),
    'breaking' => new InputDefinition(
      data_type: 'boolean',
      label: new TranslatableMarkup('Breaking'),
      description: new TranslatableMarkup('TRUE to flag as breaking news, FALSE to remove the flag. Defaults to TRUE.'),
      required: FALSE,
    ),
  ],
)]
final class BreakingNews extends ToolBase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerChannelInterface $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->logger = $container->get('logger.channel.prompt_post_news');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    $nid = $values['nid'];
    $breaking = $values['breaking'] ?? TRUE;

    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    if (!$node) {
      return ExecutableResult::failure($this->t('Node @nid not found.', ['@nid' => $nid]));
    }

    if ($node->bundle() !== 'article') {
      return ExecutableResult::failure($this->t('Node @nid is a @type, not an article. Breaking news can only be set on articles.', [
        '@nid' => $nid,
        '@type' => $node->bundle(),
      ]));
    }

    // Create a new revision.
    $node->setNewRevision(TRUE);
    $node->set('field_breaking_news', $breaking);

    if ($breaking) {
      // Promote to front page and publish.
      $node->setPromoted(TRUE);
      $node->set('moderation_state', 'published');
      $node->setRevisionLogMessage('Flagged as BREAKING NEWS via MCP');
      $this->logger->notice('BREAKING NEWS: "@title" (nid: @nid) flagged as breaking news.', [
        '@title' => $node->getTitle(),
        '@nid' => $nid,
      ]);
    }
    else {
      $node->set('field_breaking_news', FALSE);
      $node->setRevisionLogMessage('Breaking news flag removed via MCP');
      $this->logger->notice('Breaking news flag removed from "@title" (nid: @nid).', [
        '@title' => $node->getTitle(),
        '@nid' => $nid,
      ]);
    }

    $node->setRevisionCreationTime(\Drupal::time()->getRequestTime());
    $node->save();

    $action_text = $breaking ? 'flagged as BREAKING NEWS and published' : 'breaking news flag removed';
    $summary = $this->t('Article "@title" (nid: @nid) @action.', [
      '@title' => $node->getTitle(),
      '@nid' => $nid,
      '@action' => $action_text,
    ]);

    return ExecutableResult::success($summary, [
      'nid' => $nid,
      'title' => $node->getTitle(),
      'breaking_news' => $breaking,
      'promoted' => $node->isPromoted(),
      'moderation_state' => $node->get('moderation_state')->value,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    // Only editors and admins who can edit articles should set breaking news.
    $access_result = AccessResult::allowedIfHasPermission($account, 'edit any article content');
    return $return_as_object ? $access_result : $access_result->isAllowed();
  }

}
