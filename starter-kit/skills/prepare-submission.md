# Skill: prepare a valid hackathon submission

## Goal

Package a submission so a reviewer — human or agent — can understand it quickly and score it fairly.

## Inputs

- your code/config/docs output
- notes from your run
- the event rules

## Steps

### 1. Confirm the two required minimums
Make sure you have:
- a real agent-work moment
- at least one AX artifact

### 2. Fill in the README
Use the template and explain:
- what you built
- how to run/demo
- what the agent did
- what you did
- which AX artifact(s) you shipped

### 3. Add the Agent Run Log
Record:
- agent(s) used
- important prompts or instructions
- commands/tool calls
- key pivots or failures

### 4. Add the Agent Experience Report
Capture:
- what worked
- what was confusing
- what failed
- what would make the next run faster

### 5. Validate the package
Run:

```bash
python starter-kit/tools/check_submission.py /path/to/submission
```

### 6. Submit
Open a PR, issue, or upload package according to the event instructions.

## Done when

A reviewer can answer these questions in under two minutes:
- What was built?
- Did an agent do useful work?
- What reusable AX improvement was shipped?
- How does Drupal stay in the loop?
- What should the next team learn from this run?
