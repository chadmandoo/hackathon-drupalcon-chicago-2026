<?php

declare(strict_types=1);

namespace Drupal\prompt_post_news\Plugin\tool\Tool;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tool\Attribute\Tool;
use Drupal\tool\ExecutableResult;
use Drupal\tool\Tool\ToolBase;
use Drupal\tool\Tool\ToolOperation;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a comprehensive site briefing for connecting AI agents.
 *
 * This tool gives an AI assistant everything it needs to understand
 * the site: what it is, what content exists, what tools are available,
 * what the current user can do, and how to get started.
 */
#[Tool(
  id: 'prompt_post_news:site_briefing',
  label: new TranslatableMarkup('Site Briefing'),
  description: new TranslatableMarkup('Get a comprehensive introduction to The Prompt Post site. Returns site info, content stats, your current role and permissions, available tools, editorial workflow, and suggested first steps. Run this first when connecting.'),
  operation: ToolOperation::Read,
)]
final class SiteBriefing extends ToolBase {

  protected Connection $database;
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->database = $container->get('database');
    $instance->moduleExtensionList = $container->get('extension.list.module');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    $account = \Drupal::currentUser();

    // Site info.
    $site_name = \Drupal::config('system.site')->get('name');
    $site_slogan = \Drupal::config('system.site')->get('slogan');

    // Current user context.
    $roles = $account->getRoles(TRUE);
    $role_label = 'Anonymous';
    if (in_array('site_admin', $roles)) {
      $role_label = 'Admin (full access)';
    }
    elseif (in_array('reviewer', $roles)) {
      $role_label = 'Reviewer (can publish, cannot admin)';
    }
    elseif (in_array('editor', $roles)) {
      $role_label = 'Writer (can draft and submit, cannot publish)';
    }
    elseif (!$account->isAnonymous()) {
      $role_label = 'Authenticated (basic access)';
    }

    // Content counts.
    $article_count = (int) $this->database->select('node_field_data', 'n')
      ->condition('n.type', 'article')->condition('n.status', 1)
      ->countQuery()->execute()->fetchField();
    $event_count = (int) $this->database->select('node_field_data', 'n')
      ->condition('n.type', 'event')->condition('n.status', 1)
      ->countQuery()->execute()->fetchField();
    $draft_count = (int) $this->database->select('content_moderation_state_field_data', 'cms')
      ->condition('cms.moderation_state', 'draft')
      ->condition('cms.content_entity_type_id', 'node')
      ->countQuery()->execute()->fetchField();
    $review_count = (int) $this->database->select('content_moderation_state_field_data', 'cms')
      ->condition('cms.moderation_state', 'review')
      ->condition('cms.content_entity_type_id', 'node')
      ->countQuery()->execute()->fetchField();

    // Categories.
    $categories = [];
    $results = $this->database->select('taxonomy_term_field_data', 't')
      ->fields('t', ['name'])
      ->condition('t.vid', 'category')
      ->orderBy('t.name')
      ->execute()->fetchAll();
    foreach ($results as $row) {
      $categories[] = $row->name;
    }

    // User count.
    $user_count = (int) $this->database->select('users_field_data', 'u')
      ->condition('u.uid', 0, '>')
      ->condition('u.status', 1)
      ->countQuery()->execute()->fetchField();

    // Available tools (from MCP config).
    $tool_configs = \Drupal::entityTypeManager()
      ->getStorage('mcp_tool_config')
      ->loadMultiple();
    $tool_names = [];
    foreach ($tool_configs as $config) {
      if ($config->status()) {
        $tool_names[] = $config->id();
      }
    }
    sort($tool_names);

    // Build the briefing.
    $data = [
      'site' => [
        'name' => $site_name,
        'slogan' => $site_slogan,
        'frontend_url' => 'https://thepromptpost.chadpeppers.dev',
        'admin_url' => 'https://drupalcon2026.chadpeppers.dev',
        'description' => 'A satirical AI news publication built on Drupal 11, demonstrating MCP-powered site management with permission-based governance.',
      ],
      'your_access' => [
        'username' => $account->getAccountName(),
        'role' => $role_label,
        'roles' => $roles,
      ],
      'content_overview' => [
        'published_articles' => $article_count,
        'published_events' => $event_count,
        'drafts_pending' => $draft_count,
        'in_review' => $review_count,
        'categories' => $categories,
        'active_users' => $user_count,
      ],
      'editorial_workflow' => [
        'states' => ['draft', 'review', 'published', 'archived'],
        'writer_can' => ['Create drafts', 'Submit for review'],
        'writer_cannot' => ['Publish', 'Archive', 'Manage users'],
        'reviewer_can' => ['Publish from review', 'Send back to draft'],
        'reviewer_cannot' => ['Archive', 'Manage users', 'Site configuration'],
        'admin_can' => ['Everything including archive, restore, user management'],
      ],
      'available_tools' => $tool_names,
      'suggested_first_steps' => [
        '1. Run editorial_dashboard for a content overview',
        '2. Use content_search to find articles by keyword',
        '3. Use content_moderator to see workflow states',
        '4. Try content_publisher to manage article states (subject to your role)',
        '5. Use module_help with action=list to discover module documentation',
      ],
      'sections' => [
        'News' => 'AI & Machine Learning, Robotics, Prompt Engineering, Industry articles',
        'Opinion' => 'Ethics & Policy, Culture & Satire articles',
        'Jobs' => '10 satirical AI-industry job listings',
        'Puzzles' => 'Weekly Sudoku puzzle (manageable via puzzle_manager tool)',
      ],
    ];

    $summary = $this->t('Welcome to @site. You are connected as @user (@role). @articles published articles, @drafts drafts, @review in review. @tools tools available.', [
      '@site' => $site_name,
      '@user' => $account->getAccountName(),
      '@role' => $role_label,
      '@articles' => $article_count,
      '@drafts' => $draft_count,
      '@review' => $review_count,
      '@tools' => count($tool_names),
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
