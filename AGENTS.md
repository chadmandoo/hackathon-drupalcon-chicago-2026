# AGENTS.md — The Prompt Post

## Purpose

This file provides guidance for any AI agent connecting to The Prompt Post Drupal site via MCP. Follow these instructions to work safely and effectively within Drupal's governance model.

## First steps

1. **Run `site_briefing` immediately.** This returns your role, available tools, content stats, and suggested actions. Your tool set depends on which user authorized the OAuth connection.
2. **Understand your role.** Writers can draft and submit for review. Reviewers can publish and send back. Admins have full control. Don't attempt actions outside your role — the system will deny them with an explanation.
3. **Use `editorial_dashboard`** to see the current content state before making changes.

## Core rules

### 1. Drupal is the governor
Every action you take goes through Drupal's permission system. The same rules that apply to human editors apply to you. There are no agent-specific backdoors or elevated permissions.

### 2. Respect the editorial workflow
Content follows: **draft → review → published → archived**.
- Create content as drafts
- Submit for review when ready
- Only publish if your role permits it
- Provide revision log messages explaining your changes

### 3. Prefer reviewable output
- Create drafts, not published content (unless explicitly asked)
- Use `entity_revision_add` before editing existing content
- Include descriptive revision log messages
- Use `content_publisher` with clear `revision_log` values

### 4. Show your work
- Use `log_message` to record significant actions
- Provide context in revision logs
- When creating articles, explain the editorial rationale

## Available tool categories

### Reading (safe, no side effects)
- `site_briefing` — Start here. Full orientation.
- `editorial_dashboard` — Content overview and stats
- `content_search` — Find articles by keyword
- `content_moderator` — View content by workflow state
- `site_analytics` — Trends and category breakdown
- `whos_online` — Active users
- `module_help` — Read any module's documentation
- `entity_list`, `entity_load_by_id`, `entity_field_values` — Content CRUD reads

### Writing (creates revisions, state changes)
- `content_publisher` — Workflow transitions (role-restricted)
- `breaking_news` — Flag articles as breaking (reviewer+ only)
- `taxonomy_manager` — Manage categories
- `entity_save`, `field_set_value`, `entity_stub` — Content CRUD writes
- `puzzle_manager` — Generate weekly Sudoku (admin only)

### Admin (restricted to site_admin role)
- `user_block`, `user_unblock`, `user_add_role`, `user_remove_role`
- `system_status`, `recent_activity`, `send_email`
- `entity_delete`, `entity_bundle_list`

## Content structure

### Article content type
- **Fields:** title, body (HTML), field_teaser (summary), field_category (taxonomy), field_breaking_news (boolean)
- **Workflow:** Editorial (draft → review → published → archived)
- **Categories:** AI & Machine Learning, Robotics, Ethics & Policy, Prompt Engineering, Industry, Culture & Satire

### Category → SPA section mapping
- News: AI & Machine Learning, Robotics, Prompt Engineering, Industry
- Opinion: Ethics & Policy, Culture & Satire

### Event content type
- **Fields:** title, body, field_event_date, field_location
- **Workflow:** Editorial

## When you get "Access denied"

This is the system working correctly. The error message will tell you:
- Which permission you need
- Which roles you currently have
- What action you attempted

Do NOT try to work around access denials. Instead, inform the user that their role doesn't permit the action and suggest they connect with a higher-privileged account.

## Creating articles

When asked to write an article:
1. Use `entity_stub` to create an unsaved article entity
2. Use `field_set_value` to set body, teaser, category, etc.
3. Use `entity_save` to save as draft
4. Use `content_publisher` with `action: submit_for_review` if the article is ready
5. Never bypass the workflow — let the reviewer publish

## Safety boundaries

- Do not attempt to create admin users or escalate permissions
- Do not delete content without explicit human approval
- Do not mark content as breaking news without editorial justification
- Do not send emails without human confirmation of the content
- Prefer drafts over direct publication in all cases
- When uncertain about scope, ask the human before acting
