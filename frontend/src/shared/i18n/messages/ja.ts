import type { MessageCatalog } from './en'

/** Japanese — full coverage required (enforced by locales.test.ts). */
export const ja: Partial<MessageCatalog> = {
  'common.actions.cancel': 'キャンセル',
  'common.actions.confirm': '確認',
  'common.actions.retry': '再試行',
  'common.actions.back': '戻る',
  'common.actions.next': '次へ',
  'common.actions.finish': '完了',
  'common.actions.close': '閉じる',
  'common.actions.save': '保存',
  'common.actions.loading': '読み込み中…',

  'common.state.loading': '読み込み中…',
  'common.state.empty': '表示する項目がありません。',
  'common.state.error': 'エラーが発生しました。',

  'common.error.unknown': '不明なエラー',
  'common.error.unauthorized': '認証が必要です。サインインしてください。',
  'common.error.forbidden': 'この操作を行う権限がありません。',
  'common.error.notFound': 'リソースが見つかりません。',
  'common.error.conflict': '競合が発生しました。リソースが既に存在する可能性があります。',
  'common.error.validation': '送信されたデータが無効です。',
  'common.error.rateLimit': 'リクエストが多すぎます。しばらく待ってから再試行してください。',
  'common.error.serverError': 'サーバーエラーが発生しました。後でもう一度お試しください。',

  'common.dialog.close': 'ダイアログを閉じる',

  'suite.locale.label': '言語',
  'suite.locale.select': '言語を選択',

  'suite.nav.home': 'ホーム',
  'suite.nav.install': 'アプリをインストール',
  'suite.nav.audit': '監査ログ',
  'suite.nav.settings': '設定',
  'suite.nav.logout': 'ログアウト',
  'suite.nav.openMenu': 'ナビゲーションメニューを開く',
  'suite.nav.closeMenu': 'ナビゲーションメニューを閉じる',
  'suite.nav.appTitle': 'NeNe Suite',
  'suite.nav.organizations': '組織',

  'suite.auth.subtitle': 'NeNe インストールを管理するにはサインインしてください',
  'suite.auth.emailLabel': 'メールアドレス',
  'suite.auth.emailPlaceholder': 'operator@example.com',
  'suite.auth.passwordLabel': 'パスワード',
  'suite.auth.passwordPlaceholder': '••••••••',
  'suite.auth.signIn': 'サインイン',
  'suite.auth.signingIn': 'サインイン中…',
  'suite.auth.invalidCredentials': 'メールアドレスまたはパスワードが正しくありません',

  'suite.launcher.title': 'インストール済みアプリケーション',
  'suite.launcher.description': 'NeNe Suite 経由でインストールしたアプリを開きます。',
  'suite.launcher.empty.title': 'アプリがインストールされていません',
  'suite.launcher.empty.description':
    'インストーラーを実行して NeNe Invoice、Clear、Records などを追加してください。',
  'suite.launcher.openApp': '{{appName}} を開く',
  'suite.launcher.ssotBilling': '請求の正本（SSOT）',
  'suite.launcher.ssotEvidence': '消込エビデンス',
  'suite.launcher.ssotCms': '柔軟なコンテンツ基盤',
  'suite.launcher.ssotArchive': 'ドキュメントアーカイブ',
  'suite.launcher.startInstall': 'インストーラーを開始',

  'suite.install.wizard.title': 'NeNe Suite インストーラー',
  'suite.install.wizard.step.apps': 'アプリ選択',
  'suite.install.wizard.step.disclaimer': '免責事項',
  'suite.install.wizard.step.review': '確認',
  'suite.install.wizard.step.complete': '完了',

  'suite.install.apps.title': 'インストールするアプリを選択',
  'suite.install.apps.description':
    '各アプリは独立したデータベースを使用します。依存関係は正しい順序でインストールされます。',
  'suite.install.apps.selectedCount': '{{count}} 件のアプリを選択中',
  'suite.install.apps.requiredBy': '{{appName}} に必要',
  'suite.install.apps.status.planned': '準備中',
  'suite.install.apps.status.installable': '利用可能',
  'suite.install.apps.status.deprecated': '非推奨',
  'suite.install.apps.empty': 'カタログにインストール可能なアプリがありません。',

  'suite.disclaimer.title': '重要なお知らせ',
  'suite.disclaimer.shortNotice':
    'NeNe Suite はアプリケーションのインストールと環境設定を支援するソフトウェアです。業務の結果、法令遵守、会計・税務の正確性を保証するものではありません。インストールされた各アプリの利用方法および必要に応じた専門家への確認は、利用者ご自身の責任となります。',
  'suite.disclaimer.checkbox':
    'NeNe Suite が技術的なインストール支援のみを提供し、税務・法務・会計上の適合を保証しないことを理解しました。',
  'suite.disclaimer.mustAccept': '続行するには免責事項への同意が必要です。',
  'suite.disclaimer.link': '免責事項全文を読む',

  'suite.install.review.title': '設定内容の確認',
  'suite.install.review.description':
    'プロビジョニング前に、選択アプリ・データベース・連携設定を確認してください。',
  'suite.install.review.orgName': '組織名',
  'suite.install.review.selectedApps': '選択したアプリ',
  'suite.install.review.integrations.title': 'HTTP 連携',
  'suite.install.review.integrations.clearToInvoice':
    'Clear → Invoice（サービス API・明示的な有効化が必要）',
  'suite.install.review.integrations.none': '有効な連携なし',
  'suite.install.review.preCompleteSummary':
    'セットアップを完了しようとしています。NeNe Suite は設定ファイルの書き込みとデータベースのプロビジョニングを行います。**これは**組織が税務・会計・業界規則を満たすことの**証明にはなりません**。',

  'suite.install.complete.title': 'インストール完了',
  'suite.install.complete.description':
    '設定が書き込まれました。apex シェルからインストール済みアプリを起動できます。',
  'suite.install.complete.goToLauncher': 'アプリランチャーへ',

  'suite.install.errors.sessionFailed': 'インストールを完了できませんでした。',
  'suite.install.errors.dependencyMissing':
    '選択したアプリの依存関係が満たされていません。選択を見直してください。',
  'suite.install.errors.disclaimerRequired': '続行するには免責事項への同意が必要です。',

  'suite.disclaimer.footer':
    'セットアップのオーケストレーションのみ — 業務・法務上の保証はありません。',

  'suite.audit.title': 'オーケストレーション監査ログ',
  'suite.audit.description':
    'Suite 設定変更の履歴（変更前後のスナップショット）。ドメイン監査は各アプリに残ります。',
  'suite.audit.export': 'エクスポート',
  'suite.audit.empty': '監査イベントはまだありません。',
  'suite.audit.column.action': '操作',
  'suite.audit.column.actor': '実行者',
  'suite.audit.column.time': '日時',

  // ── 組織（スーパー管理者コンソール） ──────────────────────────────────────
  'suite.org.title': '組織',
  'suite.org.description': 'テナント組織を管理します。プラットフォームのスーパー管理者専用。',
  'suite.org.empty': '組織はまだありません。',
  'suite.org.column.name': '名前',
  'suite.org.column.slug': 'スラッグ',
  'suite.org.column.status': '状態',
  'suite.org.column.actions': '操作',
  'suite.org.status.active': '有効',
  'suite.org.status.disabled': '無効',
  'suite.org.create.title': '組織を作成',
  'suite.org.create.nameLabel': '名前',
  'suite.org.create.namePlaceholder': 'Acme KK',
  'suite.org.create.slugLabel': 'スラッグ',
  'suite.org.create.slugPlaceholder': 'acme-kk',
  'suite.org.create.submit': '作成',
  'suite.org.create.submitting': '作成中…',
  'suite.org.rename.action': '改名',
  'suite.org.rename.nameLabel': '新しい名前',
  'suite.org.rename.submitting': '改名中…',
  'suite.org.disable.action': '無効化',
  'suite.org.members': 'メンバー',
  'suite.org.error.slugConflict': 'このスラッグの組織は既に存在します。',
  'suite.org.error.validation': '組織の名前とスラッグを確認してください。',
  'suite.org.error.notFound': 'その組織は既に存在しません。',
  'suite.org.indicator.superadmin': 'スーパー管理者',
  'suite.org.indicator.activeOrg': '組織: {{org}}',
  'suite.org.indicator.noOrg': 'アクティブな組織なし',
  'suite.org.indicator.role': 'ロール: {{role}}',
  'suite.org.switcher.label': 'アクティブな組織',
  'suite.org.switcher.placeholder': '組織を選択',

  // ── メンバー（スーパー管理者コンソール） ──────────────────────────────────
  'suite.member.title': 'メンバー',
  'suite.member.description': 'この組織に所属するメンバーとそのロールを管理します。',
  'suite.member.empty': 'メンバーはまだいません。',
  'suite.member.column.operator': 'オペレーター',
  'suite.member.column.role': 'ロール',
  'suite.member.column.actions': '操作',
  'suite.member.role.admin': '管理者',
  'suite.member.role.member': 'メンバー',
  'suite.member.role.viewer': '閲覧者',
  'suite.member.grant.title': 'メンバーを追加',
  'suite.member.grant.operatorLabel': 'オペレーター',
  'suite.member.grant.operatorPlaceholder': 'オペレーターを選択',
  'suite.member.grant.noOperators': '追加できるオペレーターがいません。',
  'suite.member.grant.roleLabel': 'ロール',
  'suite.member.grant.submit': '追加',
  'suite.member.grant.submitting': '追加中…',
  'suite.member.stale': '削除済みオペレーター（{{operatorId}}）',
  'suite.member.revoke.action': '削除',
  'suite.member.error.conflict': 'このオペレーターは既に組織のメンバーです。',
  'suite.member.error.invariant': '組織には少なくとも 1 人の管理者が必要です。',
  'suite.member.error.validation': 'オペレーターとロールを確認してください。',
  'suite.member.error.notFound': 'そのメンバーシップは既に存在しません。',
}
