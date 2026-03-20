# Contributing to Permission to Run

Thanks for contributing to the Permission to Run hackathon repository.

This repo supports a Drupal-focused Agent Experience (AX) event. Contributions should stay reviewable, governable, and easy for the next principal to understand.

## Before you start

Read these first:

- [`README.md`](./README.md)
- [`AGENTS.md`](./AGENTS.md)
- [`docs/rules.md`](./docs/rules.md)
- [`starter-kit/README.md`](./starter-kit/README.md)

If you are preparing an event submission, make sure it follows the current rules and includes the required materials from the starter kit.

## Ways to contribute

We welcome improvements through:

- GitHub issues for ideas, fixes, and questions
- Pull requests for concrete, reviewable changes

Good contributions for this repository include:

- clarifying event guidance
- improving starter templates
- adding validators or guardrails
- fixing documentation errors
- improving example submissions
- contributing reusable AX artifacts

## Contribution expectations

Keep changes small and easy to review.

When you open a pull request:

- explain what changed and why
- include exact commands you ran to validate the change
- note anything you could not validate
- avoid including secrets, credentials, or confidential information
- preserve normal Drupal workflows and review paths

For event submissions and substantial repo changes, prefer:

- drafts over direct publishing
- diffs over opaque output
- run logs over undocumented steps
- reusable artifacts over one-off output

## Submission-specific guidance

If your pull request is a hackathon submission, include:

- one meaningful work artifact
- at least one AX artifact
- a README
- an Agent Run Log
- an Agent Experience Report
- the name or GitHub handle of the on-site DrupalCon team lead if the submission should be prize eligible

You can validate a starter-kit style submission with:

```bash
python3 starter-kit/tools/check_submission.py /path/to/submission
```

## Licensing

By contributing to this repository, you agree that your contribution may be distributed under the terms in [`LICENSE.txt`](./LICENSE.txt).

If your submission is intended for the Best Contribution Back to Drupal category, make sure you have the rights to contribute it and that any Drupal-derivative code can be shared under Drupal-compatible licensing.

## Need help

- GitHub Issues: <https://github.com/acquia/hackathon-drupalcon-chicago-2026/issues>
- See the repository README for current event guidance and supporting docs
