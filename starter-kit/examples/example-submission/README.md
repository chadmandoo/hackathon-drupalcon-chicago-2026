# Example submission: Moderated content draft assistant

## What we built

We built a small workflow that lets an agent draft a new Drupal article and leave it in review instead of publishing it directly.

The output includes a content draft payload, a review checklist, and a runbook for repeating the workflow.

## Problem addressed

Content teams want help drafting pages quickly, but they still need moderation, review, and a clear audit trail.

## What the agent did

The agent generated draft body copy, suggested taxonomy tags, and produced a reviewable JSON payload instead of trying to publish content directly.

## What the human did

The human selected the prompt, reviewed field mappings, and approved the final draft before submission.

## Drupal-in-the-loop

Drupal remained the governor through content schema, required fields, moderation workflow, and revision history. The agent produced reviewable output rather than bypassing those checks.

## AX artifact(s) shipped

- `AGENTS.md`
- `runbook.md`
- `validator-notes.md`

## How to run / demo

```bash
# Example only
python build_draft.py --input prompt.txt --output draft.json
```

## Validation

```bash
python -m json.tool draft.json > /dev/null
```

## Limits / known issues

This example does not connect to a live site.

## Links

- issue / PR: N/A
- demo / video: N/A
- related docs: N/A
