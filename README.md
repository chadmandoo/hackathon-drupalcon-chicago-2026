# The Prompt Post — Drupal MCP Editorial Platform

**Team:** Chad Peppers (remote, GitHub: @chadmandoo) + Ryan Mott (on-site DrupalCon lead, GitHub: @rmott-littler)

**Live Site:** https://thepromptpost.chadpeppers.dev
**Drupal Backend:** https://drupalcon2026.chadpeppers.dev
**MCP Endpoint:** https://drupalcon2026.chadpeppers.dev/_mcp
**Source:** https://github.com/chadmandoo (repo link TBD)

## What we built

We built **The Prompt Post**, a fully functional satirical AI news publication where Claude manages the entire editorial workflow through Drupal's MCP (Model Context Protocol) integration. The site has a decoupled React frontend (newspaper-style design), a Drupal 11 backend with 32 MCP tools, OAuth 2.1 authentication with Dynamic Client Registration, and a three-tier permission model that governs what the AI can do based on the authenticated user's role.

The core demonstration: connect Claude.ai to a Drupal site by pasting a single URL. Claude automatically discovers OAuth, registers itself as a client, authenticates, and receives a set of tools filtered by the user's Drupal role. A Writer sees 22 tools and can draft articles but cannot publish. A Reviewer sees 23 tools and can publish but cannot manage users. An Admin sees all 32 tools. Drupal is the governor at every layer — discovery, execution, and audit.

We built 12 custom MCP tools across 4 Drupal modules (`prompt_post_news`, `prompt_post_jobs`, `prompt_post_puzzles`, `prompt_post_opinion`), a PHP Sudoku puzzle generator, a decoupled React SPA frontend, and contributed a patch to the `drupal/mcp_server` module adding `hook_mcp_server_enabled_tools_alter()` for community-wide tool filtering.

The site is seeded with 15+ full-length AI-themed articles (500-600 words each with subheadings and quotes), 4 events, 10 satirical job listings, categories, users with tiered roles, and a content moderation workflow (draft → review → published → archived).

## Problem addressed

AI agents need to interact with CMS platforms, but current approaches either embed the AI inside the application (limiting it to one interface) or give it unrestricted API access (a security risk). The Drupal community needs a pattern where:

1. AI agents connect externally via open standards (not vendor-locked)
2. The CMS governs what the agent can do using existing permission systems
3. The same rules apply to humans and agents — no special backdoors
4. Multiple agents with different roles can connect simultaneously
5. The setup is simple enough that a non-developer can connect Claude by pasting a URL

## What the agent did

Claude (via Claude Code, Opus 4.6 model) built the entire project autonomously with human guidance on direction:

- **Architecture:** Designed the module structure, permission model, and editorial workflow
- **Custom modules:** Wrote all 4 Drupal modules (prompt_post_news, prompt_post_jobs, prompt_post_puzzles, prompt_post_opinion) with 12 MCP tool plugins
- **OAuth setup:** Configured OAuth 2.1 + PKCE + Dynamic Client Registration, debugged token lifetime issues, fixed the registration_endpoint URL for reverse proxy setups
- **Content creation:** Wrote all 15+ satirical AI news articles (500-600 words each), 10 job listings, event content, taxonomy terms
- **Frontend:** Ported a Replit React SPA to work with Drupal's API, added article detail pages, configured Caddy reverse proxy with API routing
- **Bug fixes:** Found and fixed the `properties: []` vs `{}` JSON Schema bug that broke Claude.ai's MCP connector, fixed `ExecutableResult::error()` → `::failure()` across all tools
- **Contrib patch:** Created `hook_mcp_server_enabled_tools_alter()`, server instructions support, DELETE method handling — packaged as a reusable patch
- **Documentation:** Generated all docs (architecture, modules, tools reference, permissions, deployment, patches), CLAUDE.md, .claude/commands/coder, hook_help for all modules
- **Site briefing:** Built automatic agent orientation via MCP `instructions` field + `site_briefing` tool + `site_introduction` prompt
- **Tool access filtering:** Implemented role-based tool visibility so agents only discover tools they can use

## What the human did

Chad Peppers (human) provided:

- **Creative direction:** Chose "The Prompt Post" concept, AI/robot theme, newspaper aesthetic
- **Strategic decisions:** Module organization, which tools to build vs use from contrib, permission tier design, SPA vs server-rendered frontend
- **Frontend design:** Created the React SPA design in Replit (newspaper layout, Tailwind styling)
- **Testing:** Connected via Claude.ai as different users, identified MCP connector issues (protocol version, token expiry, JSON Schema validation)
- **Infrastructure:** Set up the DigitalOcean server, Caddy, DNS, domain registration
- **Feedback loop:** Directed Claude on priorities, caught issues Claude couldn't see from the server side (Claude.ai error messages, UI behavior)
- **Content review:** Approved article topics and tone

Ryan Mott is the on-site DrupalCon team lead.

## Drupal-in-the-loop

Drupal governs agent behavior at three distinct layers:

### 1. Discovery-time filtering
`hook_mcp_server_enabled_tools_alter()` (our contrib patch) removes tools the user can't access before they're sent to the agent. A Writer literally never sees `user_block` or `system_status`. The agent can't attempt what it doesn't know exists.

### 2. Execution-time permission checks
Every tool plugin implements `checkAccess()` against Drupal's standard permission system. The `content_publisher` tool checks per-transition workflow permissions and returns actionable error messages: "Access denied. You do not have permission to publish. Required permission: use editorial transition publish. Your roles: authenticated, editor."

### 3. Content moderation workflow
The Editorial workflow (draft → review → published → archived) enforces review gates. Content must pass through human review before publication. The workflow uses Drupal core's Content Moderation module — the same system human editors use.

### Additional governance
- **OAuth 2.1 + PKCE:** Every MCP connection is authenticated. Token carries user identity.
- **Revision tracking:** Every tool action creates a Drupal revision with a log message ("publish: review → published via MCP by sarah_editor").
- **Watchdog audit:** All actions are logged and queryable via the `recent_activity` tool.
- **Entity access:** Contrib tools check per-entity access (can this user edit THIS node?).

## AX artifact(s) shipped

1. **AGENTS.md** — How agents should work with this Drupal site (included in submission)
2. **`hook_mcp_server_enabled_tools_alter()`** — Contrib patch adding a Drupal alter hook for tool filtering. Any module can implement it. Follows standard Drupal patterns.
3. **MCP `instructions` config** — Server-side instructions automatically injected into the agent's system prompt on connection. Configurable via `drush config:set`.
4. **`site_briefing` tool** — Dynamic orientation tool that tells the agent its role, available tools, content stats, and suggested first steps.
5. **`module_help` tool** — Exposes Drupal's `hook_help` system to agents, letting them read module documentation to understand site capabilities.
6. **`/coder` command** — Claude Code command (`.claude/commands/coder.md`) that gives a new session complete project context without searching the codebase.
7. **Comprehensive docs/** — 8 architecture documents covering every aspect of the system.

## How to run / demo

### Connect from Claude.ai (simplest)

1. Go to Claude.ai Settings → Integrations/MCP
2. Add server: `https://drupalcon2026.chadpeppers.dev/_mcp`
3. Log in as any user when prompted:
   - `sarah_editor` / `editor123` — Writer (22 tools)
   - `alex_reviewer` / `reviewer123` — Reviewer (23 tools)
   - `jane_admin` / `admin123` — Admin (32 tools)
4. Start a new conversation
5. Ask Claude to run `site_briefing`

### Demo flow

1. Connect as `sarah_editor` (Writer)
2. Ask: "What's the editorial dashboard look like?"
3. Ask: "Search for articles about AI"
4. Ask: "Create a new article about robot chefs"
5. Ask: "Publish that article" → **DENIED** (Writer can't publish)
6. Disconnect, reconnect as `alex_reviewer`
7. Ask: "What content is pending review?"
8. Ask: "Publish the robot chefs article" → **SUCCESS**
9. Visit https://thepromptpost.chadpeppers.dev → article appears on the live site

### Local setup

```bash
git clone <repo>
composer install
# Generate OAuth keys
mkdir -p oauth-keys
openssl genrsa -out oauth-keys/private.key 2048
openssl rsa -in oauth-keys/private.key -pubout -out oauth-keys/public.key
# Configure Drupal (database, settings.php)
drush site:install standard
drush en prompt_post_news prompt_post_jobs prompt_post_puzzles prompt_post_opinion
# Apply contrib patch
cd web/modules/contrib/mcp_server
git apply ../../../../patches/mcp_server--instructions-properties-delete-tools-alter.patch
drush cr
```

See [docs/mcp-setup.md](docs/mcp-setup.md) for detailed OAuth and MCP configuration.

## Validation

```bash
# Run the hackathon submission validator
python3 starter-kit/tools/check_submission.py submission/

# Verify MCP tools are exposed
curl -s https://drupalcon2026.chadpeppers.dev/_mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"initialize","id":1,"params":{"protocolVersion":"2025-03-26","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}'

# Verify SPA frontend
curl -s https://thepromptpost.chadpeppers.dev/api/articles | python3 -m json.tool

# Verify OAuth metadata
curl -s https://drupalcon2026.chadpeppers.dev/.well-known/oauth-authorization-server | python3 -m json.tool
```

## Limits / known issues

- PHP built-in server (not production Apache/Nginx) — adequate for demo, not production
- Sudoku puzzle SPA integration is API-ready but the React component still has hardcoded fallback puzzles
- Jobs are hardcoded in PHP, not a content type — a future version would use entity-backed jobs
- The `content_publisher` tool's transition enforcement happens at execution time for actions that depend on current content state (state-specific checks require knowing the node's current moderation state)
- `featured` flag logic could be refined — currently too many articles marked as featured

## Links

- Live site: https://thepromptpost.chadpeppers.dev
- Drupal admin: https://drupalcon2026.chadpeppers.dev
- MCP endpoint: https://drupalcon2026.chadpeppers.dev/_mcp
- Contrib patch: [patches/mcp_server--instructions-properties-delete-tools-alter.patch](patches/mcp_server--instructions-properties-delete-tools-alter.patch)
- Architecture docs: [docs/](docs/)
