# Agent Experience Report

## What worked

### MCP connection from Claude.ai
Once properly configured, the MCP connection from Claude.ai was remarkably smooth. You paste a URL, Claude discovers OAuth automatically via RFC 9728 (Protected Resource Metadata) and RFC 8414 (Authorization Server Metadata), registers itself via RFC 7591 (Dynamic Client Registration), walks you through login, and connects. The entire OAuth dance is invisible to the user — they just paste a URL and log in. This is the UX the industry needs.

### Drupal's permission system as AI governance
Drupal's existing permission and role system mapped perfectly to AI agent governance. We didn't need to invent a new access control system — we reused the same permissions, content moderation workflow, and entity access checks that govern human users. The `content_publisher` tool returns the exact missing permission and the user's current roles when access is denied, which means Claude can explain to the user WHY it can't do something, not just that it failed.

### Tool plugin architecture
The Tool module's plugin system (`#[Tool]` attribute, `ToolBase`, `checkAccess()`, `doExecute()`) made it straightforward to build custom MCP tools. Each tool is a self-contained PHP class with clear input definitions, access checks, and execution logic. We built 12 custom tools in roughly 2 hours.

### The `instructions` field in MCP initialize
The MCP spec's server instructions feature (injected into the agent's system prompt on connection) was highly effective. Claude immediately knew what the site was, what tools were available, and what to do first — without the user having to explain anything.

### Decoupled frontend with live updates
Because the SPA fetches from Drupal's API on every request, changes made by Claude via MCP appeared on the live site immediately. "Create an article via MCP, refresh the site, see it on the front page" — that demo loop is powerful and immediate.

### Claude Code for building
Claude Code (Opus 4.6 with 1M context) was extraordinarily effective at building this project. It maintained context across the entire session, remembered every decision, and could modify files across the codebase without losing track of dependencies. The entire project — 4 Drupal modules, 12 tools, 15 full articles, a React SPA integration, OAuth configuration, patches, and documentation — was built in a single session.

## What was confusing

### OAuth setup complexity
Getting OAuth 2.1 + PKCE + Dynamic Client Registration + Server Metadata all working together required debugging at multiple layers. The biggest gotcha: when Drupal runs behind a reverse proxy (Caddy → PHP built-in server), the `registration_endpoint` in OAuth metadata resolves to `http://default` instead of the real domain. This required explicitly setting the URL in config. A developer who doesn't understand both OAuth and reverse proxy headers would have a very hard time with this.

### Token lifetime defaults
The Dynamic Client Registration module (`simple_oauth_client_registration`) defaults to 300-second (5-minute) access tokens. This is far too short for MCP connections — by the time Claude.ai completes the handshake and you start a conversation, the token is nearly expired. The default should be at least 3600s (1 hour) or configurable via the admin UI. We changed it to 86400s.

### JSON Schema strictness
Claude.ai's MCP connector validates tool schemas strictly against JSON Schema. An empty PHP array `[]` serializes as a JSON array, but `properties` in JSON Schema must be an object `{}`. This bug in the MCP server module caused ALL tools to fail silently — Claude.ai saw the tools but couldn't register them. The error message ("Input should be a valid dictionary") was only visible in Claude.ai's internal connector logs, not surfaced to the user.

### `ExecutableResult` API
The Tool module's `ExecutableResult` class has `::success()` and `::failure()` static methods, but not `::error()`. This isn't documented anywhere obvious, and `::error()` is the intuitive name. We wrote 19 calls to `::error()` before discovering it didn't exist — only when Claude.ai tried to call a tool and got a PHP fatal.

### ChatGPT MCP limitations
ChatGPT Desktop does not support remote MCP servers — only local stdio-based servers. ChatGPT's web interface does support remote MCP, but the setup process is less polished than Claude.ai. This limits the "openness" claim somewhat — while MCP is an open standard, the client support varies significantly.

## What the agent could not do

### See Claude.ai's error messages
When Claude.ai's MCP connector encountered the JSON Schema validation error, the error was only visible in Claude.ai's UI on the user's screen. Claude Code (the agent building the server) couldn't see what Claude.ai (the agent connecting to the server) was experiencing. The human had to relay the error message back. This is inherent to the architecture — the builder agent and the user agent are separate — but it created a debugging gap.

### Test OAuth flow end-to-end from the server
The agent could verify every individual component (OAuth metadata, token endpoint, MCP initialize, tools/list) via curl, but couldn't simulate the full Claude.ai OAuth flow because that requires a browser, user interaction, and Claude.ai's internal token management. The human had to do the integration testing.

### Access the Tool module's source documentation
The `ExecutableResult::error()` vs `::failure()` confusion could have been avoided if the agent had immediately checked the source. The issue was that `::error()` is a reasonable guess that happens to be wrong, and the error only manifests at runtime when a tool is actually called.

### Modify `final` classes via decorator pattern
When we tried to filter tools at discovery time, the `McpBridgeService` class was `final`, preventing extension or decoration. We had to modify the contrib module to remove `final` and add an alter hook — a change that should be upstreamed but means our approach requires a patch.

## What would make the next run 10x faster

### 1. MCP Server module: Ship the alter hook
`hook_mcp_server_enabled_tools_alter()` should be in the module core. Every site that exposes MCP tools to agents needs to control which tools each role can see. Our patch does this — it should be merged.

### 2. MCP Server module: Ship server instructions support
The `instructions` config for the initialize response is a one-line addition that dramatically improves agent onboarding. Currently requires our patch.

### 3. Simple OAuth: Sane defaults for Dynamic Client Registration
300-second token lifetime is too short. Default should be 3600s minimum. The `registration_endpoint` URL should auto-detect correctly behind reverse proxies (use the request's Host header, not Drupal's internal base URL).

### 4. Tool module: Document ExecutableResult API prominently
Add a one-line note to the Tool module README: "Use `ExecutableResult::success()` and `ExecutableResult::failure()`. There is no `::error()` method."

### 5. MCP Server module: Fix empty properties serialization
The `properties: []` vs `properties: {}` bug breaks every MCP client that validates JSON Schema. One-line fix: `empty($properties) ? new \stdClass() : $properties`.

### 6. A "Drupal MCP Quick Start" guide
A single page that says: install these 5 modules, run these 3 drush commands, paste this URL in Claude.ai. Currently requires reading documentation across 4 different module READMEs and understanding OAuth 2.1 concepts.

### 7. Better error surfacing in Claude.ai's MCP connector
When tool registration fails due to schema issues, surface the error to the user in the conversation, not just in the connector's internal state. "Tool X failed to register because: {reason}" would have saved us an hour of debugging.

## Where Drupal acted as a useful governor

### Permission system
The core value proposition worked exactly as designed. Writers couldn't publish. Reviewers couldn't archive. The agent received clear, actionable error messages explaining the permission boundary. No special AI-specific access control was needed — Drupal's standard permissions governed everything.

### Content moderation workflow
The Editorial workflow (draft → review → published → archived) enforced review gates that prevented the agent from bypassing human review. Even with admin-level access, the content went through the correct state machine. Revisions were created for every state change with log messages attributing the change to the MCP agent.

### Entity access system
Contrib tools that operate on entities (save, edit, delete) checked per-entity access, meaning a Writer could edit their own articles but not others'. This granularity came free from Drupal's entity access system.

### Watchdog audit trail
Every action taken by the agent was logged in Drupal's watchdog, including the authenticated username, the action performed, and the result. The `recent_activity` tool exposes this — the agent can audit its own actions.

### Schema validation
Content types with required fields prevented the agent from creating incomplete content. Taxonomy term references were validated against the vocabulary. The content moderation system rejected invalid state transitions.

## Recommended follow-up issue(s)

### 1. [drupal/mcp_server] Add `hook_mcp_server_enabled_tools_alter()`
Allow modules to filter the tools list at discovery time based on the current user's permissions, OAuth scopes, or any other criteria. We have a working patch.

### 2. [drupal/mcp_server] Fix empty properties JSON serialization
`properties: []` breaks MCP clients expecting JSON Schema-compliant `properties: {}`. One-line fix included in our patch.

### 3. [drupal/mcp_server] Add server instructions from config
Expose the MCP spec's `instructions` field via `mcp_server.settings` config. Allows site admins to configure automatic agent onboarding text.

### 4. [drupal/simple_oauth_21] Increase default DCR token lifetime
Dynamic Client Registration should default to at least 3600s (1 hour) access tokens, not 300s (5 minutes). Current default causes tokens to expire before agents can use them.

### 5. [drupal.org docs] Create "Drupal MCP Quick Start" guide
A single-page guide covering the minimum setup: module installation, OAuth key generation, scope creation, and connecting from Claude.ai.
