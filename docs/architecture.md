# Architecture Overview

## System Diagram

```
┌──────────────────────────────────────────────────────────────────┐
│  Claude.ai / Claude Desktop / Any MCP Client                    │
└──────────────┬───────────────────────────────────────────────────┘
               │
               │  MCP Protocol (Streamable HTTP over HTTPS)
               │  POST https://drupalcon2026.chadpeppers.dev/_mcp
               │
               │  Auth: OAuth 2.1 + PKCE + Dynamic Client Registration
               │
┌──────────────▼───────────────────────────────────────────────────┐
│  Caddy Reverse Proxy (port 443)                                  │
│                                                                  │
│  drupalcon2026.chadpeppers.dev → 127.0.0.1:8888 (Drupal)        │
│  thepromptpost.chadpeppers.dev → static SPA + /api/* → Drupal   │
└──────────────┬───────────────────────────────────────────────────┘
               │
┌──────────────▼───────────────────────────────────────────────────┐
│  PHP 8.3 Built-in Server (127.0.0.1:8888)                        │
│                                                                  │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │  Drupal 11.3                                               │  │
│  │                                                            │  │
│  │  ┌──────────────┐  ┌───────────────┐  ┌───────────────┐   │  │
│  │  │ MCP Server   │  │ Simple OAuth  │  │ Content       │   │  │
│  │  │ Module       │  │ + OAuth 2.1   │  │ Moderation    │   │  │
│  │  │              │  │ + PKCE        │  │               │   │  │
│  │  │ /_mcp        │  │ + DCR (7591)  │  │ Editorial     │   │  │
│  │  │ endpoint     │  │ + Metadata    │  │ Workflow      │   │  │
│  │  └──────┬───────┘  └───────┬───────┘  └───────┬───────┘   │  │
│  │         │                  │                   │           │  │
│  │         ▼                  ▼                   ▼           │  │
│  │  ┌─────────────────────────────────────────────────────┐   │  │
│  │  │  Permission System + hook_mcp_server_enabled_       │   │  │
│  │  │  tools_alter() → Tools filtered by role at          │   │  │
│  │  │  DISCOVERY time, not just execution                 │   │  │
│  │  └─────────────────────────────────────────────────────┘   │  │
│  │                                                            │  │
│  │  ┌──────────────────────────────────────────────────┐      │  │
│  │  │  Custom Modules (Prompt Post)                    │      │  │
│  │  │                                                  │      │  │
│  │  │  prompt_post_news     12 MCP tools + API         │      │  │
│  │  │  prompt_post_jobs     Jobs API                   │      │  │
│  │  │  prompt_post_puzzles  Sudoku gen + MCP tool      │      │  │
│  │  │  prompt_post_opinion  Opinion section marker     │      │  │
│  │  └──────────────────────────────────────────────────┘      │  │
│  │                                                            │  │
│  │  ┌──────────────────────────────────────────────────┐      │  │
│  │  │  Contrib Tool Modules                            │      │  │
│  │  │  tool_content (10) + tool_entity (2)             │      │  │
│  │  │  tool_system (4) + tool_user (4)                 │      │  │
│  │  └──────────────────────────────────────────────────┘      │  │
│  └────────────────────────────────────────────────────────────┘  │
│                                                                  │
│  PostgreSQL (drupmcp database)                                   │
└──────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│  React SPA (thepromptpost.chadpeppers.dev)                       │
│  Vite + React + Tailwind CSS + TanStack Query                    │
│  Fetches from /api/articles, /api/jobs, /api/puzzles/current     │
│  Caddy proxies /api/* → Drupal backend                           │
└──────────────────────────────────────────────────────────────────┘
```

## Request Flow: MCP Tool Call

1. Claude.ai sends POST to `/_mcp` with `Authorization: Bearer <token>`
2. Caddy proxies to PHP built-in server at 127.0.0.1:8888
3. Drupal's OAuth middleware validates the token → resolves to a user
4. MCP Server module routes the JSON-RPC request
5. For `tools/list`: `McpBridgeService::getEnabledTools()` builds the list, then `hook_mcp_server_enabled_tools_alter()` filters by the user's permissions
6. For `tools/call`: the tool plugin is instantiated, `checkAccess()` verifies permissions, `doExecute()` runs the logic
7. Response flows back as JSON-RPC

## Request Flow: SPA Frontend

1. Browser loads `thepromptpost.chadpeppers.dev` → Caddy serves static React build
2. SPA makes fetch to `/api/articles` → Caddy proxies to Drupal
3. `NewsApiController::articles()` queries published articles → returns JSON
4. React renders the newspaper layout

## Key Design Decisions

- **PHP built-in server** instead of Apache/Nginx: simplicity for hackathon demo
- **Caddy** for TLS termination: automatic HTTPS with Let's Encrypt
- **Decoupled SPA**: shows Drupal serving both MCP (agents) and JSON API (frontends)
- **PostgreSQL**: pre-existing on the server
- **Service decorator via alter hook** instead of modifying contrib: proper Drupal pattern
