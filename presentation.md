# The Prompt Post — Presentation Deck

## Slide 1: The Vision

### Drupal + Desktop AI = The Editor's New Best Friend

What if your editorial team could manage a Drupal site from their desktop AI assistant — the same way developers use Cursor for code?

**The idea:** Connect Claude Desktop (or any MCP-capable AI) to your Drupal site. The AI becomes the editorial interface. Drupal stays the governor. The editor gets the best of both worlds: the intelligence and flexibility of a desktop AI agent, with the safety and structure of Drupal's permission system.

**Why this matters:**
- Editors work in their own environment — local files, notes, ideas, research documents — and the AI bridges the gap to the CMS
- No need to build complex editorial UIs on the web; the AI wraps the experience
- Claude + Drupal MCP is to content editing what Cursor is to code editing
- The AI agent has full context: your local documents, your conversation history, AND your Drupal site — all at once

---

## Slide 2: What We Built

### The Prompt Post — A Living Demo

A satirical AI news publication running on Drupal 11 with:

- **32 MCP tools** (12 custom editorial tools + 20 contrib)
- **3-tier permission model:** Writer (22 tools) → Reviewer (23) → Admin (32)
- **Editorial workflow:** draft → review → published → archived
- **Decoupled React SPA** at thepromptpost.chadpeppers.dev
- **OAuth 2.1** with Dynamic Client Registration — paste a URL, log in, done

**The demo:** Claude manages the newspaper. Creates articles, submits for review, publishes (if authorized), flags breaking news, generates Sudoku puzzles. A Writer literally cannot publish. Drupal enforces it. Changes appear on the live site instantly.

---

## Slide 3: Why Desktop AI, Not Embedded AI?

### The Agent Belongs on the Desktop, Not in the CMS

Most approaches try to embed AI inside Drupal — a chatbot in the admin UI, an AI assistant in the content form. We went the other direction.

**The AI lives on the editor's desktop. Drupal is the backend.**

This is better because:

| Embedded AI (in Drupal) | Desktop AI (via MCP) |
|------------------------|---------------------|
| Limited to what the web UI shows | Full access to local files, docs, research |
| One interface for everyone | Each editor's AI adapts to their workflow |
| Context is only what's on screen | Context is everything: local + remote |
| Building UI is expensive | AI IS the UI — no frontend dev needed |
| Locked to one AI vendor | Any MCP-capable AI works |

**The power move:** An editor can have a folder of research documents, a competitor analysis spreadsheet, brand guidelines, and a conversation with Claude — then say "Write an article about the trends in this research and publish it to The Prompt Post as a draft." Claude reads the local files, writes the article, and creates it in Drupal. No copy-paste. No file uploads. No context switching.

---

## Slide 4: Developer & Sysadmin Tools

### Not Just for Editors

The same MCP connection serves developers and sysadmins:

**Developer tools:**
- `module_help` — Read any module's hook_help documentation
- `site_briefing` — Full site architecture overview
- `entity_type_list` / `entity_bundle_list` — Explore content structure
- `entity_field_value_definitions` — Understand field schemas

**Sysadmin tools:**
- `system_status` — Drupal status report (errors, warnings)
- `recent_activity` — Watchdog log timeline
- `whos_online` — Active user monitoring
- `user_block` / `user_unblock` / `user_add_role` — User management

**The experience:** A developer runs Claude Code on their machine. They ask "What's the site status?" Claude calls the MCP tool, gets the report, and can immediately help troubleshoot. No SSH required. No admin UI login. Just ask.

---

## Slide 5: Visual Content Planning

### See It Before You Ship It

With a decoupled SPA (or even Drupal's own frontend), editors can use Claude to:

1. **Draft content locally** — Claude writes the article in conversation
2. **Preview it visually** — Claude generates a static HTML preview using the site's styles
3. **Iterate with feedback** — "Make the headline shorter, add a pull quote, change the category"
4. **Publish when ready** — One command sends it to Drupal through MCP

The content never touches Drupal until the editor is satisfied. No draft pollution. No incomplete nodes cluttering the admin. The AI handles the messy creative process; Drupal gets the clean result.

---

## Slide 6: The Possibilities

### Desktop AI + Drupal MCP = Unlimited Potential

What becomes possible when your AI assistant has structured access to your CMS:

- **Charts and analytics** — "Show me a graph of articles published per category this month" → Claude calls `site_analytics`, renders a chart locally
- **Content calendars** — "Plan next week's content based on trending topics" → Claude uses `editorial_dashboard`, cross-references with local research, drafts a schedule
- **Multi-site management** — Connect Claude to multiple Drupal sites via MCP, manage them all from one conversation
- **Documentation generation** — "Document all the content types and their fields" → Claude calls `module_help` and `entity_bundle_field_definitions`, writes the docs
- **Audit and compliance** — "Show me all content changes in the last 24 hours" → `recent_activity` with full audit trail

**The key insight:** Editors, developers, and managers all get AI-powered context from Drupal data without needing to move files onto the server, configure a vector database, or build custom integrations. The MCP connection IS the integration.

---

## Slide 7: Challenges & Lessons Learned

### What We Ran Into

**MCP Server Module — Promising but Early**
- The `drupal/mcp_server` module works well once configured, but setup requires understanding OAuth 2.1, PKCE, and Dynamic Client Registration simultaneously
- We had to patch the module to fix JSON Schema serialization (broke Claude.ai's connector), add tool filtering by role, and support server instructions
- The module feels developer-first today — it needs a guided setup wizard for site builders
- That said, once running, the MCP connection is remarkably solid and performant

**Claude Desktop OAuth — Friction Points**
- Setting up the OAuth connection works, but Claude caches aggressively — switching between users requires disconnecting and reconnecting
- The tool list persists in Claude's session even after permission changes; a full reconnect is needed
- These are UX issues, not deal-breakers — the underlying protocol is sound

**MCP Client Support — Growing but Uneven**
- Claude Desktop and Claude.ai: full MCP support, works great
- ChatGPT: web version supports MCP, desktop app does not (as of March 2026)
- Any MCP-capable client should work in theory — we couldn't test all of them in the time available
- The MCP spec is open; adoption is a matter of time

**Per-Tool Authorization**
- The connecting user must grant access to each tool when first connecting — this is a feature, not a bug
- It means the human explicitly approves what the AI can do, adding another governance layer on top of Drupal's permissions

---

## Slide 8: The Future

### Where This Goes Next

**Claude is becoming a Cursor-like experience.** Anthropic is building toward a desktop environment where AI can see, interact with, and render visual output. Imagine:

- Claude renders your Drupal site directly in its interface — like Cursor's preview pane but for content
- You edit an article in conversation, see the live preview update, and publish when satisfied
- The workaround today is static local previews (which Claude handles well), but native site rendering would be transformative

**What AI didn't do well:**
- Initial MCP connection required developer debugging (OAuth configuration, token lifetimes, JSON Schema bugs)
- The bottleneck was never the AI's capability — it was the setup process and module maturity
- Once connected, Claude performed exceptionally: wrote all 15 articles, built all 12 tools, created the permission system, and managed the editorial workflow without significant issues

**The real opportunity:**
This could be a contributed module ecosystem — a "Drupal Editorial AI" distribution with:
- Pre-configured MCP tools for common editorial workflows
- A setup wizard that handles OAuth configuration
- Role-based tool presets (Writer, Editor, Admin)
- Visual preview integration
- Multi-site management support

The technology works today. The ecosystem just needs to catch up.

---

## Slide 9: Summary

### TL;DR

We connected Claude to a Drupal 11 site via MCP and let the AI manage a newspaper.

**What we proved:**
1. Desktop AI + Drupal MCP is a viable editorial workflow — not a theory, a working product
2. Drupal's existing permission system governs AI agents perfectly — no special cases needed
3. Tool filtering at discovery time (our `hook_mcp_server_enabled_tools_alter()` patch) makes governance visible, not just enforced
4. The editor experience is superior to embedded AI: local context + remote CMS + AI intelligence
5. The same infrastructure serves editors, developers, and sysadmins with role-appropriate tools

**What we shipped:**
- 4 custom Drupal modules with 12 MCP tools
- A contrib patch adding tool filtering to the MCP server module
- A decoupled React SPA frontend
- 15 full-length satirical articles, 10 job listings, 4 events
- Complete architecture documentation and submission materials
- A live, working demo at thepromptpost.chadpeppers.dev

**The bottom line:** The future of Drupal editorial is not "AI in the admin UI." It's "Drupal as the governed backend for AI agents that live wherever the editor works."

---

*Demo video: https://www.youtube.com/watch?v=V0B0RgSnsW0*
*Live site: https://thepromptpost.chadpeppers.dev*
*MCP endpoint: https://drupalcon2026.chadpeppers.dev/_mcp*
*Source: https://github.com/chadmandoo/hackathon-drupalcon-chicago-2026*
