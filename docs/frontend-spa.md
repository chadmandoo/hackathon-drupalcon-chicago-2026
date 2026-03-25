# Frontend SPA

## Overview

The Prompt Post frontend is a decoupled React SPA served at `thepromptpost.chadpeppers.dev`. It fetches content from Drupal via custom API endpoints proxied through Caddy.

**Tech stack:** React 19, Vite 7, Tailwind CSS 4, TanStack Query, Wouter (routing), Framer Motion, date-fns.

**Design:** Newspaper/broadsheet aesthetic with serif headlines, uppercase section headers, and a monochrome palette.

## Architecture

```
thepromptpost.chadpeppers.dev
├── / (Home)           → Featured article + latest dispatches + trending sidebar
├── /news              → News articles (filtered by category)
├── /opinion           → Opinion articles (filtered by category)
├── /jobs              → Classified job listings
├── /puzzles           → Weekly Sudoku puzzle
└── /article/:id       → Article detail page (full body)
```

All routes serve the same `index.html` (SPA). Caddy's `try_files` handles this.

## API Consumption

The SPA fetches from these Drupal endpoints (proxied by Caddy):

| SPA calls | Drupal endpoint | Module |
|-----------|----------------|--------|
| `/api/articles` | `prompt_post_news` NewsApiController::articles | prompt_post_news |
| `/api/articles?category=news` | Same, filtered | prompt_post_news |
| `/api/articles/featured` | NewsApiController::featured | prompt_post_news |
| `/api/articles/{nid}` | NewsApiController::detail (HTML body) | prompt_post_news |
| `/api/jobs` | JobsApiController::list | prompt_post_jobs |
| `/api/puzzles/current` | PuzzleApiController::current | prompt_post_puzzles |
| `/api/healthz` | NewsApiController::health | prompt_post_news |

## Build & Deploy

Source lives at `/home/cpeppers/prompt_post/artifacts/prompt-post/`. Build copy at `/tmp/prompt-post-build/`.

```bash
cd /tmp/prompt-post-build
npx vite build
sudo cp -r dist/* /srv/prompt-post/
```

Static files served from `/srv/prompt-post/`.

### Key modifications from original Replit app:
- Replaced `@workspace/api-client-react` with local `src/lib/api.ts` that fetches from `/api/*`
- Simplified `vite.config.ts` (removed Replit plugins, PORT/BASE_PATH requirements)
- Resolved `catalog:` version references from pnpm workspace
- Added `/article/:id` route and `ArticleDetail.tsx` page component
- Updated `ArticleCard.tsx` links to point to `/article/{id}` instead of `/news`

## Caddy Configuration

```
thepromptpost.chadpeppers.dev, thepromptpost.chadpeppers.com {
    handle /api/* {
        reverse_proxy 127.0.0.1:8888 {
            header_up Host drupalcon2026.chadpeppers.dev
            header_up X-Forwarded-Proto https
        }
    }
    handle {
        root * /srv/prompt-post
        try_files {path} /index.html
        file_server
    }
}
```

The `handle` directive ordering ensures `/api/*` is proxied to Drupal before the SPA's `try_files` catches everything.

## Content Sections

### News
Articles with categories: AI & Machine Learning, Robotics, Prompt Engineering, Industry

### Opinion
Articles with categories: Ethics & Policy, Culture & Satire

### Jobs
10 hardcoded satirical AI-industry listings returned by `prompt_post_jobs`

### Puzzles
Sudoku component with puzzle data from `/api/puzzles/current`. Currently uses hardcoded fallback puzzles in the React component; API integration ready.

## Real-time Updates

When Claude creates or publishes content via MCP tools, the changes are immediately visible on the SPA because:
1. MCP tool modifies the Drupal database
2. SPA API endpoints query the database directly (no cache layer)
3. Browser refresh shows the new content

TanStack Query's `refetchOnWindowFocus: false` means manual refresh is needed to see changes (acceptable for demo).
