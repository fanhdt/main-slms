export interface CreateLabForm {
  name: string
  slug: string
  description: string
  primary_color: string
  secondary_color: string
  is_active: boolean
  student_price_per_hour: number | null
  public_price_per_hour: number | null
  contact: {
    email: string
    phone: string
    address: string
  }
  logoPreview: string | null
  heroPreview: string | null
}

export interface UpdateLabForm extends Partial<CreateLabForm> {}

export const defaultCreateLabForm = (): CreateLabForm => ({
  name: '',
  slug: '',
  description: '',
  primary_color: '#1a1a2e',
  secondary_color: '#e94560',
  is_active: true,
  student_price_per_hour: 0,
  public_price_per_hour: 0,
  contact: {
    email: '',
    phone: '',
    address: '',
  },
  logoPreview: null,
  heroPreview: null,
})
