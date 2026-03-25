<?php

declare(strict_types=1);

namespace Drupal\prompt_post_news\Plugin\tool\Tool;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tool\Attribute\Tool;
use Drupal\tool\ExecutableResult;
use Drupal\tool\Tool\ToolBase;
use Drupal\tool\Tool\ToolOperation;
use Drupal\tool\TypedData\InputDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exposes Drupal module help documentation via MCP.
 *
 * This is a developer-facing tool that demonstrates how MCP can surface
 * Drupal's hook_help system, giving AI assistants access to module
 * documentation for understanding site capabilities and architecture.
 */
#[Tool(
  id: 'prompt_post_news:module_help',
  label: new TranslatableMarkup('Module Help'),
  description: new TranslatableMarkup('Get documentation for any installed Drupal module via hook_help. Use action "list" to see all modules with help available, or "get" with a module_name to read a specific module\'s documentation. A developer tool for understanding site capabilities.'),
  operation: ToolOperation::Read,
  input_definitions: [
    'action' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Action'),
      description: new TranslatableMarkup('"list" to see all modules with help, or "get" to read a specific module\'s help.'),
      required: TRUE,
    ),
    'module_name' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Module name'),
      description: new TranslatableMarkup('The machine name of the module (e.g., "prompt_post_news"). Required for "get" action.'),
      required: FALSE,
    ),
  ],
)]
final class ModuleHelp extends ToolBase {

  protected ModuleHandlerInterface $moduleHandler;
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->moduleExtensionList = $container->get('extension.list.module');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    $action = $values['action'];

    return match ($action) {
      'list' => $this->listModulesWithHelp(),
      'get' => $this->getModuleHelp($values['module_name'] ?? NULL),
      default => ExecutableResult::failure($this->t('Invalid action "@action". Use "list" or "get".', ['@action' => $action])),
    };
  }

  /**
   * List all installed modules that implement hook_help.
   */
  private function listModulesWithHelp(): ExecutableResult {
    $modules_with_help = [];

    $installed = $this->moduleExtensionList->getAllInstalledInfo();

    foreach ($installed as $module_name => $info) {
      // Check if the module implements hook_help.
      $function = $module_name . '_help';
      if (function_exists($function) || $this->moduleHandler->hasImplementations('help', $module_name)) {
        $modules_with_help[] = [
          'module' => $module_name,
          'name' => $info['name'] ?? $module_name,
          'description' => $info['description'] ?? '',
          'package' => $info['package'] ?? 'Other',
        ];
      }
    }

    // Sort by package then name.
    usort($modules_with_help, function ($a, $b) {
      $pkg = strcmp($a['package'], $b['package']);
      return $pkg !== 0 ? $pkg : strcmp($a['name'], $b['name']);
    });

    return ExecutableResult::success(
      $this->t('@count modules have help documentation available.', [
        '@count' => count($modules_with_help),
      ]),
      ['modules' => $modules_with_help]
    );
  }

  /**
   * Get a specific module's help documentation.
   */
  private function getModuleHelp(?string $module_name): ExecutableResult {
    if (empty($module_name)) {
      return ExecutableResult::failure($this->t('module_name is required for the "get" action.'));
    }

    // Check module is installed.
    if (!$this->moduleHandler->moduleExists($module_name)) {
      return ExecutableResult::failure($this->t('Module "@module" is not installed.', ['@module' => $module_name]));
    }

    // Get module info.
    $info = $this->moduleExtensionList->getExtensionInfo($module_name);

    // Invoke hook_help for the module's help page route.
    $route_name = 'help.page.' . $module_name;

    // Create a minimal route match object.
    $help_text = '';
    $function = $module_name . '_help';
    $this->moduleHandler->loadInclude($module_name, 'module');

    if (function_exists($function)) {
      $route_match = \Drupal::routeMatch();
      $result = $function($route_name, $route_match);
      if ($result) {
        $help_text = $result;
      }
    }

    if (empty($help_text)) {
      return ExecutableResult::success(
        $this->t('Module "@module" is installed but has no help page documentation.', [
          '@module' => $module_name,
        ]),
        [
          'module' => $module_name,
          'name' => $info['name'] ?? $module_name,
          'description' => $info['description'] ?? '',
          'package' => $info['package'] ?? 'Other',
          'help_text' => NULL,
        ]
      );
    }

    // Strip HTML for clean text output, but keep structure.
    $clean_text = strip_tags($help_text, '<h2><h3><p><ul><ol><li><dl><dt><dd><table><thead><tbody><tr><th><td><strong><em><code>');

    return ExecutableResult::success(
      $this->t('Help documentation for "@module" (@name).', [
        '@module' => $module_name,
        '@name' => $info['name'] ?? $module_name,
      ]),
      [
        'module' => $module_name,
        'name' => $info['name'] ?? $module_name,
        'description' => $info['description'] ?? '',
        'package' => $info['package'] ?? 'Other',
        'version' => $info['version'] ?? NULL,
        'help_html' => $help_text,
        'help_text' => strip_tags($help_text),
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
