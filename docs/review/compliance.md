# Orchestration Compliance Self-Review

**Binding.** Use for **any** change touching the installer, catalog, `NENE_SUITE_*`
env contract, SSOT documentation, sibling wiring defaults, install manifest,
suite audit trail, operator-facing copy, or disclaimer language. If unsure, assume compliance impact
and run this list.

Source of truth: [`../explanation/orchestration-compliance.md`](../explanation/orchestration-compliance.md).
Do not delete items to pass. Mark `N/A` only when genuinely not applicable.

## Checklist

- [ ] Change reviewed against `docs/explanation/orchestration-compliance.md`; impact stated in PR.
- [ ] SSOT matrix preserved — Invoice billing, Clear evidence, Vault archive boundaries not blurred.
- [ ] One database per app; no cross-DB domain writes during install.
- [ ] `external_id` treated as IT federation only — not tax registration or legal merge.
- [ ] No auto-copy of issuer registration number or billing figures across apps.
- [ ] HTTP-only integration; no shared domain tables or cross-app SQL.
- [ ] Clear → Invoice (if wired) uses documented service scopes only; operator explicitly confirmed.
- [ ] Install manifest spec respected or updated in same PR (Phase 1+).
- [ ] Audit trail spec respected or updated in same PR when adding/changing mutating installer steps (Phase 1+).
- [ ] New audit `action` / `entity_type` registered in audit-trail.md §4 and JSON schema before merge.
- [ ] `before_json` / `after_json` use sanitized presenters; secrets redacted as `[REDACTED]`.
- [ ] Audit rows append-only — no UPDATE/DELETE of historical events.
- [ ] Installer / docs language avoids "compliant out of the box", "audit-ready", 税理士不要, etc.
- [ ] Disclaimer linked for operator-facing flows; no business/legal guarantee strings added.
- [ ] Secrets not written to install manifest or git.
- [ ] Any deviation carries ADR + professional sign-off per orchestration-compliance §9.
- [ ] Sibling product compliance docs referenced where domain touch occurs — not duplicated or overridden.
- [ ] Terminology matches [`../explanation/terminology.md`](../explanation/terminology.md); see [`../review/terminology.md`](../review/terminology.md).
