export interface PhotoFile {
  uuid: string
  type: { value: 'preview' | 'edited' | 'final'; label: string }
  filename: string
  url: string
  size: number
  mime_type: string | null
  is_selected: boolean
  order: number
  created_at: string
}

export interface PhotoProject {
  uuid: string
  booking: {
    uuid: string
    booking_code: string
    user: { uuid: string; name: string }
  }
  lab_id: string
  status: { value: string; label: string }
  preview_count: number
  selection_count: number
  max_selection: number
  notes: string | null
  customer_note: string | null
  editor_note: string | null
  expires_at: string | null
  is_expired: boolean
  previews: PhotoFile[]
  edited_files: PhotoFile[]
  files: PhotoFile[]
  created_at: string
}
