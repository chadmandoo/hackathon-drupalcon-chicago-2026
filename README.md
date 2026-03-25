# The Prompt Post — Drupal MCP Editorial Platform

**Team:**
- Chad Peppers — remote ([drupal.org/u/chadmandoo](https://www.drupal.org/u/chadmandoo), GitHub: @chadmandoo)
- Ryan Mott — on-site DrupalCon lead ([drupal.org/u/rymo](https://www.drupal.org/u/rymo), GitHub: @rmott-littler)
- Monica McLaassen — contributor ([drupal.org/u/mclaassen](https://www.drupal.org/u/mclaassen))

**Live Site:** https://thepromptpost.chadpeppers.dev
**Drupal Backend:** https://drupalcon2026.chadpeppers.dev
**MCP Endpoint:** https://drupalcon2026.chadpeppers.dev/_mcp
**Source:** https://github.com/chadmandoo/hackathon-drupalcon-chicago-2026
**Demo Video:** [link TBD]
**Presentation:** [presentation.md](presentation.md)

---

## TL;DR

We connected Claude to a Drupal 11 newspaper site via MCP and proved that **desktop AI + Drupal is the future of editorial workflow** — not embedded AI chatbots in the admin UI.

**The thesis:** The AI agent belongs on the editor's desktop, not inside the CMS. Claude becomes the editorial interface. Drupal stays the governed backend. The editor gets AI intelligence + local file access + CMS power in one conversation. This is what Cursor did for developers, applied to content management.

**What works today (not theory — working product):**
- Paste one URL into Claude, log in, manage the entire site
- Writers draft and submit. Reviewers publish. Admins control everything. Drupal enforces the boundaries.
- Claude reads your local research docs, writes articles, and publishes them to Drupal — no copy-paste, no file uploads
- Charts, analytics, content planning, site status, user management — all from the desktop
- Same MCP connection serves editors, developers, and sysadmins with role-appropriate tools

**What we shipped:** 4 Drupal modules, 12 custom MCP tools, 32 total tools, 3-tier permission model, decoupled React SPA, contrib patch with `hook_mcp_server_enabled_tools_alter()`, and a live working demo.

**The bottom line:** The future of Drupal editorial is not "AI in the admin UI." It's "Drupal as the governed backend for AI agents that live wherever the editor works."

---

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

## Why desktop AI, not embedded AI?

Most approaches try to put AI inside Drupal — a chatbot in the admin, an assistant in the content form. We went the other direction: **the AI lives on the editor's desktop, Drupal is the governed backend.**

This is fundamentally better:

- **Local context:** The editor has research docs, competitor analysis, brand guidelines, notes — all on their machine. A desktop AI reads all of it. An embedded chatbot can't.
- **No UI to build:** The AI IS the interface. No custom admin themes, no React components in Drupal, no UX design sprints. You talk to Claude and it manages the site.
- **Cursor for content:** What Cursor did for developers (AI-native code editor), Claude + MCP does for content teams. The editing experience lives locally; the CMS is the governed backend.
- **Multi-source intelligence:** Claude can read your local files, search the web, analyze data, generate charts, plan content calendars — then publish the result to Drupal. The CMS only sees clean, finished work.
- **Tool-agnostic:** Any MCP-capable AI works. Claude today, others tomorrow. The Drupal side doesn't change.

Claude Cowork (Claude.ai's desktop mode) already demonstrates this: gathering data, presenting graphs and analytics locally, planning content, then publishing. This is not a theory — it's a working workflow we used to build this project.

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

### Live demo

The fastest way to see this in action is the demo video: **[link TBD]**

The live site is running at:
- **Frontend:** https://thepromptpost.chadpeppers.dev
- **MCP endpoint:** https://drupalcon2026.chadpeppers.dev/_mcp

### Connect from Claude.ai

1. Go to Claude.ai Settings → Integrations/MCP
2. Add server URL: `https://drupalcon2026.chadpeppers.dev/_mcp`
3. Claude discovers OAuth automatically — log in when prompted
4. Start a new conversation and ask Claude to run `site_briefing`

Different Drupal roles see different tools: Writers (22 tools) can draft but not publish. Reviewers (23 tools) can publish. Admins (32 tools) have full control.

### Local setup

```bash
# Clone and install
git clone https://github.com/chadmandoo/hackathon-drupalcon-chicago-2026.git
cd hackathon-drupalcon-chicago-2026

# Set up Drupal (requires PHP 8.3+, PostgreSQL or MySQL, Composer)
composer install
drush site:install standard --db-url=pgsql://user:pass@localhost/dbname

# Import the site configuration
drush config:import -y

# Apply the MCP server contrib patch
cd web/modules/contrib/mcp_server
git apply ../../../../patches/mcp_server--instructions-properties-delete-tools-alter.patch
cd ../../../..
drush cr

# Generate OAuth keys
mkdir -p oauth-keys
openssl genrsa -out oauth-keys/private.key 2048
openssl rsa -in oauth-keys/private.key -pubout -out oauth-keys/public.key
chmod 600 oauth-keys/private.key

# Configure OAuth key paths
drush config:set simple_oauth.settings public_key $(pwd)/oauth-keys/public.key -y
drush config:set simple_oauth.settings private_key $(pwd)/oauth-keys/private.key -y

# Set the registration endpoint to your domain
drush config:set simple_oauth_server_metadata.settings registration_endpoint "https://your-domain.com/oauth/register" -y

# Create users with roles
drush user:create writer_user --password=changeme
drush user:role:add editor writer_user
drush user:create reviewer_user --password=changeme
drush user:role:add reviewer reviewer_user
drush user:create admin_user --password=changeme
drush user:role:add site_admin admin_user

# Grant OAuth permission to all roles
drush role:perm:add editor 'grant simple_oauth codes'
drush role:perm:add reviewer 'grant simple_oauth codes'
drush role:perm:add site_admin 'grant simple_oauth codes'

drush cr
```

Then connect from Claude.ai using `https://your-domain.com/_mcp`.

See [docs/mcp-setup.md](docs/mcp-setup.md) for detailed configuration including reverse proxy setup and troubleshooting.

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

## Challenges & lessons learned

### MCP Server Module — Works Great, Setup Is Hard
The `drupal/mcp_server` module is solid once configured, but getting there requires understanding OAuth 2.1, PKCE, Dynamic Client Registration, and server metadata simultaneously. We patched the module to fix bugs and add missing features (tool filtering, server instructions, JSON Schema fix). The module feels developer-first today — it needs a guided setup experience for site builders. That said, once running, the MCP connection is remarkably stable and performant.

### Claude Desktop OAuth — Caching Friction
The OAuth connection works well, but Claude caches aggressively. Switching between users (Writer vs Admin) requires disconnecting and reconnecting the MCP server entirely. The tool list persists in Claude's session even after server-side permission changes. These are UX friction points, not deal-breakers — the underlying protocol is sound. Regular users wouldn't switch roles frequently.

### MCP Client Landscape — Growing but Uneven
Claude Desktop and Claude.ai have full MCP support and work great. ChatGPT's web interface supports remote MCP servers, but the desktop app does not (as of March 2026). Any MCP-capable client should work in theory — the spec is open — but we couldn't test all clients in the available time.

### Per-Tool Authorization Is a Feature
When connecting, the user must grant access to each tool. This feels heavy the first time but it's actually an important governance layer: the human explicitly approves what the AI can do, adding consent on top of Drupal's role-based permissions.

### What AI Didn't Do Well
Honestly, not much. The AI (Claude Opus 4.6 via Claude Code) built the entire project — 4 modules, 12 tools, 15 articles, a React integration, OAuth debugging, patches, and documentation — in a single session. The bottleneck was never AI capability. It was the MCP module's setup complexity and OAuth configuration quirks. The developer and the setup process were the limiting factors, not the AI.

## The future

**Claude is becoming a Cursor-like experience.** Anthropic is building toward a desktop environment where AI can see and render visual output. Imagine Claude rendering your Drupal site directly in its interface — you edit an article in conversation, see the live preview update, and publish when satisfied. The workaround today is local static previews (which Claude handles), but native site rendering would be transformative.

**This could be a contributed module ecosystem:** a "Drupal Editorial AI" distribution with pre-configured MCP tools, a setup wizard for OAuth, role-based tool presets, and visual preview integration. The technology works today. The ecosystem needs to catch up.

## Limits / known issues

- PHP built-in server (not production Apache/Nginx) — adequate for demo, not production
- Sudoku puzzle SPA integration is API-ready but the React component still has hardcoded fallback puzzles
- `featured` flag logic could be refined — currently too many articles marked as featured

## Links

- Live site: https://thepromptpost.chadpeppers.dev
- Drupal admin: https://drupalcon2026.chadpeppers.dev
- MCP endpoint: https://drupalcon2026.chadpeppers.dev/_mcp
- Presentation: [presentation.md](presentation.md)
- Contrib patch: [patches/mcp_server--instructions-properties-delete-tools-alter.patch](patches/mcp_server--instructions-properties-delete-tools-alter.patch)
- Architecture docs: [docs/](docs/)
- Demo video: [link TBD]
