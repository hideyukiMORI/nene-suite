# Commit Message Conventions

NeNe Suite uses Conventional Commits, inherited from
[NENE2](https://github.com/hideyukiMORI/NENE2/blob/main/docs/development/commit-conventions.md)
and aligned with [NeNe Records](../nene-records/docs/development/commit-conventions.md).

## Format

```text
<type>(<optional scope>): <description> (#<issue>)

[optional body]

[optional footer]
```

## Language

- Keep `type`, `scope`, `BREAKING CHANGE`, and other Conventional Commits keywords in **English**.
- Write the **description and body in Japanese**.
- Include the related GitHub Issue number in the subject when practical.

Example:

```text
docs(governance): Issue 駆動ワークフローと Phase 0 ドキュメントを追加する (#1)
```

## Common Types

| Type | Use |
| --- | --- |
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation only |
| `refactor` | Code change without feature or bug fix |
| `test` | Test additions or changes |
| `build` | Dependency or build setup |
| `ci` | CI configuration |
| `chore` | Maintenance |

## Body

Use the body when the reason is not obvious from the subject. Explain why the change exists, what trade-off was chosen, and whether follow-up work remains.

## Breaking Changes

Use `!` or a `BREAKING CHANGE:` footer when installer contracts, catalog schema, environment variable names, or documented behavior changes incompatibly.

Catalog or env contract changes must update `catalog/apps.json` and the relevant ADR or integration doc in the same PR when possible.
