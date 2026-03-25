<?php

declare(strict_types=1);

namespace Drupal\prompt_post_puzzles\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\prompt_post_puzzles\Service\SudokuGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * API controller for serving puzzles to the SPA.
 */
final class PuzzleApiController extends ControllerBase {

  protected SudokuGenerator $generator;

  public function __construct(
    ConfigFactoryInterface $config_factory,
    SudokuGenerator $generator,
  ) {
    $this->configFactory = $config_factory;
    $this->generator = $generator;
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('prompt_post_puzzles.generator'),
    );
  }

  /**
   * GET /api/puzzles/current — returns the active Sudoku puzzle.
   */
  public function current(): JsonResponse {
    $config = $this->configFactory->get('prompt_post_puzzles.settings');
    $custom = $config->get('custom_puzzle');
    $difficulty = $config->get('difficulty') ?? 'medium';
    $index = $config->get('current_puzzle_index') ?? 0;

    if ($custom) {
      $puzzle = json_decode($custom, TRUE);
    }
    else {
      // Generate deterministically from the index.
      $puzzle = $this->generator->generate($difficulty, $index);
    }

    return new JsonResponse([
      'difficulty' => $difficulty,
      'puzzle' => $puzzle,
    ]);
  }

}
