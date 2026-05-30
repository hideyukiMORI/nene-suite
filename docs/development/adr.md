# ADR Policy

NeNe Suite uses lightweight Architecture Decision Records, inherited from
[NENE2 ADR policy](https://github.com/hideyukiMORI/NENE2/blob/main/docs/development/adr.md).

## When to write an ADR

Write an ADR when a decision affects:

- suite installer architecture or orchestration contracts
- JWT / org federation / `NENE_SUITE_*` environment surface
- catalog schema or sibling dependency rules
- Tier A vs Tier B packaging strategy
- release, versioning, or compatibility policy
- long-term maintenance cost

Do **not** use ADRs for routine task detail — use Issues, PRs, and `docs/todo/current.md`.

## Directory and naming

```text
docs/adr/
├── 0000-template.md
├── 0001-inherit-nene2-governance.md
└── NNNN-short-title.md
```

Use a stable four-digit sequence. Do not renumber after publication.

## Status values

- `proposed`
- `accepted`
- `deprecated`
- `superseded`

Supersede old ADRs instead of silently rewriting history.

## Template

Use `docs/adr/0000-template.md`.

Link related Issues and PRs in the **Related** section.

## Language

Repository ADRs are written in **English**, consistent with sibling product governance docs.
