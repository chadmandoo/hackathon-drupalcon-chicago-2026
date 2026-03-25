# Contrib Module Patches

All patches are in the `/patches` directory.

## mcp_server--instructions-properties-delete-tools-alter.patch

**Applies to:** `drupal/mcp_server` (1.x-dev)
**Files modified:** 4

### Changes

#### 1. Fix empty properties JSON serialization (bug fix)
**File:** `src/Plugin/Tool/ToolApi.php`

Tools with no input parameters (like `editorial_dashboard`, `entity_type_list`, `system_status`) serialized their `properties` field as `[]` (JSON array) instead of `{}` (JSON object). This broke Claude.ai's MCP connector which validates against JSON Schema spec — `properties` must be an object.

**Fix:** `empty($properties) ? new \stdClass() : $properties`

#### 2. Add server instructions support (feature)
**File:** `src/McpServerFactory.php`

The MCP spec's `initialize` response supports an `instructions` field — free-text that gets injected into the AI's system prompt on connection. The SDK supports it (`$builder->setInstructions()`), but the Drupal module didn't expose it.

**Fix:** Reads `instructions` from `mcp_server.settings` config and passes it to the builder. Set via:
```bash
drush config:set mcp_server.settings instructions "Your instructions here" -y
```

#### 3. Add DELETE method to routing (spec compliance)
**File:** `mcp_server.routing.yml`

The MCP Streamable HTTP spec defines DELETE for session cleanup. Claude.ai sends DELETE requests when disconnecting. Without this, Drupal returns a 405 with a full HTML error page (~112KB), which confuses MCP clients.

**Fix:** Added `DELETE` to the allowed methods: `[GET, POST, DELETE]`

#### 4. Remove `final` from McpBridgeService + add alter hook (extensibility)
**File:** `src/McpBridgeService.php`

- Removed `final` keyword so the service can be extended/decorated
- Added `hook_mcp_server_enabled_tools_alter(&$tools)` call after tools are collected

This allows any module to filter, modify, or add MCP tools at discovery time. Our module uses it for permission-based tool filtering. The alter hook follows Drupal's standard `hook_*_alter` pattern.

### Applying the Patch

```bash
cd web/modules/contrib/mcp_server
git apply ../../../../patches/mcp_server--instructions-properties-delete-tools-alter.patch
```

Or via composer.json with `cweagans/composer-patches`:
```json
{
  "extra": {
    "patches": {
      "drupal/mcp_server": {
        "Instructions, properties fix, DELETE, tools alter hook": "patches/mcp_server--instructions-properties-delete-tools-alter.patch"
      }
    }
  }
}
```

## simple_oauth_21 — Client Registration Token Lifetime

**Not a patch file** — direct edit to `modules/simple_oauth_client_registration/src/Service/ClientRegistrationService.php`

Changed default access token lifetime for dynamically registered clients from 300s (5 minutes) to 86400s (24 hours). Without this, OAuth tokens expire almost immediately after Claude.ai authorizes.

**Line 91:** `'access_token_expiration' => 300` → `'access_token_expiration' => 86400`
**Line 92:** `'refresh_token_expiration' => 1209600` → `'refresh_token_expiration' => 604800`
