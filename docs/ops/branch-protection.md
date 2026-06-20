# Branch protection (`main`)

`main` is protected so the `AGENTS.md` rule **"No direct commits to `main`"** is
enforced technically, not just by convention. Protection is implemented as a
**repository ruleset** named `protect-main` (id `17923674`, enforcement
`active`), created 2026-06-20.

## Rules

| Rule | Effect |
| --- | --- |
| `pull_request` (required approvals **0**) | All changes to `main` must go through a pull request. Direct `git push origin main` is rejected. Approvals are set to 0 so a solo maintainer can merge their own PR without a separate reviewer. |
| `required_status_checks` | The three CI jobs must pass before merge: `Docs, catalog & OpenAPI`, `Frontend (type-check + tests)`, `Backend (PHPUnit + PHPStan + CS)`. |
| `non_fast_forward` | Force-pushes (history rewrites) to `main` are blocked. |
| `deletion` | The `main` branch cannot be deleted. |

**Bypass: none.** `bypass_actors` is empty, so administrators have **no**
exception either — `git push origin main` and `gh pr merge --admin` (merging
before checks finish) are both blocked for everyone.

A direct push is rejected with:

```
! [remote rejected] main -> main (push declined due to repository rule violations)
- Changes must be made through a pull request.
- 3 of 3 required status checks are expected.
```

## Normal flow

1. Branch: `git checkout -b type/issue-number-summary`
2. Commit (Conventional Commits) and push the branch.
3. Open a PR against `main` (`gh pr create`).
4. Once CI is green, merge — auto-merge works: `gh pr merge --squash --auto --delete-branch`.

No second reviewer is required (approvals = 0), but CI must pass.

## Administration

These commands require repo admin and the GitHub CLI (`gh`).

```bash
# Inspect rules currently applied to main
gh api repos/hideyukiMORI/nene-suite/rules/branches/main

# View / edit the ruleset
gh api repos/hideyukiMORI/nene-suite/rulesets/17923674

# Temporarily disable in an emergency (re-enable with enforcement=active)
gh api -X PUT repos/hideyukiMORI/nene-suite/rulesets/17923674 -f enforcement=disabled

# Remove protection entirely
gh api -X DELETE repos/hideyukiMORI/nene-suite/rulesets/17923674
```

When CI job names in `.github/workflows/ci.yml` change, update the
`required_status_checks` contexts in the ruleset to match, or merges will block
on a check that no longer reports.
