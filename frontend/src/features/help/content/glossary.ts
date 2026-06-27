import type { GlossaryCategory, GlossaryTerm } from './types'

/** Display labels for glossary categories (rendered as section headings). */
export const GLOSSARY_CATEGORIES: Readonly<Record<GlossaryCategory, string>> = {
  basics: '基本',
  tenancy: '組織とメンバー',
  install: 'インストール',
  database: 'データベース',
  federation: '連携（フェデレーション）',
  origin: '更新・お知らせ',
  operation: '運用・安全',
}

/**
 * Plain-language glossary for NeNe Suite. Japanese-first (English follows later).
 * Keep `short` to one sentence a non-technical operator can understand; put
 * detail in `body`. `identifiers` are auto-linked from guide prose by HelpText.
 */
export const GLOSSARY: readonly GlossaryTerm[] = [
  // ── 基本 ─────────────────────────────────────────────────────────────────
  {
    id: 'nene-suite',
    term: 'NeNe Suite（ねね スイート）',
    category: 'basics',
    short:
      '複数の NeNe アプリを 1 台のサーバーにまとめて導入・管理するための土台（オーケストレーター）です。',
    body: [
      'NeNe Suite 自体は請求書や帳簿などの業務機能を持ちません。必要なアプリを選んで入れ、共通のログイン画面と管理画面を用意するのが役目です。',
      '各アプリは Suite を使わず単体でも導入できます。Suite は「まとめて入れて、まとめて管理する」ための入口です。',
    ],
    see: ['apex', 'sibling'],
  },
  {
    id: 'apex',
    term: 'apex（玄関画面）',
    reading: 'えーぺっくす',
    category: 'basics',
    short:
      'ログインした後に最初に開く共通画面です。アプリへの入口・管理・インストーラがここに集まります。',
    see: ['nene-suite'],
  },
  {
    id: 'sibling',
    term: '兄弟アプリ（sibling）',
    category: 'basics',
    short: 'Suite で導入できる個々の NeNe 製品（Invoice・Clear・Records など）のことです。',
    identifiers: ['sibling'],
    see: ['nene-suite', 'catalog'],
  },
  {
    id: 'suite-mode',
    term: 'スイートモード（suite mode）',
    category: 'basics',
    short:
      'アプリが「Suite に組み込まれて動く」状態です。環境変数 NENE_SUITE_MODE=1 で有効になります。',
    identifiers: ['suite mode', 'NENE_SUITE_MODE'],
    see: ['org-external-id'],
  },
  {
    id: 'edition',
    term: 'エディション（edition）',
    category: 'basics',
    short:
      '動かし方の種別です。自分のサーバーで動かす OSS 版と、クラウドで使う hosted 版があります。',
    identifiers: ['edition'],
  },

  // ── 組織とメンバー ─────────────────────────────────────────────────────────
  {
    id: 'organization',
    term: '組織（organization）',
    category: 'tenancy',
    short: '利用する会社・事業者の単位です。メンバーやアプリの所属先になります。',
    identifiers: ['organization'],
    see: ['membership', 'org-external-id'],
  },
  {
    id: 'membership',
    term: 'メンバーシップ（membership）',
    category: 'tenancy',
    short: 'ある人（オペレーター）が、どの組織に・どの役割で属するかの結びつきです。',
    identifiers: ['membership'],
    see: ['organization', 'operator', 'role'],
  },
  {
    id: 'operator',
    term: 'オペレーター（operator）',
    category: 'tenancy',
    short: 'Suite にログインして操作する人（管理者や担当者）のことです。',
    identifiers: ['operator'],
    see: ['membership', 'role'],
  },
  {
    id: 'role',
    term: '役割（role）',
    category: 'tenancy',
    short:
      'メンバーができることの範囲です。Admin（管理）・Member（一般）・Viewer（閲覧）があります。',
    identifiers: ['role'],
    see: ['superadmin', 'membership'],
  },
  {
    id: 'superadmin',
    term: 'スーパー管理者（superadmin）',
    category: 'tenancy',
    short: 'プラットフォーム全体を管理できる最上位の権限です。組織をまたいで操作できます。',
    identifiers: ['superadmin'],
    see: ['role'],
  },
  {
    id: 'org-external-id',
    term: 'org_external_id（組織の共通ID）',
    category: 'tenancy',
    short:
      '組織を各アプリへ橋渡しするための共通の識別子です。ログイン連携や環境設定の紐付けに使います。',
    body: [
      'これは IT 上の識別子であり、税務上の法人番号や適格請求書登録番号ではありません。会計上の意味は持ちません。',
    ],
    identifiers: ['org_external_id'],
    see: ['organization', 'federation'],
  },

  // ── インストール ───────────────────────────────────────────────────────────
  {
    id: 'catalog',
    term: 'カタログ（catalog）',
    category: 'install',
    short: '導入できるアプリの一覧と、その依存関係（先に入れる必要があるアプリ）の情報です。',
    identifiers: ['catalog'],
    see: ['sibling'],
  },
  {
    id: 'install-session',
    term: 'インストールセッション（install session）',
    category: 'install',
    short:
      '1 回のインストール作業のまとまりです。アプリ選択 → データベース → 同意 → 完了の状態を持ちます。',
    identifiers: ['install session', 'install_session'],
    see: ['install-manifest', 'disclaimer'],
  },
  {
    id: 'install-manifest',
    term: 'マニフェスト（manifest）',
    category: 'install',
    short:
      'インストールした結果の記録（スナップショット）です。何をどう構成したかを後から確認できます。',
    body: ['パスワードやトークンなどの秘密情報は含めません。'],
    identifiers: ['manifest'],
    see: ['install-session', 'audit-event'],
  },
  {
    id: 'disclaimer',
    term: '免責事項（disclaimer）',
    category: 'install',
    short:
      'Suite は技術的な導入支援のみで、業務・法務・会計の正しさは保証しない、という説明です。導入前に同意が必要です。',
    identifiers: ['disclaimer'],
    see: ['install-session'],
  },

  // ── データベース ───────────────────────────────────────────────────────────
  {
    id: 'database-target',
    term: 'データベースの割り当て（database target）',
    category: 'database',
    short:
      '各アプリがどのデータベースを使うかの設定です。新規作成（provision）か既存採用（adopt）かを選びます。',
    identifiers: ['database target'],
    see: ['provision', 'adopt'],
  },
  {
    id: 'provision',
    term: '新規作成（provision）',
    category: 'database',
    short:
      'Suite が新しいデータベースを作って割り当てる方式です。とくに事情がなければこちらが既定です。',
    identifiers: ['provision'],
    see: ['adopt', 'database-target'],
  },
  {
    id: 'adopt',
    term: '既存採用（adopt）',
    category: 'database',
    short:
      'すでにあるデータベースをそのまま登録して使う方式です。Suite は中身を作りも変えもしません（登録のみ）。',
    body: [
      '「いま使っているデータをそのまま Suite の管理下に置きたい」ときに使います。元のデータはそのまま残ります。',
    ],
    identifiers: ['adopt'],
    see: ['provision', 'database-target'],
  },

  // ── 連携（フェデレーション） ────────────────────────────────────────────────
  {
    id: 'federation',
    term: 'フェデレーション（federation）',
    category: 'federation',
    short: 'Suite と各アプリの間で、ログインや組織情報を安全に受け渡すしくみです。',
    identifiers: ['federation'],
    see: ['jwks', 'org-external-id'],
  },
  {
    id: 'jwks',
    term: 'JWKS（公開鍵の配布口）',
    category: 'federation',
    short: '署名を確かめるための公開鍵を配る、標準的な入口です。秘密の鍵はここには載りません。',
    identifiers: ['JWKS'],
    see: ['federation'],
  },

  // ── 更新・お知らせ ─────────────────────────────────────────────────────────
  {
    id: 'origin',
    term: 'NeNe Origin（origin）',
    category: 'origin',
    short:
      '更新情報・お知らせ・広告を、署名付きで配る配信元です。Suite はそれを検証してから画面に出します。',
    identifiers: ['origin'],
  },

  // ── 運用・安全 ─────────────────────────────────────────────────────────────
  {
    id: 'audit-event',
    term: '監査イベント（audit event）',
    category: 'operation',
    short:
      'Suite 上の変更操作の記録です。変更前と変更後を残すので、誰が・何を・いつ変えたかを後から追えます。',
    identifiers: ['audit event'],
    see: ['install-manifest'],
  },
]
