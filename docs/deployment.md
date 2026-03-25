# Deployment & Infrastructure

## Server

- **Host:** DigitalOcean droplet at 64.23.186.120
- **OS:** Ubuntu 24.04 (Linux 6.8.0-106-generic)
- **Domain registrar:** GoDaddy (chadpeppers.dev)

## Services

| Service | Port | Purpose |
|---------|------|---------|
| Caddy | 80, 443 | Reverse proxy + TLS |
| PHP 8.3 built-in server | 8888 | Drupal |
| PostgreSQL | 5432 | Database |

## Domains

| Domain | Points To | Purpose |
|--------|-----------|---------|
| `drupalcon2026.chadpeppers.dev` | Drupal backend | Admin, MCP endpoint, OAuth |
| `thepromptpost.chadpeppers.dev` | SPA frontend | Public newspaper site |
| `thepromptpost.chadpeppers.com` | SPA frontend | Alternate domain |

## File Locations

| Path | Contents |
|------|----------|
| `/srv/drupmcp/` | Drupal project root |
| `/srv/drupmcp/web/` | Drupal webroot |
| `/srv/drupmcp/web/modules/custom/` | Our 4 custom modules |
| `/srv/drupmcp/oauth-keys/` | OAuth private/public keys |
| `/srv/drupmcp/patches/` | Contrib patches |
| `/srv/drupmcp/docs/` | This documentation |
| `/srv/prompt-post/` | SPA static build (deployed) |
| `/home/cpeppers/prompt_post/` | SPA source (from Replit) |
| `/tmp/prompt-post-build/` | SPA build workspace |
| `/etc/caddy/Caddyfile` | Caddy configuration |
| `/srv/drupmcp/web/sites/default/services.yml` | CORS configuration |

## Database

- **Engine:** PostgreSQL
- **Database:** `drupmcp`
- **User:** `drupal`
- **Host:** 127.0.0.1:5432

## PHP Server

Started as webuser:
```bash
/usr/bin/php -S 127.0.0.1:8888 -t /srv/drupmcp/web /srv/drupmcp/web/.ht.router.php
```

## CORS Configuration

`web/sites/default/services.yml`:
```yaml
parameters:
  cors.config:
    enabled: true
    allowedHeaders: ['Content-Type', 'Authorization', 'X-CSRF-Token', 'Accept', 'Origin', 'Mcp-Session-Id', 'Mcp-Protocol-Version']
    allowedMethods: ['GET', 'POST', 'PATCH', 'DELETE', 'OPTIONS']
    allowedOrigins: ['https://thepromptpost.chadpeppers.dev', 'https://thepromptpost.chadpeppers.com', 'http://localhost:5173', 'https://prompt-post-daily.replit.app']
    exposedHeaders: ['Content-Type', 'Mcp-Session-Id']
    maxAge: 1000
    supportsCredentials: true
```

## OAuth Configuration

- **Keys:** `/srv/drupmcp/oauth-keys/private.key` and `public.key`
- **Key config:** `simple_oauth.settings` points to these paths
- **Default token lifetime:** 86400s (24h access), 604800s (7d refresh)
- **Dynamic Client Registration:** Enabled at `/oauth/register`
- **Registration endpoint:** Explicitly set to `https://drupalcon2026.chadpeppers.dev/oauth/register` in `simple_oauth_server_metadata.settings` (required because PHP built-in server behind Caddy doesn't know the external hostname)
- **PKCE:** Mandatory (S256 and plain)

## Common Operations

```bash
# Clear Drupal cache
vendor/bin/drush cr

# Watch Drupal logs
vendor/bin/drush watchdog:show --count=20

# Generate admin login link
vendor/bin/drush uli --uri=https://drupalcon2026.chadpeppers.dev

# Reload Caddy after config changes
sudo caddy reload --config /etc/caddy/Caddyfile

# Rebuild SPA
cd /tmp/prompt-post-build && npx vite build && sudo cp -r dist/* /srv/prompt-post/

# Check OAuth tokens
vendor/bin/drush sql:query "SELECT t.id, t.bundle, t.expire, u.name FROM oauth2_token t LEFT JOIN users_field_data u ON t.auth_user_id = u.uid WHERE t.bundle = 'access_token' ORDER BY t.expire DESC;"
```
