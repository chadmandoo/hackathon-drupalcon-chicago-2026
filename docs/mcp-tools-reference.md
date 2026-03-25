# MCP Tools Reference

32 tools total: 12 custom (Prompt Post) + 20 contrib (tool module).

## Custom Tools — Prompt Post

### Reading Tools

| Tool | Module | Permission | Description |
|------|--------|------------|-------------|
| `site_briefing` | prompt_post_news | `access content` | Full site orientation: your role, content stats, available tools, workflow, suggested first steps. **Run this first.** |
| `editorial_dashboard` | prompt_post_news | `access content` | Content counts by type/state, articles by category, pending reviews, author activity |
| `content_search` | prompt_post_news | `access content` | Full-text search across titles and body. Filter by content type and category. |
| `content_moderator` | prompt_post_news | `access content` | View content by moderation state. Shows workflow transitions available. |
| `site_analytics` | prompt_post_news | `access content` | Publishing trends, category breakdown, author stats, upcoming events |
| `whos_online` | prompt_post_news | `access content` | Currently active users with roles and last access times |
| `module_help` | prompt_post_news | `access content` | Read hook_help documentation from any installed module. Actions: `list`, `get` |
| `recent_activity` | prompt_post_news | `access site reports` | Activity timeline from watchdog: content changes, user logins, system events |

### Writing Tools

| Tool | Module | Permission | Description |
|------|--------|------------|-------------|
| `content_publisher` | prompt_post_news | Per-transition | Change moderation state. Actions: `submit_for_review`, `publish`, `send_back`, `archive`, `restore_to_draft`. Each checks the user's specific workflow transition permission. |
| `breaking_news` | prompt_post_news | `edit any article content` | Flag/unflag article as breaking news. Auto-publishes and promotes to front page. |
| `taxonomy_manager` | prompt_post_news | `access content` (list) / `administer taxonomy` (write) | List categories with article counts. Create, rename, delete categories. |
| `puzzle_manager` | prompt_post_puzzles | `access content` (get) / `administer site configuration` (write) | Get current puzzle, generate new ones, set difficulty. |

## Contrib Tools — tool module

### Content CRUD (tool_content)

| Tool | Permission | Description |
|------|------------|-------------|
| `entity_list` | `access content` | List/filter/sort content entities |
| `entity_load_by_id` | per-entity | Load entity by type and ID |
| `entity_load_by_property` | `access content` | Find entities by field value match |
| `entity_field_values` | per-entity | Get field values from an entity |
| `entity_field_value_definitions` | none | Get field schema for a bundle |
| `entity_stub` | per-entity create | Create an unsaved entity scaffold |
| `entity_save` | per-entity create/edit | Save an entity |
| `field_set_value` | per-entity edit | Set a field value on an entity |
| `entity_revision_add` | per-entity edit | Add a new revision (use before editing existing content) |
| `entity_delete` | `administer nodes` | Delete an entity |

### Structure (tool_entity)

| Tool | Permission | Description |
|------|------------|-------------|
| `entity_type_list` | none | List all content entity types and bundles |
| `entity_bundle_list` | `administer site configuration` | List bundles for a given entity type |

### System (tool_system)

| Tool | Permission | Description |
|------|------------|-------------|
| `system_status` | `administer site configuration` | Drupal system status report |
| `send_email` | `use send_email tool` | Send an email |
| `display_message` | `use display_message tool` | Display a Drupal messenger message |
| `log_message` | `use log_message tool` | Write to watchdog log |

### User Management (tool_user)

| Tool | Permission | Description |
|------|------------|-------------|
| `user_block` | `administer users` | Block a user account |
| `user_unblock` | `administer users` | Unblock a user account |
| `user_add_role` | `administer users` | Assign a role to a user |
| `user_remove_role` | `administer users` | Remove a role from a user |

## Tool Visibility by Role

Tools are filtered at **discovery time** via `hook_mcp_server_enabled_tools_alter()`. Agents only see what they can use.

| Role | Tools Visible | Hidden |
|------|---------------|--------|
| **Writer** | 22 | user_*, system_status, entity_bundle_list, send_email, recent_activity, entity_delete, breaking_news |
| **Reviewer** | 23 | user_*, system_status, entity_bundle_list, send_email, recent_activity, entity_delete |
| **Admin** | 32 | (none) |

## content_publisher Transition Matrix

| Action | From States | Required Permission | Writer | Reviewer | Admin |
|--------|-------------|---------------------|--------|----------|-------|
| `submit_for_review` | draft | `use editorial transition submit_for_review` | YES | YES | YES |
| `publish` | draft, review | `use editorial transition publish` | **NO** | YES | YES |
| `send_back` | review | `use editorial transition send_back` | **NO** | YES | YES |
| `archive` | published | `use editorial transition archive` | **NO** | **NO** | YES |
| `restore_to_draft` | archived | `use editorial transition archived_draft` | **NO** | **NO** | YES |
