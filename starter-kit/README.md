# Starter kit v0.1

This starter kit is here to help the **next principal** — human, agent, or hybrid — succeed faster.

## Start here

1. Read [`AGENTS.md`](./AGENTS.md)
2. Pick a task
3. Use the templates in [`templates/`](./templates/)
4. Run the submission validator in [`tools/check_submission.py`](./tools/check_submission.py)
5. Submit according to the event instructions

## Included

- `AGENTS.md` — repo guidance for agents
- `skills/prepare-submission.md` — repeatable workflow for preparing a valid entry
- `skills/write-agent-experience-report.md` — workflow for generating the required AX report
- `templates/` — starter templates for README, run log, AX report, and benchmark tasks
- `tools/check_submission.py` — basic validator for event submissions

## Event defaults

- Submission path: open a GitHub issue or pull request in this repo
- Issues: <https://github.com/acquia/hackathon-drupalcon-chicago-2026/issues>
- Help channel: GitHub Issues first; Drupal Slack channel will be posted before launch
- Prize eligibility: remote collaborators are fine, but each prize-eligible submission needs one on-site DrupalCon team lead
- Validator command: `python3 starter-kit/tools/check_submission.py /path/to/submission`
- Example validator run: `python3 starter-kit/tools/check_submission.py starter-kit/examples/example-submission`
