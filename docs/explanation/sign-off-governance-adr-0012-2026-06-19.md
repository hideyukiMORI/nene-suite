# Governance サインオフ記録 — ADR 0012

Based on [`professional-sign-off-record.md`](./professional-sign-off-record.md) template.

> **This is a governance / architecture sign-off — NOT a licensed-professional (士業)
> review.** It records the project owner's engineering governance decision to accept
> ADR 0012. It is not legal, tax, or accounting advice, and does not certify any
> operator's business compliance.

---

## 審査メタデータ

| 項目 | 内容 |
| :--- | :--- |
| **審査対象文書** | `docs/adr/0012-federation-participation-contract.md`, `terminology.md` (§4–§6) |
| **スイートのバージョン** | v0.1.0 / `26fcf1e` (main HEAD at time of decision) |
| **審査日** | 2026-06-19 |
| **審査者の資格** | プロジェクトオーナー / アーキテクチャ governance（士業ではない） |
| **審査者氏名** | hideyukiMORI |
| **ステータス** | Governance sign-off completed |

---

## 審査範囲チェックリスト

- [x] **メンバーシップ = 可逆な `NENE_SUITE_MODE` トグル・データ移動なし（§1–§2）**
  - sibling DB は常にローカル SSOT。join/leave は設定変更のみ・非破壊であることを確認。
- [x] **2トークン / 2トラストドメイン（§4）**
  - federation アサーション（非対称・JWKS verify-only）とローカルセッション（HMAC `NENE2_LOCAL_JWT_SECRET`・sibling 所有）の分離を確認。SSO は login 認証のみで billing API は自前セッション（fail-closed）。
- [x] **org UUID 権威・merge 不能（§5）**
  - ローカル org id が billing/採番/issued の不変アンカー、`organizations.external_id` は nullable リンク。スキーマレベルで merge 不能。
- [x] **HMAC → 非対称 supersession（§4・terminology §4.2）**
  - ADR 0004 の共有 HMAC ログイン経路を supersede する範囲が federation 検証経路に限定され、ローカルセッションを侵さないことを確認。

### compliance ガードレールの継承（§11）

- [x] §11（merge/split/renumber 禁止・issued docs ローカル・soft-disable・`external_id` は登録番号と別物）は、**orchestration-compliance §2–§5 の再掲**であり、新規の compliance 義務を導入しない。
  - 当該原則は **2026-05-31 に公認会計士・税理士がサインオフ済み**（[`sign-off-tax-accounting-2026-05-31.md`](./sign-off-tax-accounting-2026-05-31.md) / Issue #75）。federation・`external_id` の意味（§4）はその審査範囲に含まれていた。
  - したがって ADR 0012 を accepted にするにあたり、**新たな士業レビューは要しない**と判断する。

---

## 審査所見・サインオフ意見

ADR 0012 が定める federation participation contract は、(a) 既存の orchestration-compliance 原則（DB 非共有・sibling ドメイン SSOT・external_id の IT 統合限定）を侵さず、(b) ローカルセッションの認可経路を federation から分離して fail-closed を維持し、(c) 組織連携を 1:1 リンクに固定して merge をスキーマレベルで不能にしている。

アーキテクチャ governance の観点からこれらの方針は適切であり、ADR 0012 を `accepted` に昇格することを承認する。

---

## 公式サインオフ文言

> 審査対象文書は、記載のエンジニアリングスコープ（suite ↔ sibling federation 境界）において受け入れ可能であり、ADR 0012 を `accepted` とする。このサインオフは owner の governance 決定であり、士業によるレビューでも、オペレーターの業務コンプライアンスの保証でもない。

**確認チャネル:** GitHub Issue #96

---

## エンジニアリング後続作業

- [ ] Blocking findings: なし（findings テーブル空）
- [ ] **セキュリティレビュー（実装時）** — federation IdP / 非対称鍵の取り扱い・JWKS ローテーションを、JWKS endpoint・authz-code surface 実装時にレビューする。本 governance sign-off の前提ではなく follow-up。
- [ ] ADR 0012 accepted を受けて nene-invoice ADR 0016 を参照のみで起こす（cross-repo）。

---

*この記録は士業によるレビューではなく、オペレーターの業務コンプライアンスを保証するものではありません。
 sibling 製品（Invoice 等）のドメインロジックについては各製品の compliance docs を参照してください。*
