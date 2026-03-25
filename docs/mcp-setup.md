# Drupal MCP Server Setup Guide

This project is an entry for the [Acquia "Permission to Run" AX Hackathon](https://github.com/acquia/hackathon-drupalcon-chicago-2026) at DrupalCon Chicago 2026. It demonstrates Claude managing a Drupal site via MCP (Model Context Protocol) with full OAuth 2.1 authentication -- Drupal acts as the governor for agent actions.

This documents how to set up the Drupal MCP Server module with OAuth 2.1 so that Claude (Desktop/Web) can connect and use Drupal tools.

## Architecture

- **Drupal 11** site with MCP Server module (`drupal/mcp_server`)
- **OAuth 2.1** via `simple_oauth` + `simple_oauth_21` modules
- **Dynamic Client Registration** (RFC 7591) allows Claude to register itself automatically
- **MCP endpoint**: `/_mcp` (Streamable HTTP transport)

## Required Modules

These modules must be installed and enabled:

```
drupal/mcp_server (1.x-dev)
```

The MCP Server module pulls in its dependencies:
- `tool` - Tool plugin system
- `simple_oauth` - Core OAuth 2.0 server
- `simple_oauth_21` - OAuth 2.1 compliance umbrella
- `consumers` - OAuth consumer/client entities

Additionally, these `simple_oauth_21` sub-modules must be enabled:

| Module | Purpose |
|--------|---------|
| `simple_oauth_pkce` | PKCE support (required by OAuth 2.1) |
| `simple_oauth_server_metadata` | RFC 8414 server metadata at `/.well-known/oauth-authorization-server` |
| `simple_oauth_client_registration` | RFC 7591 Dynamic Client Registration at `/oauth/register` |

Enable them:
```bash
drush en simple_oauth_pkce simple_oauth_server_metadata simple_oauth_client_registration -y
```

## Setup Steps

### 1. Generate OAuth Keys

```bash
mkdir -p oauth-keys
openssl genrsa -out oauth-keys/private.key 2048
openssl rsa -in oauth-keys/private.key -pubout -out oauth-keys/public.key
chmod 600 oauth-keys/private.key
```

### 2. Configure Simple OAuth Key Paths

Go to `/admin/config/people/simple_oauth` or use drush:

```bash
drush config:set simple_oauth.settings public_key /path/to/oauth-keys/public.key -y
drush config:set simple_oauth.settings private_key /path/to/oauth-keys/private.key -y
```

### 3. Create an OAuth Scope for MCP

Go to `/admin/config/people/simple_oauth/oauth2_scope/static/add` and create a scope:

- **Name/ID**: `mcp`
- **Label**: `MCP Access`
- **Description**: `Access MCP tools`
- **Grant types**: Enable both `authorization_code` and `client_credentials`
- **Granularity**: Permission
- **Permission**: `access content` (or a more restrictive custom permission)

### 4. Set the Registration Endpoint URL

If your site runs behind a reverse proxy (e.g., Caddy, Nginx), Drupal may not know the correct external URL. Explicitly set it:

```bash
drush config:set simple_oauth_server_metadata.settings registration_endpoint "https://your-domain.com/oauth/register" -y
```

Then clear caches:
```bash
drush cr
```

### 5. Configure MCP Tools

Go to `/admin/config/services/mcp-server/tools` to enable/configure which tools are exposed via MCP. Each tool can be set to:

- **Authentication mode**: `required` (needs OAuth token) or `optional`
- **Scopes**: Which OAuth scopes grant access to the tool

### 6. Verify the Setup

Test that the metadata endpoints respond correctly:

```bash
# Should return OAuth server metadata with registration_endpoint
curl https://your-domain.com/.well-known/oauth-authorization-server

# Should return protected resource metadata
curl https://your-domain.com/.well-known/oauth-protected-resource

# Should return MCP server info
curl -X POST https://your-domain.com/_mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"initialize","id":1,"params":{"protocolVersion":"2025-03-26","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}'
```

## Connecting from Claude

### Claude Desktop / Claude.ai

1. Go to Settings > MCP Servers (or Integrations)
2. Add a new MCP server
3. Enter the URL: `https://your-domain.com/_mcp`
4. Claude will automatically:
   - Discover OAuth metadata via `/.well-known/oauth-protected-resource`
   - Register itself as a client via `/oauth/register` (Dynamic Client Registration)
   - Redirect you to Drupal to log in and authorize
5. After authorization, tools are available immediately

That's it -- just the URL. Claude handles the entire OAuth flow automatically.

## Troubleshooting

### "Access token has been revoked"
The OAuth tokens have expired or been invalidated (e.g., after regenerating OAuth keys). Disconnect and reconnect the MCP server in Claude to re-authorize.

### Connection spins / never completes auth
Check that `registration_endpoint` in the OAuth metadata points to the correct external URL (not `http://default`). Fix with:
```bash
drush config:set simple_oauth_server_metadata.settings registration_endpoint "https://your-domain.com/oauth/register" -y
drush cr
```

### Tool calls return "Authentication required"
The tool's `authentication_mode` is set to `required` but no valid OAuth token was sent. Re-authorize from Claude.

### Check logs
```bash
drush watchdog:show --count=30
drush watchdog:show --type=simple_oauth --count=10
drush watchdog:show --type=mcp_server --count=10
```

## How It Works (The OAuth + MCP Flow)

```
Claude                          Drupal
  |                               |
  |-- POST /_mcp (initialize) --> |  (anonymous, gets session ID)
  |<-- 200 + session ID ---------|
  |                               |
  |-- POST /_mcp (tools/call) --> |  (no token)
  |<-- 401 Auth Required --------|
  |                               |
  |-- GET /.well-known/           |
  |   oauth-protected-resource -> |  (discovers auth server)
  |<-- { authorization_servers }--|
  |                               |
  |-- GET /.well-known/           |
  |   oauth-authorization-server->|  (discovers endpoints + registration_endpoint)
  |<-- { endpoints, scopes... } --|
  |                               |
  |-- POST /oauth/register -----> |  (Dynamic Client Registration, RFC 7591)
  |<-- { client_id } ------------|
  |                               |
  |-- GET /oauth/authorize -----> |  (authorization code + PKCE)
  |   [User logs in & approves]   |
  |<-- redirect with auth code ---|
  |                               |
  |-- POST /oauth/token --------> |  (exchange code for access token)
  |<-- { access_token } ---------|
  |                               |
  |-- POST /_mcp (tools/call)     |
  |   Authorization: Bearer xxx ->|  (authenticated tool call)
  |<-- tool result ---------------|
```

Drupal remains the governor: all tool calls are subject to the authenticated user's permissions, OAuth scopes, and Drupal's access control system.

## This Deployment

- **URL**: `https://drupalcon2026.chadpeppers.dev`
- **MCP endpoint**: `https://drupalcon2026.chadpeppers.dev/_mcp`
- **Web server**: Caddy reverse proxy -> PHP built-in server on `127.0.0.1:8888`
- **Database**: PostgreSQL (`drupmcp` database)
- **OAuth keys**: `/srv/drupmcp/oauth-keys/`

## Hackathon Context

This is an entry for the **Acquia "Permission to Run" AX Hackathon** at DrupalCon Chicago 2026.

**What we're demonstrating**: Claude connected to Drupal via MCP, able to manage the site and query information -- with Drupal enforcing permissions, authentication, and access control on every action.

**Key hackathon alignment**:
- **Agent Success**: Claude performs real site management tasks via MCP tools
- **AX Quality**: OAuth 2.1 + PKCE + Dynamic Client Registration provide secure, standards-based agent access
- **Drupal-in-the-loop**: All agent actions go through Drupal's permission system and OAuth scopes
- **Openness**: Uses open standards (MCP, OAuth 2.1, RFC 7591/8414/9728) -- works with any MCP client
- **Impact**: Reusable pattern for any Drupal site wanting AI agent integration
