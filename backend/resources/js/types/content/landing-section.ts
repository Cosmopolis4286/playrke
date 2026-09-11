export type LandingSectionKey =
  | 'hero'
  | 'jackpot'
  | 'salas'
  | 'verificar'
  | 'invita'
  | 'ganadores'
  | 'cta_final'
  | 'footer'

export interface HeroContent {
  eyebrowPrefix: string
  eyebrowSuffix: string
  title: string
  subtitle: string
  ctaPrimaryLabel: string
  ctaSecondaryLabel: string
  trustItems: [string, string, string]
}

export type JackpotContent = Record<string, never>

export interface SalasContent {
  title: string
  subtitle: string
  ctaLabel: string
}

export interface VerificarContent {
  eyebrow: string
  title: string
  paragraph: string
  steps: [string, string, string]
}

export interface InvitaContent {
  eyebrow: string
  title: string
  paragraph: string
  steps: [string, string, string]
  cardLead: string
  ctaLabel: string
}

export interface GanadoresContent {
  eyebrow: string
  title: string
  paragraph: string
}

export interface CtaFinalContent {
  eyebrow: string
  title: string
  paragraph: string
  ctaLabel: string
}

export interface FooterContent {
  tagline: string
  socials: {
    facebook: string
    instagram: string
    telegram: string
  }
  copyrightSuffix: string
  trustLine: string
}

export interface LandingSectionContentMap {
  hero: HeroContent
  jackpot: JackpotContent
  salas: SalasContent
  verificar: VerificarContent
  invita: InvitaContent
  ganadores: GanadoresContent
  cta_final: CtaFinalContent
  footer: FooterContent
}

/** Fila completa tal como la ve el admin (CRUD, drag-and-drop, toggle). */
export interface LandingSectionAdmin<K extends LandingSectionKey = LandingSectionKey> {
  id: string
  section_key: K
  label: string
  content: LandingSectionContentMap[K]
  sort_order: number
  status: 'ACTIVE' | 'INACTIVE'
  is_removable: boolean
}

/** Shape mínimo que recibe la Landing pública: solo key + content. */
export type LandingSectionPublic<K extends LandingSectionKey = LandingSectionKey> = {
  [P in K]: { key: P; content: LandingSectionContentMap[P] }
}[K]
