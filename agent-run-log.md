# Agent Run Log

## Agent(s) used
- **Name:** Claude (Anthropic)
- **Model:** Opus 4.6 (1M context)
- **Interface:** Claude Code CLI (for building) + Claude.ai web (for MCP testing)
- **MCP connection:** Claude.ai Cowork with remote MCP server

## Goal
Build a complete Drupal 11 site demonstrating AI editorial management via MCP, with permission-based governance, a decoupled SPA frontend, and custom MCP tools — from zero to submission in one session.

## Key prompts / instructions
- "We are going to enter a Drupal to Claude MCP where you can manage the site and get information from it"
- "Lets come up with some ideas on what we can do. We probably will need to transform this website into a fictitious website"
- "We need to provide some sort of permission based function calling so not anyone can just run any functions"
- "Play on the AI angle and call it The Prompt Post so the articles will be fictitious articles read by AI and robot related"
- "Create tools — editorial_dashboard, content_search, content_publisher, breaking_news, site_analytics, taxonomy_manager, content_moderator, recent_activity"
- "Create a drupal module that does the sudoku generation"
- "Is there any way to present only the tools the person requesting has access to instead of everything and then just denying?"
- "Create a hook_help on each module and a tool that exposes hook_help"
- "Is there a sort of intro or init type tool that can give the connecting agent a heads up of the site?"

## Commands / tool calls

### Infrastructure setup
```bash
composer require drupal/mcp_server drupal/simple_oauth_21 drupal/tailwindcss drupal/gin drupal/admin_toolbar
drush en mcp_server simple_oauth simple_oauth_21 simple_oauth_pkce simple_oauth_server_metadata simple_oauth_client_registration
openssl genrsa -out oauth-keys/private.key 2048
openssl rsa -in oauth-keys/private.key -pubout -out oauth-keys/public.key
```

### OAuth debugging
```bash
# Discovered token lifetime was 300s (5 min) for dynamic clients — changed to 86400s
# Fixed registration_endpoint URL (was http://default, needed https://drupalcon2026.chadpeppers.dev)
drush config:set simple_oauth_server_metadata.settings registration_endpoint "https://drupalcon2026.chadpeppers.dev/oauth/register"
```

### MCP bug fixes
```bash
# Fixed properties: [] → properties: {} for tools with no inputs (broke Claude.ai connector)
# Fixed ExecutableResult::error() → ExecutableResult::failure() (19 occurrences)
# Added DELETE method to MCP route (Claude.ai sends DELETE for session cleanup)
```

### Content seeding
```bash
drush php:script /tmp/seed_gazette.php      # Initial content
drush php:script /tmp/rebrand_prompt_post.php  # Rebrand to Prompt Post
drush php:script /tmp/expand_articles.php   # Expand to full articles
drush php:script /tmp/seed_replit_articles.php  # Add Replit funny articles
```

### Module creation
```bash
# Created 4 custom modules with 12 MCP tools
web/modules/custom/prompt_post_news/       # 12 tools + API
web/modules/custom/prompt_post_jobs/       # Jobs API
web/modules/custom/prompt_post_puzzles/    # Sudoku + tool
web/modules/custom/prompt_post_opinion/    # Section marker
```

### SPA build & deploy
```bash
cd /tmp/prompt-post-build && npx vite build
sudo cp -r dist/* /srv/prompt-post/
sudo caddy reload --config /etc/caddy/Caddyfile
```

### Contrib patch
```bash
cd web/modules/contrib/mcp_server && git diff > patches/mcp_server--instructions-properties-delete-tools-alter.patch
```

## Important iterations

### Iteration 1: OAuth connection spinning
- **Attempt:** Connected Claude.ai to MCP endpoint
- **Result:** Connection spun indefinitely, never completed auth
- **Root cause:** `simple_oauth_client_registration` module not enabled — Claude.ai needs Dynamic Client Registration (RFC 7591) to register itself
- **Fix:** Enabled the module, fixed `registration_endpoint` URL in metadata

### Iteration 2: Token expiry
- **Attempt:** Connected successfully but tools returned "Access token has been revoked"
- **Result:** Tokens expired within 5 minutes
- **Root cause:** Dynamic Client Registration defaulted to 300s access token lifetime
- **Fix:** Updated all existing clients to 86400s, changed default in ClientRegistrationService.php

### Iteration 3: Claude.ai schema validation error
- **Attempt:** Connected to MCP, Claude reported "tools.5.FrontendRemoteMcpToolDefinition.input_schema.properties: Input should be a valid dictionary"
- **Result:** All tools failed to register in Claude's session
- **Root cause:** PHP `json_encode([])` produces `[]` for empty arrays, but JSON Schema requires `properties` to be `{}` (object). Three tools with no inputs were affected.
- **Fix:** `empty($properties) ? new \stdClass() : $properties` in ToolApi.php

### Iteration 4: ExecutableResult::error() doesn't exist
- **Attempt:** Claude.ai called tools, got server errors
- **Result:** "Call to undefined method ExecutableResult::error()"
- **Root cause:** The Tool module's API uses `::success()` and `::failure()`, not `::error()`
- **Fix:** Global find-replace across 19 occurrences in 6 files

### Iteration 5: Tool access filtering
- **Attempt:** Wanted to filter tools by role at discovery time, not just execution
- **Result:** First tried service decorator — failed because McpBridgeService is `final`
- **Adjustment:** Created `hook_mcp_server_enabled_tools_alter()` in the contrib patch + implemented it in our module. Proper Drupal pattern, contributes back to the community.

## Validation run
- **MCP tools/list:** 32 tools returned, 0 with invalid schemas
- **Role filtering:** Writer sees 22, Reviewer sees 23, Admin sees 32
- **SPA API:** /api/articles returns 15 articles, /api/jobs returns 10
- **OAuth flow:** Full DCR + PKCE flow verified
- **Manual testing:** Connected from Claude.ai as all 3 roles, tested permission boundaries

## Notes
- The entire project was built in a single Claude Code session (~5 hours of active work)
- Claude wrote all 15 satirical AI news articles (500-600 words each) — they're genuinely funny
- The biggest time sinks were OAuth debugging (token lifetime, endpoint URLs) and the JSON Schema properties bug
- Claude.ai's MCP connector is strict about JSON Schema validation — this is actually good, it exposed a real bug
- The `hook_mcp_server_enabled_tools_alter()` pattern is the most reusable contribution — any Drupal site can use it
