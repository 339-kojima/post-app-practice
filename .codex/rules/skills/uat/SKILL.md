---
name: uat
description: Run browser based user acceptance checks against this Laravel app with Playwright MCP, save screenshot evidence in the repository, and report each criterion with its observed result.
---

# Browser UAT for this repository

Use this skill when asked to verify user facing behavior in the local post app. Follow the acceptance criteria supplied for the current task; do not expand them into unrelated feature checks.

## Workflow

1. Use Playwright MCP to open the URL requested by the user (usually `http://localhost`). If it is unavailable, report the connection issue. Start the repository's Compose services only when the user asked for that or authorized it in the conversation. If the application returns an error, inspect the actual error and resolve only local, task relevant setup problems; do not silently change application behavior.
2. Sign in using credentials the user supplied or clearly documented as local test accounts. Do not expose credentials in screenshots or the final report.
3. Walk through each acceptance criterion in the requested order. Use the visible UI for the action and verify the resulting page state. For authorization checks, inspect whether the correct controls appear for each user; where appropriate, verify a direct unauthorized request is rejected without changing another user's data.
4. Capture a screenshot for each criterion after the state that demonstrates its result is visible. Save screenshots under `uat-shots/` with ordered, descriptive names, such as `01-post-detail.png`, `02-reply-added.png`, and `03-empty-validation.png`. Create the directory if needed. Prefer full page screenshots when they capture the relevant evidence clearly.
5. Report one line per criterion with a clear pass/fail (or blocked) judgment and the observation supporting it. Link each line to its screenshot using the repository-relative path. Mention any setup adjustment or test data left behind that affects the working environment.

## Evidence and state

- Base conclusions on the rendered page and observed interactions, not source code alone.
- After submit or delete actions, verify the visible state or response before deciding the outcome. A click returning without an error is not evidence that the operation succeeded.
- Preserve existing app data and screenshots. Use clearly recognizable test content; clean up only test records created during this run when the requested checks permit it, and report records that remain.
- If a required state cannot be reached, save a screenshot of the blocking state when possible and mark the criterion blocked rather than inferring success.
- Keep screenshot names tied to the acceptance criterion number so evidence is easy to match to the report.
---

確かめる受け入れ条件は、次のとおりです。

$ARGUMENTS

受け入れ条件そのものではなく、それが書いてあるファイルの場所を渡されたときは、
そのファイルを開いて、書かれている受け入れ条件を読んでから始めてください。
