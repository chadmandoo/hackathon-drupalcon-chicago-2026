# Custom Modules

All custom modules live in `web/modules/custom/` under the `Prompt Post` package.

## prompt_post_news

**Path:** `web/modules/custom/prompt_post_news/`
**Purpose:** Core editorial module. All MCP tools for content management + API endpoints for the SPA.

### Files

```
prompt_post_news/
├── prompt_post_news.info.yml          # Module definition
├── prompt_post_news.module            # hook_help + hook_mcp_server_enabled_tools_alter
├── prompt_post_news.routing.yml       # API routes: /api/articles, /api/articles/featured, /api/articles/{nid}, /api/healthz
├── prompt_post_news.services.yml      # Logger channel
└── src/
    ├── Controller/
    │   └── NewsApiController.php      # SPA API: articles list, featured, detail, health
    └── Plugin/tool/Tool/
        ├── EditorialDashboard.php     # Content stats, pending reviews, author activity
        ├── ContentSearch.php          # Full-text search across titles/body
        ├── ContentModerator.php       # View content by moderation state
        ├── ContentPublisher.php       # Change moderation state (role-enforced transitions)
        ├── BreakingNews.php           # Flag article as breaking, auto-publish + promote
        ├── SiteAnalytics.php          # Publishing trends, category stats, events
        ├── TaxonomyManager.php        # CRUD for categories with article counts
        ├── RecentActivity.php         # Activity timeline from watchdog
        ├── WhosOnline.php             # Active users report
        ├── ModuleHelp.php             # Expose hook_help from any module (developer tool)
        └── SiteBriefing.php           # Full site orientation for connecting agents
```

### Key Implementation: Tool Access Filtering

`prompt_post_news.module` implements `hook_mcp_server_enabled_tools_alter()` to filter the tools list based on the current user's Drupal permissions. This means:

- **Writers see 22 tools** (no admin or reviewer-only tools)
- **Reviewers see 23 tools** (adds breaking_news)
- **Admins see 32 tools** (everything)

The permission map is defined in the alter hook. Tools not in the map default to visible for all authenticated users.

### API Endpoints (NewsApiController)

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/articles` | GET | All published articles. Optional `?category=news\|opinion` filter |
| `/api/articles/featured` | GET | Single featured article (breaking news or promoted) |
| `/api/articles/{nid}` | GET | Single article with full HTML body |
| `/api/healthz` | GET | `{"status": "ok"}` |

The controller transforms Drupal nodes into the flat JSON shape the React SPA expects:
```json
{
  "id": 18,
  "title": "...",
  "summary": "...",
  "content": "<p>Full HTML body</p>",
  "author": "sarah_editor",
  "date": "2026-03-25T00:45:58+00:00",
  "category": "news",
  "tags": ["AI & Machine Learning"],
  "featured": false
}
```

Category mapping: articles in "Ethics & Policy" or "Culture & Satire" → `"opinion"`. Everything else → `"news"`.

---

## prompt_post_jobs

**Path:** `web/modules/custom/prompt_post_jobs/`
**Purpose:** Serves the Classifieds/Jobs section.

### Files

```
prompt_post_jobs/
├── prompt_post_jobs.info.yml
├── prompt_post_jobs.module            # hook_help
├── prompt_post_jobs.routing.yml       # /api/jobs
└── src/Controller/
    └── JobsApiController.php          # Returns 10 satirical job listings
```

Jobs are hardcoded in the controller (not a content type). 10 satirical AI-industry listings including "Vending Machine Therapist" and "AI Model Retirement Counselor".

---

## prompt_post_puzzles

**Path:** `web/modules/custom/prompt_post_puzzles/`
**Purpose:** Sudoku puzzle generation and MCP management.

### Files

```
prompt_post_puzzles/
├── prompt_post_puzzles.info.yml
├── prompt_post_puzzles.module         # hook_help
├── prompt_post_puzzles.routing.yml    # /api/puzzles/current
├── prompt_post_puzzles.services.yml   # SudokuGenerator service + logger
├── config/
│   ├── install/
│   │   └── prompt_post_puzzles.settings.yml   # Default config
│   └── schema/
│       └── prompt_post_puzzles.schema.yml     # Config schema
└── src/
    ├── Controller/
    │   └── PuzzleApiController.php    # Serves current puzzle to SPA
    ├── Plugin/tool/Tool/
    │   └── PuzzleManager.php          # MCP tool: get/generate/configure puzzles
    └── Service/
        └── SudokuGenerator.php        # PHP backtracking Sudoku generator
```

### Sudoku Generator

`SudokuGenerator::generate(string $difficulty, ?int $seed)` creates valid 9x9 puzzles:
- **Easy:** 36-42 clues revealed
- **Medium:** 28-35 clues
- **Hard:** 22-27 clues
- Seed-based for reproducible weekly puzzles

### Config

`prompt_post_puzzles.settings`:
- `current_puzzle_index` — seed for deterministic generation
- `difficulty` — easy/medium/hard
- `custom_puzzle` — JSON puzzle data when generated via MCP

---

## prompt_post_opinion

**Path:** `web/modules/custom/prompt_post_opinion/`
**Purpose:** Opinion section marker. Depends on prompt_post_news.

### Files

```
prompt_post_opinion/
├── prompt_post_opinion.info.yml       # Depends on prompt_post_news
└── prompt_post_opinion.module         # hook_help (documents category mapping)
```

No tools of its own. Opinion articles are regular Article nodes with categories "Ethics & Policy" or "Culture & Satire". The SPA and API handle the section split.
