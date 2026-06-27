import type { SuiteAuditEvent } from '@/entities/suite-audit-event'

export type ChangeKind = 'create' | 'update' | 'delete'

export function classifyChange(event: Pick<SuiteAuditEvent, 'before' | 'after'>): ChangeKind {
  if (event.before === null && event.after !== null) return 'create'
  if (event.before !== null && event.after === null) return 'delete'
  return 'update'
}

/** Material Symbols icon per change kind. */
export const CHANGE_ICON: Readonly<Record<ChangeKind, string>> = {
  create: 'add_circle',
  update: 'change_circle',
  delete: 'remove_circle',
}

/** Sign glyph — always paired with a text label, never color alone. */
export const CHANGE_SIGN: Readonly<Record<ChangeKind, string>> = {
  create: '＋',
  update: '～',
  delete: '−',
}

const ENTITY_ICON: Readonly<Record<string, string>> = {
  install_session: 'deployed_code',
  app_selection: 'apps',
  disclaimer_acknowledgment: 'verified_user',
  suite_env_config: 'tune',
  app_database: 'database',
  integration_wiring: 'cable',
  install_manifest: 'receipt_long',
  suite_org_profile: 'badge',
  catalog_pin: 'push_pin',
  apex_operator: 'shield_person',
  organization: 'corporate_fare',
  membership: 'group',
  federation_signing_key: 'key',
}

export function entityIcon(entityType: string): string {
  return ENTITY_ICON[entityType] ?? 'history'
}

const SOURCE_ICON: Readonly<Record<string, string>> = {
  installer_ui: 'install_desktop',
  installer_cli: 'terminal',
  apex_admin: 'admin_panel_settings',
  system: 'smart_toy',
  api: 'api',
}

export function sourceIcon(source: string): string {
  return SOURCE_ICON[source] ?? 'bolt'
}

/** Japanese gloss per action (content; English shows no gloss — mirrors the ADR 0024 posture). */
const ACTION_GLOSS_JA: Readonly<Record<string, string>> = {
  'organization.renamed': '組織名を変更',
  'organization.created': '組織を作成',
  'organization.disabled': '組織を無効化',
  'database_targets.configured': 'DB割り当てを設定',
  'membership.granted': 'メンバーを追加',
  'membership.revoked': 'メンバーを解除',
  'membership.role_changed': 'メンバーのロールを変更',
  'app_selection.changed': 'アプリ選択を変更',
  'install_session.started': 'インストール開始',
  'install_session.completed': 'インストール完了',
  'install_session.failed': 'インストール失敗',
  'manifest.created': 'マニフェストを作成',
  'env_config.written': '環境設定を書き込み',
  'federation_signing_key.generated': '署名鍵を生成',
  'federation_signing_key.rotated': '署名鍵をローテーション',
  'federation_signing_key.revoked': '署名鍵を失効',
  'disclaimer.accepted': '免責事項に同意',
}

/** Japanese gloss for an action, or null when none is registered / locale is not ja. */
export function actionGloss(action: string, locale: string): string | null {
  if (locale !== 'ja') return null
  return ACTION_GLOSS_JA[action] ?? null
}

export function shortId(value: string): string {
  if (value.length <= 16) return value
  return `${value.slice(0, 9)}…${value.slice(-4)}`
}

export function absoluteTime(iso: string): string {
  const date = new Date(iso)
  const pad = (n: number): string => String(n).padStart(2, '0')
  return `${String(date.getUTCFullYear())}-${pad(date.getUTCMonth() + 1)}-${pad(date.getUTCDate())} ${pad(date.getUTCHours())}:${pad(date.getUTCMinutes())} UTC`
}

export function relativeTime(iso: string, locale: string, now: number = Date.now()): string {
  const minutes = Math.floor(Math.max(0, now - new Date(iso).getTime()) / 60000)
  const ja = locale === 'ja'
  if (minutes < 1) return ja ? 'たった今' : 'just now'
  if (minutes < 60) return ja ? `${String(minutes)}分前` : `${String(minutes)}m ago`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return ja ? `${String(hours)}時間前` : `${String(hours)}h ago`
  const days = Math.floor(hours / 24)
  return ja ? `${String(days)}日前` : `${String(days)}d ago`
}
