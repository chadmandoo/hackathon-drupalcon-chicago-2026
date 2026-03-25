# Permissions & Editorial Workflow

## Roles

| Role | Machine Name | Users | Purpose |
|------|-------------|-------|---------|
| Writer | `editor` | sarah_editor, mike_editor | Create content, submit for review |
| Reviewer | `reviewer` | alex_reviewer | Review, publish, send back |
| Admin | `site_admin` | jane_admin | Full site control |

User passwords: Writers use `editor123`, Reviewer uses `reviewer123`, Admin uses `admin123`.

## Editorial Workflow

Uses Drupal's Content Moderation module with the "Editorial" workflow.

```
                    ┌─────────────┐
            ┌──────►│   Draft     │◄──────────────────┐
            │       └──────┬──────┘                    │
            │              │                           │
            │    Submit for Review                Send Back
            │    (Writer, Reviewer, Admin)   (Reviewer, Admin)
            │              │                           │
            │       ┌──────▼──────┐                    │
            │       │  In Review  │────────────────────┘
            │       └──────┬──────┘
            │              │
            │           Publish
            │       (Reviewer, Admin)
            │              │
            │       ┌──────▼──────┐
   Create   │       │  Published  │
   New Draft│       └──────┬──────┘
 (All roles)│              │
            │           Archive
            │         (Admin only)
            │              │
            │       ┌──────▼──────┐
            └───────│  Archived   │
                    └─────────────┘
                 Restore to Draft
                   (Admin only)
```

## Permission Matrix

### Workflow Transitions

| Transition | Writer | Reviewer | Admin |
|------------|--------|----------|-------|
| Create Draft | YES | YES | YES |
| Submit for Review | YES | YES | YES |
| Publish | **NO** | YES | YES |
| Send Back to Draft | **NO** | YES | YES |
| Archive | **NO** | **NO** | YES |
| Restore from Archive | **NO** | **NO** | YES |

### Content Operations

| Operation | Writer | Reviewer | Admin |
|-----------|--------|----------|-------|
| Create articles (own) | YES | YES | YES |
| Edit own articles | YES | YES | YES |
| Edit any article | **NO** | YES | YES |
| Delete any article | **NO** | **NO** | YES |
| View unpublished (own) | YES | YES | YES |
| View unpublished (any) | **NO** | YES | YES |

### Administrative

| Operation | Writer | Reviewer | Admin |
|-----------|--------|----------|-------|
| Administer users | **NO** | **NO** | YES |
| Administer site config | **NO** | **NO** | YES |
| Access site reports | **NO** | **NO** | YES |
| Administer taxonomy | **NO** | **NO** | YES |
| Send email tool | **NO** | **NO** | YES |

### MCP Tool Visibility

| Tool Category | Writer (22) | Reviewer (23) | Admin (32) |
|---------------|-------------|---------------|------------|
| Content reading | YES | YES | YES |
| Content writing | YES | YES | YES |
| Breaking news | **NO** | YES | YES |
| User management | **NO** | **NO** | YES |
| System admin | **NO** | **NO** | YES |
| Site reports | **NO** | **NO** | YES |

## How It Works at the MCP Level

1. **Connection:** Claude authenticates via OAuth 2.1. The token carries the user identity.
2. **Discovery:** `tools/list` returns only tools the user can access (filtered by `hook_mcp_server_enabled_tools_alter()`).
3. **Execution:** Each tool call checks permissions again via `checkAccess()`. The `content_publisher` tool additionally checks per-transition permissions and returns actionable error messages.
4. **Audit:** All state changes create Drupal revisions with log messages. Watchdog captures the full activity trail.

## Demo Scenarios

### Writer tries to publish (denied)
1. Connect as sarah_editor
2. Call `content_publisher` with `action: publish, nid: 28` (a draft article)
3. Response: "Access denied. You do not have permission to publish. Required permission: use editorial transition publish. Your roles: authenticated, editor."

### Reviewer publishes from review
1. Connect as alex_reviewer
2. Call `content_moderator` with `state: review` → sees articles pending review
3. Call `content_publisher` with `action: publish, nid: 30` → succeeds
4. Article appears on the SPA frontend immediately

### Admin archives old content
1. Connect as jane_admin
2. Call `content_publisher` with `action: archive, nid: 18` → succeeds
3. Article removed from SPA, retained in Drupal
