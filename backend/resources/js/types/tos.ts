export interface TosVersion {
  version: string
  title: string
  summary: string | null
  effective_at: string
}

export interface TosStatus {
  version: string | null
  title: string | null
  summary: string | null
  effective_at: string | null
  needs_acceptance: boolean
  accepted_version: string | null
  accepted_at: string | null
}
