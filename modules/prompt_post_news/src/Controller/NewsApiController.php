<?php

declare(strict_types=1);

namespace Drupal\prompt_post_news\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller serving articles for The Prompt Post SPA.
 */
final class NewsApiController extends ControllerBase {

  public function __construct(
    private readonly Connection $database,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
    );
  }

  /**
   * GET /api/articles — returns Article[] for the SPA.
   */
  public function articles(Request $request): JsonResponse {
    $category = $request->query->get('category');

    $query = $this->database->select('node_field_data', 'n');
    $query->fields('n', ['nid', 'title', 'type', 'created', 'promote']);
    $query->condition('n.type', 'article');
    $query->condition('n.status', 1);
    $query->join('users_field_data', 'u', 'n.uid = u.uid');
    $query->addField('u', 'name', 'author');
    $query->orderBy('n.created', 'DESC');

    // Filter by category if provided.
    if ($category) {
      $cat_map = $this->getCategoryMap();
      if ($category === 'news') {
        // "news" = everything except opinion-like categories
        $opinion_cats = ['Ethics & Policy', 'Culture & Satire'];
        $news_tids = [];
        foreach ($cat_map as $name => $tid) {
          if (!in_array($name, $opinion_cats)) {
            $news_tids[] = $tid;
          }
        }
        if ($news_tids) {
          $query->join('node__field_category', 'fc', 'n.nid = fc.entity_id');
          $query->condition('fc.field_category_target_id', $news_tids, 'IN');
        }
      }
      elseif ($category === 'opinion') {
        $opinion_cats = ['Ethics & Policy', 'Culture & Satire'];
        $opinion_tids = [];
        foreach ($cat_map as $name => $tid) {
          if (in_array($name, $opinion_cats)) {
            $opinion_tids[] = $tid;
          }
        }
        if ($opinion_tids) {
          $query->join('node__field_category', 'fc', 'n.nid = fc.entity_id');
          $query->condition('fc.field_category_target_id', $opinion_tids, 'IN');
        }
      }
    }

    $results = $query->execute()->fetchAll();
    $articles = [];

    foreach ($results as $row) {
      $articles[] = $this->buildArticle($row);
    }

    return new JsonResponse($articles);
  }

  /**
   * GET /api/articles/featured — returns the featured Article.
   */
  public function featured(): JsonResponse {
    // Featured = promoted to front page, or most recent breaking news.
    $query = $this->database->select('node_field_data', 'n');
    $query->fields('n', ['nid', 'title', 'type', 'created', 'promote']);
    $query->condition('n.type', 'article');
    $query->condition('n.status', 1);
    $query->join('users_field_data', 'u', 'n.uid = u.uid');
    $query->addField('u', 'name', 'author');

    // Prefer breaking news, then promoted, then most recent.
    $query->leftJoin('node__field_breaking_news', 'bn', 'n.nid = bn.entity_id');
    $query->addField('bn', 'field_breaking_news_value', 'breaking');
    $query->orderBy('bn.field_breaking_news_value', 'DESC');
    $query->orderBy('n.promote', 'DESC');
    $query->orderBy('n.created', 'DESC');
    $query->range(0, 1);

    $row = $query->execute()->fetch();

    if (!$row) {
      return new JsonResponse(NULL, 404);
    }

    return new JsonResponse($this->buildArticle($row));
  }

  /**
   * GET /api/articles/{nid} — returns a single Article with full HTML body.
   */
  public function detail(int $nid): JsonResponse {
    $query = $this->database->select('node_field_data', 'n');
    $query->fields('n', ['nid', 'title', 'type', 'created', 'promote']);
    $query->condition('n.nid', $nid);
    $query->condition('n.type', ['article', 'event'], 'IN');
    $query->condition('n.status', 1);
    $query->join('users_field_data', 'u', 'n.uid = u.uid');
    $query->addField('u', 'name', 'author');
    $row = $query->execute()->fetch();

    if (!$row) {
      return new JsonResponse(['error' => 'Article not found'], 404);
    }

    $article = $this->buildArticle($row, TRUE);
    return new JsonResponse($article);
  }

  /**
   * GET /api/healthz — health check.
   */
  public function health(): JsonResponse {
    return new JsonResponse(['status' => 'ok']);
  }

  /**
   * Build an Article object in the shape the SPA expects.
   */
  private function buildArticle(\stdClass $row, bool $fullHtml = FALSE): array {
    $nid = (int) $row->nid;

    // Get teaser.
    $teaser = $this->database->select('node__field_teaser', 'ft')
      ->fields('ft', ['field_teaser_value'])
      ->condition('ft.entity_id', $nid)
      ->execute()->fetchField();

    // Get body.
    $body = $this->database->select('node__body', 'b')
      ->fields('b', ['body_value'])
      ->condition('b.entity_id', $nid)
      ->execute()->fetchField();

    // Get category name.
    $category_name = $this->database->select('node__field_category', 'fc')
      ->condition('fc.entity_id', $nid)
      ->join('taxonomy_term_field_data', 't', 'fc.field_category_target_id = t.tid');
    $category_name = $this->database->select('node__field_category', 'fc');
    $category_name->condition('fc.entity_id', $nid);
    $category_name->join('taxonomy_term_field_data', 't', 'fc.field_category_target_id = t.tid');
    $category_name->addField('t', 'name');
    $cat = $category_name->execute()->fetchField();

    // Map Drupal categories to SPA categories.
    $spa_category = 'news';
    if ($cat && in_array($cat, ['Ethics & Policy', 'Culture & Satire'])) {
      $spa_category = 'opinion';
    }

    // Check if breaking news / promoted.
    $breaking = $this->database->select('node__field_breaking_news', 'bn')
      ->fields('bn', ['field_breaking_news_value'])
      ->condition('bn.entity_id', $nid)
      ->execute()->fetchField();

    return [
      'id' => $nid,
      'title' => $row->title,
      'summary' => $teaser ?: '',
      'content' => $body ? ($fullHtml ? $body : strip_tags($body)) : '',
      'author' => $row->author,
      'date' => date('c', (int) $row->created),
      'category' => $spa_category,
      'tags' => $cat ? [$cat] : [],
      'featured' => (bool) $breaking || (bool) $row->promote,
    ];
  }

  /**
   * Get category name -> tid mapping.
   */
  private function getCategoryMap(): array {
    $results = $this->database->select('taxonomy_term_field_data', 't')
      ->fields('t', ['tid', 'name'])
      ->condition('t.vid', 'category')
      ->execute()->fetchAll();

    $map = [];
    foreach ($results as $row) {
      $map[$row->name] = (int) $row->tid;
    }
    return $map;
  }

}
