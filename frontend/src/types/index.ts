// =============================================================================
// API Response Types
// =============================================================================

export interface ApiResponse<T> {
  success: boolean
  message: string
  data: T
}

export interface PaginatedResponse<T> {
  success: boolean
  message: string
  data: {
    data: T[]
    meta: PaginationMeta
    links: PaginationLinks
  }
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

export interface PaginationLinks {
  first: string | null
  last: string | null
  prev: string | null
  next: string | null
}

// =============================================================================
// Auth Types
// =============================================================================

export interface User {
  id: number
  uuid: string
  name: string
  email: string
  rfid_uid: string | null
  phone: string | null
  avatar: string | null
  is_active: boolean
  roles: string[]
  permissions: string[]
  created_at: string
  nim: string | null
}

export interface AuthResponse {
  user: User
  token: string
}

export interface LoginPayload {
  email: string
  password: string
}

export interface RegisterPayload {
  name: string
  email: string
  password: string
  password_confirmation: string
  phone?: string
}

// =============================================================================
// Lab Types
// =============================================================================

export interface Lab {
  id: number
  uuid: string
  name: string
  slug: string
  description: string | null
  is_active: boolean
  is_photography_lab: boolean
  branding: LabBranding
  contact: LabContact | null
  settings: Record<string, unknown> | null
  lab_rental_rates: LabRentalRates | null
  created_at: string
}

export interface LabRentalRates {
  student_price_per_hour: number
  public_price_per_hour: number
}

export interface LabBranding {
  primary_color: string | null
  secondary_color: string | null
  logo: string | null
  hero_image: string | null
  favicon: string | null
}

export interface LabContact {
  email: string | null
  phone: string | null
  address: string | null
}

export interface LabBranding {
  primary_color: string | null
  secondary_color: string | null
  logo: string | null
  hero_image: string | null
  favicon: string | null
}

export interface LabContact {
  email: string | null
  phone: string | null
  address: string | null
}

// =============================================================================
// Asset Types
// =============================================================================

export interface Asset {
  uuid: string
  lab_id: string
  name: string
  code: string
  category: EnumField
  brand: string | null
  model: string | null
  description: string | null
  serial_number: string | null
  status: EnumField
  specifications: Record<string, unknown> | null
  image: string | null
  is_rentable: boolean
  rental_price: string | null
  purchase_price: string | null
  purchase_date: string | null
  quantity: number
  created_at: string
}

// =============================================================================
// Shared Types
// =============================================================================

export interface EnumField {
  value: string
  label: string
}

export interface SelectOption {
  value: string
  label: string
}

export interface Booking {
  uuid: string
  booking_code: string
  lab_id: string
  user: {
    uuid: string
    name: string
    email: string
  }
  status: EnumField
  payment_status: EnumField
  checked_in_at: string | null
  photo_project: {
    uuid: string
    status: EnumField
  } | null
  start_time: string
  end_time: string
  total_price: string
  notes: string | null
  created_at: string
}

export interface AppNotification {
  uuid: string
  type: string
  title: string
  body: string | null
  data: Record<string, unknown> | null
  read_at: string | null
  created_at: string
}

export interface Service {
  uuid: string
  lab_id: number
  name: string
  type: EnumField
  description: string | null
  pricing_type: EnumField
  price: string
  duration: number | null
  min_quantity: number
  max_quantity: number | null
  includes: string[] | null
  image: string | null
  is_active: boolean
  created_at: string
}
