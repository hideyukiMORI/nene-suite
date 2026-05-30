# Installer Disclaimer Copy (for UI)

**Status: binding reference.** Use this text (or equivalent meaning) in the suite
installer wizard, apex shell, and release notes. Full legal context:
[`disclaimer.md`](./disclaimer.md).

---

## Short notice (checkbox / first screen)

**English (repository canonical):**

> NeNe Suite helps you install and configure NeNe applications. It does **not**
> guarantee business results, legal compliance, or accounting correctness. You remain
> solely responsible for how installed apps are used and for obtaining professional
> advice when required. See the [Disclaimer](./disclaimer.md).

**Japanese (operator-facing UI — allowed in product UI only, not in repo docs body):**

> NeNe Suite はアプリケーションのインストールと環境設定を支援するソフトウェアです。
> …（以下同文）

**UI implementation:** canonical strings live in message catalogs —
`frontend/src/shared/i18n/messages/en.ts` (English) and `ja.ts` (Japanese).
Keys: `suite.disclaimer.shortNotice`, `suite.disclaimer.checkbox`, etc.
See [`docs/development/i18n.md`](../development/i18n.md). Do not duplicate prose in JSX.

---

## Pre-complete summary (final step)

> You are about to finish setup. NeNe Suite has written configuration files and
> provisioned databases. **This does not certify** that your organization meets
> any tax, accounting, or industry rule. Review each installed application's
> documentation and consult qualified professionals before production use.

---

## Apex shell footer (one line)

> Setup orchestration only — no business or legal warranty. [Disclaimer](./disclaimer.md)

---

Last updated: 2026-05-29
