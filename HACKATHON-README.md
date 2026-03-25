<p align="center">
  <img src="./assets/permission-to-run-banner.svg" alt="Permission to Run banner" />
</p>

# Permission to Run
## The Agent Experience (AX) Hackathon for Drupal

An Acquia-sponsored DrupalCon Chicago 2026 hackathon.

Build with any agent. Let Drupal be the governor.

Permission to Run is what happens when agents can move at full speed and Drupal keeps outcomes safe, reviewable, and repeatable through permissions, workflow, schema, diffs, and auditability.

## Why this exists

We are designing Drupal for **principals**: humans, agents, and hybrids.

That means giving agents enough context, validation, and guidance to do useful work without magic backdoors or one-off exceptions. When we improve Agent Experience (AX), we improve Drupal for everyone.

## Core rule

Build something with **any agent**.

Use:
- Drupal's built-in agent/chat interface
- your own agent workflow
- Cursor, Claude Code, Codex, custom agents, MCP/JSON:API/CLI tooling
- a team of agents

Tool-agnostic is the point.

## What to build

Your project should make agent work safer, more accountable, or more repeatable in Drupal.

Examples:
- agent drafts content safely using revisions, moderation, or workspaces
- agent proposes config changes as reviewable diffs
- safe site-ops tool boundaries with batching, rollback, and audit logs
- one-interface patterns that reduce module-by-module UI discovery
- validators, runbooks, and guardrails that improve first-pass success

## AX principles

### 1. Parity with human safety
Agents operate inside the same frameworks humans use:
- permissions
- schema validation
- workflows
- revisions
- config diffs

No magic backdoors.

### 2. Guidance over giant new features
Prefer:
- context files
- instructions
- templates
- validators
- checks
- small guardrails

### 3. Open standards and tool-agnostic design
Your output should help:
- Drupal's built-in agent
- bring-your-own agents
- future tools

### 4. Layered overrides
Design artifacts so they can be layered:
**Core -> Project -> Local/Dev**

## Minimum requirements

To qualify, your submission must include **both**:

### A. A working "agent does real work" moment
Show an agent completing meaningful steps toward a goal:
- creating drafts
- generating diffs
- proposing config changes
- summarizing issues
- preparing reviewable output

### B. At least one AX artifact
Ship one or more of:
- `AGENTS.md`
- a skill or runbook
- a validator or gate
- a tool interface mapping
- a benchmark task definition

## Required AX loop

Every submission must include an **Agent Experience Report** that says:
- what worked
- what was confusing
- what it could not do
- what guidance or interface would make it 10x faster

Bonus: open that report as a Drupal issue or GitHub issue so the next agent can pick it up.

## Who can participate

This is a virtual-first hackathon, so remote collaborators are welcome.

No purchase necessary to enter or win.

To be eligible for judging and prizes, each submission must name one team lead who:
- is physically attending DrupalCon Chicago 2026
- is the primary contact for the submission
- can be available in person on Wednesday afternoon if judges need clarification or if the team wins

Teams without an on-site DrupalCon lead are still welcome to share their work, but they should treat it as showcase participation rather than prize-eligible competition.

## What to submit

Your submission is complete when it includes:

### 1. Output
Code, config, docs, repo, patch, PR, or zip

### 2. README
Explain:
- what you built
- how to run or demo it
- what the agent did vs what you did
- which AX artifact(s) you shipped

### 3. Agent Run Log
Rough is fine. Include:
- which agent(s) you used
- key prompts or steps
- main commands or tool calls

### 4. Agent Experience Report
Required.

### Submission path
Submit your work as either:
- a GitHub issue in this repo
- a pull request against this repo

Link any external repo, demo, or attachment you want judges to review.
Include the name or GitHub handle of your on-site DrupalCon team lead if you want the submission to be prize-eligible.

## Judging

We will score entries on:
- **Agent Success**
- **AX Quality**
- **Drupal-in-the-loop**
- **Openness**
- **Impact**

Entries will be reviewed by Acquia and Drupal judges, with an AI-assisted pass used to help compare patterns and surface strong entries. Final decisions are made by human judges.

See the full rubric in [`docs/judging-rubric.md`](./docs/judging-rubric.md).

## Prize categories

Prize categories:
1. **Best Overall**
2. **Best Agent**
3. **Best Contribution Back to Drupal**

See the official rules in [`docs/rules.md`](./docs/rules.md).

If you want your entry considered for **Best Contribution Back to Drupal**, make sure the contribution can be shared under Drupal-compatible licensing. For Drupal-derivative code intended for Drupal.org-hosted projects, that generally means code that can be released under **GPL-2.0-or-later**.

## Dates and logistics

- DrupalCon Chicago 2026 runs Monday, March 23 through Thursday, March 26, 2026.
- Entries due Wednesday, March 25, 2026 at 12:00 PM Central Time.
- Judging happens Wednesday afternoon, with winner timing announced here once finalized.
- This is a virtual-first hackathon with in person support at the Acquia booth if needed.
- Prize-eligible teams must have an on-site DrupalCon lead available Wednesday afternoon.

## Repository layout

- [`starter-kit/`](./starter-kit/) - starter docs, templates, examples, and the submission validator
- [`docs/judging-rubric.md`](./docs/judging-rubric.md) - scoring model and judging flow
- [`docs/rules.md`](./docs/rules.md) - official contest rules

## Get started

1. Open the starter kit
2. Read [`starter-kit/AGENTS.md`](./starter-kit/AGENTS.md)
3. Pick a task
4. Build with any agent
5. Submit your work as an issue or PR

## Need help?

- GitHub Issues: <https://github.com/acquia/hackathon-drupalcon-chicago-2026/issues>
