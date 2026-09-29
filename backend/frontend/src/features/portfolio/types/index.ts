export interface Photographer {
  id: number
  uuid: string
  lab_id: number
  name: string
  photo: string | null
  bio: string | null
  instagram: string | null
  order: number
  is_active: boolean
}

export interface PhotographerPortfolio {
  uuid: string
  lab_id: number
  photographer_id: number
  photographer?: { uuid: string; name: string } | null
  image: string
  caption: string | null
  order: number
  created_at: string
}
