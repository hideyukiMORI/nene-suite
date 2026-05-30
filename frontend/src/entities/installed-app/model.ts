export type SsotRole = 'billing' | 'reconciliation_evidence' | 'cms' | 'archive' | 'none'

export interface InstalledApp {
  catalogId: string
  name: string
  publicUrl: string
  databaseName: string | null
  ssotRole: SsotRole
}
