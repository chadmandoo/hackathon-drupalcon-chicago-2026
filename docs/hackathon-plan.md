# Hackathon Plan: "Greenville Gazette"

## Concept

A fictitious community news site ("The Greenville Gazette") that demonstrates Claude managing a Drupal site via MCP with permission-tiered access. Drupal acts as the governor -- the agent can only do what its OAuth scope allows.

## Site Structure

### Content Types

| Type | Fields | Purpose |
|------|--------|---------|
| **Article** | Title, Body, Image, Category (term ref), Author, Published date | News stories |
| **Event** | Title, Body, Date/Time, Location, Image | Community calendar |
| **Page** | Title, Body, Image | Static pages (About, Contact) |

### Taxonomy

- **Category**: Local News, Sports, Arts & Culture, Opinion, Community, Business

### Roles & Permissions

| Role | Drupal Permissions | Purpose |
|------|-------------------|---------|
| `viewer` | View published content | Public/read-only access |
| `editor` | View + create/edit own articles & events | Editorial staff |
| `site_admin` | View + create/edit any content + administer users | Site management |

### Users (Seed Data)

| Username | Role | Purpose |
|----------|------|---------|
| `admin` | Administrator | Drupal super admin |
| `sarah_editor` | editor | Reporter/journalist |
| `mike_editor` | editor | Sports reporter |
| `jane_admin` | site_admin | Managing editor |

## MCP Tool Tiers (OAuth Scope Mapping)

### Scope: `mcp:read`

Read-only tools. Safe for any authenticated user.

| Tool | Source Module | What It Does |
|------|-------------|--------------|
| `entity_list` | tool_content | List/search content |
| `entity_load_by_id` | tool_content | Load a specific entity |
| `entity_field_values` | tool_content | Get field values |
| `entity_field_value_definitions` | tool_content | See what fields an entity has |
| `entity_load_by_property` | tool_content | Find content by field values |
| `entity_type_list` | tool_entity | List all entity types |
| `entity_bundle_list` | tool_entity | List content types/bundles |
| `entity_bundle_definition` | tool_entity | Get bundle details |
| `entity_bundle_field_definitions` | tool_entity | See fields on a bundle |
| `system_status` | tool_system | Site health/status |
| `whos_online` | drupalcon_tools | Active users |

### Scope: `mcp:write`

Content creation/editing. For editorial roles.

| Tool | Source Module | What It Does |
|------|-------------|--------------|
| `entity_stub` | tool_content | Scaffold a new entity |
| `entity_save` | tool_content | Create or update content |
| `field_set_value` | tool_content | Set a field value |
| `entity_revision_add` | tool_content | Add a new revision |
| `log_message` | tool_system | Write to Drupal watchdog |

### Scope: `mcp:admin`

Administrative operations. For site admins only.

| Tool | Source Module | What It Does |
|------|-------------|--------------|
| `user_block` | tool_user | Block a user account |
| `user_unblock` | tool_user | Unblock a user account |
| `user_add_role` | tool_user | Assign a role to a user |
| `user_remove_role` | tool_user | Remove a role from a user |
| `entity_bundle_add` | tool_entity | Create a new content type |
| `entity_bundle_update` | tool_entity | Update a content type |
| `entity_bundle_delete` | tool_entity | Delete a content type |
| `field_add` | tool_entity | Add a field to a bundle |
| `field_update` | tool_entity | Update field settings |
| `field_delete` | tool_entity | Remove a field |
| `field_storage_add` | tool_entity | Add field storage |
| `field_storage_update` | tool_entity | Update field storage |
| `field_storage_delete` | tool_entity | Delete field storage |
| `send_email` | tool_system | Send email notifications |
| `entity_delete` | tool_content | Delete content |

## Demo Scenarios

### Scenario 1: "Editor Claude" (mcp:read + mcp:write scopes)
1. Ask Claude "What articles do we have about local news?"
2. Claude uses `entity_list` to search -- shows read access works
3. Ask Claude to "Write a new article about the Greenville Farmers Market opening this Saturday"
4. Claude uses `entity_stub` + `field_set_value` + `entity_save` to create the article
5. Ask Claude to "Block the user mike_editor" -- gets denied (no mcp:admin scope)

### Scenario 2: "Admin Claude" (mcp:read + mcp:write + mcp:admin scopes)
1. Ask Claude to "Check the site status"
2. Claude uses `system_status` -- shows system health
3. Ask Claude to "Add a new role called 'photographer' and assign it to sarah_editor"
4. Claude uses `user_add_role` -- demonstrates admin capability
5. Ask Claude to "Create a new content type called 'Photo Gallery' with title, body, and image fields"
6. Claude uses `entity_bundle_add` + `field_add` -- demonstrates structural changes

### Scenario 3: Permission Boundary Demo
1. Connect with mcp:read only
2. Try to create content -- denied
3. Show the Drupal watchdog log showing the denied access
4. Connect with mcp:write -- now it works
5. Drupal audit trail shows exactly what the agent did and when

## Seed Content (Sample Articles)

1. "Greenville Farmers Market Returns for Spring Season" - Local News
2. "Wildcats Take State Championship in Overtime Thriller" - Sports
3. "New Mural Unveiled at Downtown Arts Center" - Arts & Culture
4. "City Council Approves New Bike Lane on Main Street" - Local News
5. "Annual Jazz Festival Lineup Announced" - Arts & Culture
6. "Local Bakery Celebrates 50 Years in Business" - Business
7. "Opinion: Why Our Parks Need More Funding" - Opinion
8. "Community Clean-up Day Set for April 5th" - Community
9. "High School Robotics Team Heads to Nationals" - Community
10. "Restaurant Week Returns with 20 Participating Venues" - Business

## Frontend Theme

TBD - Need a clean, newspaper/magazine-style theme.

## Submission Checklist

- [ ] Site with content and users
- [ ] MCP tools enabled and scoped
- [ ] OAuth scopes created (mcp:read, mcp:write, mcp:admin)
- [ ] Tools mapped to scopes in MCP tool configs
- [ ] Demo working end-to-end
- [ ] README (hackathon template)
- [ ] Agent Run Log
- [ ] Agent Experience Report
- [ ] AGENTS.md (AX artifact)
