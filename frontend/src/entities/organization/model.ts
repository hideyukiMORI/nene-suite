export interface Organization {
  id: string
  externalId: string
  name: string
  slug: string
  status: 'active' | 'disabled'
}
