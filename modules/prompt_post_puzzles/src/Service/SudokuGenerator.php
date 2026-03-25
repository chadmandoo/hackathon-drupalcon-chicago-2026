<?php

declare(strict_types=1);

namespace Drupal\prompt_post_puzzles\Service;

/**
 * Generates valid Sudoku puzzles with solutions.
 */
final class SudokuGenerator {

  /**
   * Generate a puzzle with a given difficulty.
   *
   * @param string $difficulty
   *   One of 'easy', 'medium', 'hard'.
   * @param int|null $seed
   *   Optional random seed for reproducible puzzles.
   *
   * @return array
   *   Array with 'solution' (9x9 grid) and 'mask' (9x9, 1=given, 0=empty).
   */
  public function generate(string $difficulty = 'medium', ?int $seed = NULL): array {
    if ($seed !== NULL) {
      mt_srand($seed);
    }

    // Generate a complete valid solution.
    $solution = $this->generateSolution();

    // Determine how many cells to reveal based on difficulty.
    $clues = match ($difficulty) {
      'easy' => mt_rand(36, 42),
      'medium' => mt_rand(28, 35),
      'hard' => mt_rand(22, 27),
      default => mt_rand(28, 35),
    };

    // Create mask by removing cells.
    $mask = $this->createMask($solution, $clues);

    // Reset random seed.
    if ($seed !== NULL) {
      mt_srand();
    }

    return [
      'solution' => $solution,
      'mask' => $mask,
    ];
  }

  /**
   * Generate a complete valid Sudoku solution using backtracking.
   */
  private function generateSolution(): array {
    $board = array_fill(0, 9, array_fill(0, 9, 0));
    $this->fillBoard($board);
    return $board;
  }

  /**
   * Recursively fill the board with valid numbers.
   */
  private function fillBoard(array &$board): bool {
    for ($row = 0; $row < 9; $row++) {
      for ($col = 0; $col < 9; $col++) {
        if ($board[$row][$col] === 0) {
          $numbers = range(1, 9);
          shuffle($numbers);

          foreach ($numbers as $num) {
            if ($this->isValid($board, $row, $col, $num)) {
              $board[$row][$col] = $num;
              if ($this->fillBoard($board)) {
                return TRUE;
              }
              $board[$row][$col] = 0;
            }
          }
          return FALSE;
        }
      }
    }
    return TRUE;
  }

  /**
   * Check if placing a number is valid.
   */
  private function isValid(array $board, int $row, int $col, int $num): bool {
    // Check row.
    if (in_array($num, $board[$row])) {
      return FALSE;
    }

    // Check column.
    for ($r = 0; $r < 9; $r++) {
      if ($board[$r][$col] === $num) {
        return FALSE;
      }
    }

    // Check 3x3 box.
    $boxRow = intdiv($row, 3) * 3;
    $boxCol = intdiv($col, 3) * 3;
    for ($r = $boxRow; $r < $boxRow + 3; $r++) {
      for ($c = $boxCol; $c < $boxCol + 3; $c++) {
        if ($board[$r][$c] === $num) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

  /**
   * Create a mask that reveals only the specified number of clues.
   */
  private function createMask(array $solution, int $clues): array {
    $mask = array_fill(0, 9, array_fill(0, 9, 1));
    $cellsToRemove = 81 - $clues;

    // Create list of all cell positions and shuffle.
    $positions = [];
    for ($r = 0; $r < 9; $r++) {
      for ($c = 0; $c < 9; $c++) {
        $positions[] = [$r, $c];
      }
    }
    shuffle($positions);

    $removed = 0;
    foreach ($positions as [$r, $c]) {
      if ($removed >= $cellsToRemove) {
        break;
      }
      $mask[$r][$c] = 0;
      $removed++;
    }

    return $mask;
  }

  /**
   * Validate that a puzzle array is properly formed.
   */
  public function validatePuzzle(array $puzzle): bool {
    if (!isset($puzzle['solution']) || !isset($puzzle['mask'])) {
      return FALSE;
    }

    if (count($puzzle['solution']) !== 9 || count($puzzle['mask']) !== 9) {
      return FALSE;
    }

    foreach ($puzzle['solution'] as $row) {
      if (count($row) !== 9) {
        return FALSE;
      }
      foreach ($row as $val) {
        if (!is_int($val) || $val < 1 || $val > 9) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

}
