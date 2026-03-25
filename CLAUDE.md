# The Prompt Post — Drupal MCP Hackathon Project

## What This Is

A satirical AI news publication ("The Prompt Post") built on Drupal 11 for the Acquia "Permission to Run" AX Hackathon at DrupalCon Chicago 2026. Claude manages the site via MCP (Model Context Protocol) with Drupal enforcing permissions on every action.

## Quick Reference

- **Drupal root:** `/srv/drupmcp/web/`
- **Custom modules:** `web/modules/custom/prompt_post_*`
- **Patches:** `/srv/drupmcp/patches/`
- **SPA build:** `/srv/prompt-post/` (source: `/home/cpeppers/prompt_post/`)
- **MCP endpoint:** `https://drupalcon2026.chadpeppers.dev/_mcp`
- **SPA frontend:** `https://thepromptpost.chadpeppers.dev`

## Key Commands

```bash
vendor/bin/drush cr                    # Clear cache
vendor/bin/drush watchdog:show --count=20  # View logs
vendor/bin/drush uli --uri=https://drupalcon2026.chadpeppers.dev  # Admin login
```

## Architecture

Read `docs/architecture.md` for the full system diagram. Key points:
- Caddy reverse proxy → PHP 8.3 built-in server → Drupal 11 → PostgreSQL
- OAuth 2.1 + PKCE + Dynamic Client Registration for MCP auth
- React SPA at separate domain, Caddy proxies `/api/*` to Drupal
- `hook_mcp_server_enabled_tools_alter()` filters tools by role at discovery time

## Custom Modules

- `prompt_post_news` — 12 MCP tools (editorial dashboard, search, publisher, breaking news, etc.) + SPA API endpoints + tool access filtering via alter hook
- `prompt_post_jobs` — Jobs API (10 satirical listings)
- `prompt_post_puzzles` — Sudoku generator + puzzle_manager MCP tool + API
- `prompt_post_opinion` — Section marker (depends on news module)

See `docs/custom-modules.md` for file-by-file breakdown.

## 32 MCP Tools

See `docs/mcp-tools-reference.md` for the complete reference. Key tools:
- `site_briefing` — Run first. Returns live site orientation.
- `content_publisher` — Role-enforced workflow transitions (the key demo)
- `breaking_news` — Flag articles, auto-publish
- `puzzle_manager` — Generate weekly Sudoku
- `module_help` — Read any module's hook_help documentation

## Permissions (3-tier model)

| Role | Sees | Can Do | Cannot Do |
|------|------|--------|-----------|
| Writer (22 tools) | Content tools | Draft, submit for review | Publish, admin |
| Reviewer (23 tools) | + breaking_news | Publish, send back | Archive, user mgmt |
| Admin (32 tools) | Everything | Everything | — |

See `docs/permissions-and-workflow.md` for the full matrix.

## Contrib Patches

One patch file: `patches/mcp_server--instructions-properties-delete-tools-alter.patch`
Fixes: empty properties JSON bug, adds instructions support, DELETE method, tools alter hook.
See `docs/patches.md` for details.

## Coding Conventions

- PHP: `declare(strict_types=1)`, typed properties, comprehensive docblocks
- Tool plugins: extend `ToolBase`, use `#[Tool]` attribute, implement `checkAccess()` and `doExecute()`
- Error results: use `ExecutableResult::failure()` (NOT `::error()` — that doesn't exist)
- Module structure: `src/Plugin/tool/Tool/` for MCP tools, `src/Controller/` for API endpoints

## Don't Break

- The MCP endpoint (`/_mcp`) — Claude.ai connects here
- OAuth endpoints (`/oauth/*`) — authentication flow
- `.well-known/*` paths — OAuth discovery
- `/api/*` endpoints — SPA frontend consumes these
- CORS config in `web/sites/default/services.yml`
- Token lifetime in simple_oauth_client_registration (changed from 300s to 86400s)
