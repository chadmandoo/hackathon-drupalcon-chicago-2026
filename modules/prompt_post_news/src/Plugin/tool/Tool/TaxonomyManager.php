<?php

declare(strict_types=1);

namespace Drupal\prompt_post_news\Plugin\tool\Tool;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\taxonomy\Entity\Term;
use Drupal\tool\Attribute\Tool;
use Drupal\tool\ExecutableResult;
use Drupal\tool\Tool\ToolBase;
use Drupal\tool\Tool\ToolOperation;
use Drupal\tool\TypedData\InputDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manage taxonomy terms (categories) with article counts.
 */
#[Tool(
  id: 'prompt_post_news:taxonomy_manager',
  label: new TranslatableMarkup('Taxonomy Manager'),
  description: new TranslatableMarkup('List, create, update, or delete taxonomy terms (categories). Shows article counts per category. Use action "list" to see all categories, "create" to add one, "update" to rename, or "delete" to remove.'),
  operation: ToolOperation::Write,
  input_definitions: [
    'action' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Action'),
      description: new TranslatableMarkup('The action to perform: "list", "create", "update", or "delete".'),
      required: TRUE,
    ),
    'vocabulary' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Vocabulary'),
      description: new TranslatableMarkup('The vocabulary machine name. Defaults to "category".'),
      required: FALSE,
    ),
    'term_name' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Term name'),
      description: new TranslatableMarkup('The name of the term to create, or the new name for an update.'),
      required: FALSE,
    ),
    'term_id' => new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Term ID'),
      description: new TranslatableMarkup('The term ID for update or delete operations.'),
      required: FALSE,
    ),
  ],
)]
final class TaxonomyManager extends ToolBase {

  protected Connection $database;
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->database = $container->get('database');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    $action = $values['action'];
    $vocabulary = $values['vocabulary'] ?? 'category';

    switch ($action) {
      case 'list':
        return $this->listTerms($vocabulary);

      case 'create':
        if (empty($values['term_name'])) {
          return ExecutableResult::failure($this->t('term_name is required for create action.'));
        }
        return $this->createTerm($vocabulary, $values['term_name']);

      case 'update':
        if (empty($values['term_id']) || empty($values['term_name'])) {
          return ExecutableResult::failure($this->t('term_id and term_name are required for update action.'));
        }
        return $this->updateTerm((int) $values['term_id'], $values['term_name']);

      case 'delete':
        if (empty($values['term_id'])) {
          return ExecutableResult::failure($this->t('term_id is required for delete action.'));
        }
        return $this->deleteTerm((int) $values['term_id']);

      default:
        return ExecutableResult::failure($this->t('Invalid action "@action". Valid: list, create, update, delete.', ['@action' => $action]));
    }
  }

  /**
   * List all terms with article counts.
   */
  protected function listTerms(string $vocabulary): ExecutableResult {
    $query = $this->database->select('taxonomy_term_field_data', 't');
    $query->fields('t', ['tid', 'name']);
    $query->condition('t.vid', $vocabulary);
    $query->leftJoin('node__field_category', 'fc', 't.tid = fc.field_category_target_id');
    $query->addExpression('COUNT(fc.entity_id)', 'article_count');
    $query->groupBy('t.tid');
    $query->groupBy('t.name');
    $query->orderBy('t.name', 'ASC');
    $results = $query->execute()->fetchAll();

    $terms = [];
    foreach ($results as $row) {
      $terms[] = [
        'tid' => (int) $row->tid,
        'name' => $row->name,
        'article_count' => (int) $row->article_count,
      ];
    }

    $summary = $this->t('@count categories in @vocab vocabulary.', [
      '@count' => count($terms),
      '@vocab' => $vocabulary,
    ]);

    return ExecutableResult::success($summary, ['vocabulary' => $vocabulary, 'terms' => $terms]);
  }

  /**
   * Create a new term.
   */
  protected function createTerm(string $vocabulary, string $name): ExecutableResult {
    // Check for duplicates.
    $existing = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties(['name' => $name, 'vid' => $vocabulary]);
    if (!empty($existing)) {
      return ExecutableResult::failure($this->t('Term "@name" already exists in @vocab.', [
        '@name' => $name,
        '@vocab' => $vocabulary,
      ]));
    }

    $term = Term::create(['vid' => $vocabulary, 'name' => $name]);
    $term->save();

    return ExecutableResult::success(
      $this->t('Created term "@name" (tid: @tid) in @vocab.', [
        '@name' => $name,
        '@tid' => $term->id(),
        '@vocab' => $vocabulary,
      ]),
      ['tid' => (int) $term->id(), 'name' => $name, 'vocabulary' => $vocabulary]
    );
  }

  /**
   * Update a term name.
   */
  protected function updateTerm(int $tid, string $new_name): ExecutableResult {
    $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
    if (!$term) {
      return ExecutableResult::failure($this->t('Term @tid not found.', ['@tid' => $tid]));
    }

    $old_name = $term->getName();
    $term->setName($new_name);
    $term->save();

    return ExecutableResult::success(
      $this->t('Renamed term from "@old" to "@new" (tid: @tid).', [
        '@old' => $old_name,
        '@new' => $new_name,
        '@tid' => $tid,
      ]),
      ['tid' => $tid, 'old_name' => $old_name, 'new_name' => $new_name]
    );
  }

  /**
   * Delete a term.
   */
  protected function deleteTerm(int $tid): ExecutableResult {
    $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
    if (!$term) {
      return ExecutableResult::failure($this->t('Term @tid not found.', ['@tid' => $tid]));
    }

    $name = $term->getName();
    $term->delete();

    return ExecutableResult::success(
      $this->t('Deleted term "@name" (tid: @tid).', ['@name' => $name, '@tid' => $tid]),
      ['tid' => $tid, 'name' => $name, 'deleted' => TRUE]
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    $action = $values['action'] ?? 'list';
    if ($action === 'list') {
      $access_result = AccessResult::allowedIfHasPermission($account, 'access content');
    }
    else {
      $access_result = AccessResult::allowedIfHasPermission($account, 'administer taxonomy');
    }
    return $return_as_object ? $access_result : $access_result->isAllowed();
  }

}
