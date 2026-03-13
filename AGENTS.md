# AGENTS.md

## Purpose

This repository supports **Permission to Run**, an Agent Experience (AX) hackathon for Drupal.

Your goal is not just to produce output. Your goal is to produce **useful, reviewable, governable output** that works inside normal Drupal constraints.

## Core rules

1. **Use the same safety model humans use.**
   - Respect permissions, workflows, schema, revisions, and diffs.
   - Do not invent agent-only backdoors.

2. **Prefer reviewable output.**
   - Drafts over direct publishing
   - Diffs over opaque changes
   - Small steps over giant mutations

3. **Show your work.**
   - Keep a run log
   - Record commands, prompts, and tool use
   - Explain what you changed and why

4. **Leave the next principal in a better position.**
   - Add context, templates, checks, or guidance where possible

## Good hackathon tasks

- draft content safely
- propose configuration changes as diffs
- summarize and triage issues
- generate PRs or patches with explanation
- add validators, tool mappings, or runbooks
- improve agent guidance for a real Drupal workflow

## Preferred output shape

A strong submission usually contains:
- one meaningful work artifact
- one reusable AX artifact
- a README
- a run log
- an Agent Experience Report

## Validation expectations

Before submitting:
- run tests if available
- run linters if available
- note clearly what you could not validate
- include exact commands where possible

## Safety and scope

- Do not use secrets you were not explicitly given
- Do not claim actions you did not actually perform
- Do not represent uncertain output as verified
- Do not bypass review, moderation, or approval processes
- Prefer reversible or reviewable changes

## Layering guidance

When creating guidance or configuration, make it composable:

**Core -> Project -> Local/Dev**

Avoid assumptions that only fit one tool or one team.

## Help and escalation

- GitHub Issues: <https://github.com/acquia/hackathon-drupalcon-chicago-2026/issues>
- Drupal Slack: channel to be posted here before launch
- Office hours: schedule to be posted here before launch

## Suggested process

1. Read this file and the event README
2. Choose a narrow goal
3. Identify how Drupal will remain in the loop
4. Make a small, reviewable change
5. Add one reusable AX improvement
6. Document what happened
7. Run the submission validator
