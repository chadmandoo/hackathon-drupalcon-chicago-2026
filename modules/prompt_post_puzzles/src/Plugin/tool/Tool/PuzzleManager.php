<?php

declare(strict_types=1);

namespace Drupal\prompt_post_puzzles\Plugin\tool\Tool;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\prompt_post_puzzles\Service\SudokuGenerator;
use Drupal\tool\Attribute\Tool;
use Drupal\tool\ExecutableResult;
use Drupal\tool\Tool\ToolBase;
use Drupal\tool\Tool\ToolOperation;
use Drupal\tool\TypedData\InputDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manage the weekly Sudoku puzzle for The Prompt Post.
 */
#[Tool(
  id: 'prompt_post_puzzles:puzzle_manager',
  label: new TranslatableMarkup('Puzzle Manager'),
  description: new TranslatableMarkup('Manage The Prompt Post weekly Sudoku puzzle. Actions: "get_current" returns the active puzzle, "generate_new" creates a fresh puzzle with optional difficulty (easy/medium/hard) and seed, "set_difficulty" changes the difficulty level.'),
  operation: ToolOperation::Write,
  input_definitions: [
    'action' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Action'),
      description: new TranslatableMarkup('The action: "get_current", "generate_new", or "set_difficulty".'),
      required: TRUE,
    ),
    'difficulty' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Difficulty'),
      description: new TranslatableMarkup('Puzzle difficulty: "easy" (36-42 clues), "medium" (28-35 clues), "hard" (22-27 clues). Used with generate_new and set_difficulty.'),
      required: FALSE,
    ),
    'seed' => new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Random seed'),
      description: new TranslatableMarkup('Optional seed for reproducible puzzle generation. Use a week number or date-based integer for weekly puzzles.'),
      required: FALSE,
    ),
  ],
)]
final class PuzzleManager extends ToolBase {

  protected ConfigFactoryInterface $configFactory;
  protected SudokuGenerator $generator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->configFactory = $container->get('config.factory');
    $instance->generator = $container->get('prompt_post_puzzles.generator');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    $action = $values['action'];

    return match ($action) {
      'get_current' => $this->getCurrent(),
      'generate_new' => $this->generateNew($values),
      'set_difficulty' => $this->setDifficulty($values),
      default => ExecutableResult::failure($this->t('Invalid action "@action". Valid: get_current, generate_new, set_difficulty.', ['@action' => $action])),
    };
  }

  /**
   * Get the current active puzzle.
   */
  private function getCurrent(): ExecutableResult {
    $config = $this->configFactory->get('prompt_post_puzzles.settings');
    $custom = $config->get('custom_puzzle');
    $difficulty = $config->get('difficulty') ?? 'medium';
    $index = $config->get('current_puzzle_index') ?? 0;

    if ($custom) {
      $puzzle = json_decode($custom, TRUE);
      $source = 'custom (generated via MCP)';
    }
    else {
      $puzzle = $this->generator->generate($difficulty, $index);
      $source = "auto-generated (index: $index, difficulty: $difficulty)";
    }

    // Count clues in the mask.
    $clue_count = 0;
    foreach ($puzzle['mask'] as $row) {
      $clue_count += array_sum(array_map(fn($v) => $v === 1 ? 1 : 0, $row));
    }

    return ExecutableResult::success(
      $this->t('Current puzzle: @source with @clues clues revealed.', [
        '@source' => $source,
        '@clues' => $clue_count,
      ]),
      [
        'source' => $source,
        'difficulty' => $difficulty,
        'puzzle_index' => $index,
        'clue_count' => $clue_count,
        'puzzle' => $puzzle,
      ]
    );
  }

  /**
   * Generate a new puzzle and set it as current.
   */
  private function generateNew(array $values): ExecutableResult {
    $difficulty = $values['difficulty'] ?? NULL;
    $seed = isset($values['seed']) ? (int) $values['seed'] : NULL;

    $config = $this->configFactory->getEditable('prompt_post_puzzles.settings');

    if ($difficulty) {
      if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
        return ExecutableResult::failure($this->t('Invalid difficulty "@diff". Use: easy, medium, hard.', ['@diff' => $difficulty]));
      }
      $config->set('difficulty', $difficulty);
    }
    else {
      $difficulty = $config->get('difficulty') ?? 'medium';
    }

    // Use provided seed or generate one from current timestamp.
    if ($seed === NULL) {
      $seed = (int) date('YW');
    }

    $puzzle = $this->generator->generate($difficulty, $seed);

    // Store as custom puzzle.
    $config->set('custom_puzzle', json_encode($puzzle));
    $config->set('current_puzzle_index', $seed);
    $config->save();

    $clue_count = 0;
    foreach ($puzzle['mask'] as $row) {
      $clue_count += array_sum(array_map(fn($v) => $v === 1 ? 1 : 0, $row));
    }

    return ExecutableResult::success(
      $this->t('New @difficulty puzzle generated with seed @seed. @clues clues revealed. Puzzle is now live on The Prompt Post.', [
        '@difficulty' => $difficulty,
        '@seed' => $seed,
        '@clues' => $clue_count,
      ]),
      [
        'difficulty' => $difficulty,
        'seed' => $seed,
        'clue_count' => $clue_count,
        'puzzle' => $puzzle,
      ]
    );
  }

  /**
   * Set the difficulty level.
   */
  private function setDifficulty(array $values): ExecutableResult {
    $difficulty = $values['difficulty'] ?? NULL;
    if (!$difficulty || !in_array($difficulty, ['easy', 'medium', 'hard'])) {
      return ExecutableResult::failure($this->t('Provide a valid difficulty: easy, medium, hard.'));
    }

    $config = $this->configFactory->getEditable('prompt_post_puzzles.settings');
    $old = $config->get('difficulty') ?? 'medium';
    $config->set('difficulty', $difficulty);
    // Clear custom puzzle so next fetch regenerates with new difficulty.
    $config->set('custom_puzzle', NULL);
    $config->save();

    return ExecutableResult::success(
      $this->t('Puzzle difficulty changed from @old to @new. Next puzzle fetch will use the new difficulty.', [
        '@old' => $old,
        '@new' => $difficulty,
      ]),
      [
        'previous_difficulty' => $old,
        'new_difficulty' => $difficulty,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    $action = $values['action'] ?? 'get_current';
    if ($action === 'get_current') {
      $access_result = AccessResult::allowedIfHasPermission($account, 'access content');
    }
    else {
      $access_result = AccessResult::allowedIfHasPermission($account, 'administer site configuration');
    }
    return $return_as_object ? $access_result : $access_result->isAllowed();
  }

}
