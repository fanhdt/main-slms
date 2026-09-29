export interface CreateServiceForm {
  name: string
  type: string
  description: string
  pricing_type: string
  price: string
  duration: string
  min_quantity: number
  max_quantity: string
  includes_text: string
  is_active: boolean
  lab_id: number
  imagePreview: string | null
}

export const defaultCreateServiceForm = (labId: number): CreateServiceForm => ({
  name: '',
  type: 'photography',
  description: '',
  pricing_type: 'per_session',
  price: '',
  duration: '',
  min_quantity: 1,
  max_quantity: '',
  includes_text: '',
  is_active: true,
  lab_id: labId,
  imagePreview: null,
})
