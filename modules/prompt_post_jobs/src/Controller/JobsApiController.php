<?php

declare(strict_types=1);

namespace Drupal\prompt_post_jobs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * API controller for job listings, backed by the job_listing content type.
 */
final class JobsApiController extends ControllerBase {

  public function __construct(
    private readonly Connection $database,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
    );
  }

  /**
   * GET /api/jobs — returns Job[] from job_listing nodes.
   */
  public function list(): JsonResponse {
    $query = $this->database->select('node_field_data', 'n');
    $query->fields('n', ['nid', 'title', 'created']);
    $query->condition('n.type', 'job_listing');
    $query->condition('n.status', 1);
    $query->orderBy('n.created', 'DESC');
    $results = $query->execute()->fetchAll();

    $jobs = [];
    foreach ($results as $row) {
      $nid = (int) $row->nid;

      $body = $this->database->select('node__body', 'b')
        ->fields('b', ['body_value'])
        ->condition('b.entity_id', $nid)
        ->execute()->fetchField();

      $company = $this->database->select('node__field_company', 'fc')
        ->fields('fc', ['field_company_value'])
        ->condition('fc.entity_id', $nid)
        ->execute()->fetchField();

      $location = $this->database->select('node__field_job_location', 'fl')
        ->fields('fl', ['field_job_location_value'])
        ->condition('fl.entity_id', $nid)
        ->execute()->fetchField();

      $remote = (bool) $this->database->select('node__field_remote', 'fr')
        ->fields('fr', ['field_remote_value'])
        ->condition('fr.entity_id', $nid)
        ->execute()->fetchField();

      $tag_results = $this->database->select('node__field_job_tags', 'ft')
        ->fields('ft', ['field_job_tags_value'])
        ->condition('ft.entity_id', $nid)
        ->execute()->fetchAll();
      $tags = array_map(fn($t) => $t->field_job_tags_value, $tag_results);

      $jobs[] = [
        'id' => $nid,
        'title' => $row->title,
        'company' => $company ?: '',
        'location' => $location ?: '',
        'description' => $body ? strip_tags($body) : '',
        'tags' => $tags,
        'postedDate' => date('c', (int) $row->created),
        'remote' => $remote,
      ];
    }

    return new JsonResponse($jobs);
  }

}
