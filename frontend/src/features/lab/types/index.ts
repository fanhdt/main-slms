export interface CreateLabForm {
  name: string
  slug: string
  description: string
  primary_color: string
  secondary_color: string
  is_active: boolean
  contact: {
    email: string
    phone: string
    address: string
  }
}

export interface UpdateLabForm extends Partial<CreateLabForm> {}

export const defaultCreateLabForm = (): CreateLabForm => ({
  name: '',
  slug: '',
  description: '',
  primary_color: '#1a1a2e',
  secondary_color: '#e94560',
  is_active: true,
  contact: {
    email: '',
    phone: '',
    address: '',
  },
})
