import api from '@/lib/axios'
import type { ApiResponse, PaginatedResponse } from '@/types'
import type { PhotoProject, PhotoFile } from '../types'

export const photoApi = {
  getAll(params?: Record<string, unknown>) {
    return api.get<PaginatedResponse<PhotoProject>>('/photo-projects', { params })
  },

  getByUuid(uuid: string) {
    return api.get<ApiResponse<PhotoProject>>(`/photo-projects/${uuid}`)
  },

  create(data: { booking_uuid: string; max_selection?: number }) {
    return api.post<ApiResponse<PhotoProject>>('/photo-projects', data)
  },

  updateMaxSelection(uuid: string, max_selection: number) {
    return api.patch(`/photo-projects/${uuid}/max-selection`, { max_selection })
  },

  uploadPreviews(uuid: string, files: File[]) {
    const formData = new FormData()
    files.forEach((f) => formData.append('files[]', f))
    return api.post<ApiResponse<PhotoFile[]>>(`/photo-projects/${uuid}/previews`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  uploadEdited(uuid: string, files: File[]) {
    const formData = new FormData()
    files.forEach((f) => formData.append('files[]', f))
    return api.post<ApiResponse<PhotoFile[]>>(`/photo-projects/${uuid}/edited`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  submitSelection(uuid: string, photo_file_uuids: string[], customer_note?: string) {
    return api.post<ApiResponse<PhotoProject>>(`/photo-projects/${uuid}/selection`, {
      photo_file_uuids,
      customer_note,
    })
  },

  submitForApproval(uuid: string) {
    return api.post<ApiResponse<PhotoProject>>(`/photo-projects/${uuid}/submit-approval`)
  },

  resolveApproval(uuid: string, decision: 'approve' | 'revise', revision_note?: string) {
    return api.post<ApiResponse<PhotoProject>>(`/photo-projects/${uuid}/approval`, {
      decision,
      revision_note,
    })
  },

  downloadFile(uuid: string, fileUuid: string) {
    return api.get<ApiResponse<{ url: string; filename: string }>>(
      `/photo-projects/${uuid}/files/${fileUuid}/download`,
    )
  },
}
