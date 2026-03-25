# The Prompt Post — Full Project Context

You are working on **The Prompt Post**, a Drupal 11 site built for the Acquia "Permission to Run" AX Hackathon at DrupalCon Chicago 2026. This is a satirical AI news publication where Claude manages the site via MCP with Drupal enforcing permissions.

## Immediately Read These Files

Before doing anything, read these to understand the project:

1. `CLAUDE.md` — Quick reference (paths, commands, don't-break list)
2. `docs/architecture.md` — System diagram and request flows
3. `docs/custom-modules.md` — All 4 custom modules with file-by-file breakdown
4. `docs/mcp-tools-reference.md` — All 32 MCP tools with permissions
5. `docs/permissions-and-workflow.md` — 3-tier role model and editorial workflow
6. `docs/patches.md` — What we patched in contrib and why
7. `docs/frontend-spa.md` — React SPA architecture and API consumption
8. `docs/deployment.md` — Server infrastructure, file locations, common operations

## The Stack

- **Drupal 11.3** on PHP 8.3 with PostgreSQL
- **MCP Server module** (drupal/mcp_server) — patched with our alter hook
- **OAuth 2.1** via simple_oauth + simple_oauth_21 (PKCE, DCR, server metadata)
- **React SPA** (Vite + Tailwind) at thepromptpost.chadpeppers.dev
- **Caddy** reverse proxy with auto-TLS

## Custom Module Locations

```
web/modules/custom/
├── prompt_post_news/      # 12 MCP tools + API + alter hook (THE MAIN MODULE)
├── prompt_post_jobs/      # Jobs API endpoint
├── prompt_post_puzzles/   # Sudoku generator + puzzle_manager tool
└── prompt_post_opinion/   # Opinion section marker
```

## The 12 Custom MCP Tools (prompt_post_news + prompt_post_puzzles)

| Tool | File | What It Does |
|------|------|-------------|
| `site_briefing` | SiteBriefing.php | Live site orientation — run first |
| `editorial_dashboard` | EditorialDashboard.php | Content stats overview |
| `content_search` | ContentSearch.php | Full-text article search |
| `content_moderator` | ContentModerator.php | View by moderation state |
| `content_publisher` | ContentPublisher.php | State transitions with role enforcement |
| `breaking_news` | BreakingNews.php | Flag as breaking, auto-publish |
| `site_analytics` | SiteAnalytics.php | Publishing trends and stats |
| `taxonomy_manager` | TaxonomyManager.php | Category CRUD with counts |
| `recent_activity` | RecentActivity.php | Watchdog activity feed |
| `whos_online` | WhosOnline.php | Active users |
| `module_help` | ModuleHelp.php | Read hook_help from modules |
| `puzzle_manager` | PuzzleManager.php | Sudoku generation + config |

## Permission Model (Critical)

**Writer** (sarah_editor, mike_editor) — 22 tools. Can draft + submit. CANNOT publish.
**Reviewer** (alex_reviewer) — 23 tools. Can publish + send back. CANNOT archive or manage users.
**Admin** (jane_admin) — 32 tools. Full access.

Tool filtering happens in `prompt_post_news.module` via `hook_mcp_server_enabled_tools_alter()`.
Execution-time checks happen in each tool's `checkAccess()` method.
The `content_publisher` tool has per-transition permission checks with actionable error messages.

## Error Result API

Use `ExecutableResult::success($message, $data)` and `ExecutableResult::failure($message)`.
There is NO `ExecutableResult::error()` method — that will crash.

## Contrib Patches Applied

One patch: `patches/mcp_server--instructions-properties-delete-tools-alter.patch`
- Fixes `properties: []` → `properties: {}` for tools with no inputs
- Adds `instructions` config to initialize response
- Adds DELETE method to /_mcp route
- Adds `hook_mcp_server_enabled_tools_alter()` for tool filtering
- Removes `final` from McpBridgeService

## Key Gotchas

- PHP built-in server behind Caddy means Drupal doesn't always know its external URL. OAuth metadata `registration_endpoint` is explicitly set in config.
- Dynamic Client Registration defaults to 300s token lifetime — we changed it to 86400s in the contrib code.
- The SPA lives at a separate domain — CORS must include it in `web/sites/default/services.yml`.
- Tool plugins that define `input_definitions` in the `#[Tool]` attribute use `ToolOperation::Read` or `ToolOperation::Write` (not `::Update` — doesn't exist).
- The `access()` method on contrib tool plugins requires entity context values and crashes with empty inputs. Don't try to call it for access filtering at discovery time — use permission mapping instead.

## URLs

| URL | Purpose |
|-----|---------|
| `https://drupalcon2026.chadpeppers.dev` | Drupal backend (admin, MCP, OAuth) |
| `https://drupalcon2026.chadpeppers.dev/_mcp` | MCP endpoint for Claude |
| `https://thepromptpost.chadpeppers.dev` | SPA frontend |
| `https://thepromptpost.chadpeppers.com` | SPA alternate domain |

## Hackathon Submission

Repo: https://github.com/acquia/hackathon-drupalcon-chicago-2026
Deadline: March 25, 2026 12:00 PM CT
Required: README, Agent Run Log, Agent Experience Report, AX artifact
