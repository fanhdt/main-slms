import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export interface BookingDraft {
  purpose?: 'academic' | 'organization' | 'public'
  nim?: string
  notes?: string
  schedule?: { date: string; start: string; end: string; durationHours: number } | null
  rentalStartDate?: string
  rentalEndDate?: string
}

const STORAGE_KEY = 'slms_booking_draft'

function loadFromStorage(): Record<string, BookingDraft> {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '{}')
  } catch {
    return {}
  }
}

/**
 * Menyimpan draft form booking (bukan draft keranjang alat — itu sudah
 * ditangani useCartStore) supaya kalau user pindah halaman (misal balik
 * dari checkout untuk mengubah pilihan) lalu kembali lagi, isian form
 * seperti jadwal, keperluan, NIM, catatan, dan rentang tanggal sewa tidak
 * hilang dan tidak perlu diinput ulang dari awal.
 *
 * Draft di-key per kombinasi lab + tipe booking (+ item spesifik untuk
 * jasa/paket), supaya draft "Pinjam Lab" tidak bentrok dengan draft
 * "Sewa Alat" walau di lab yang sama.
 */
export const useBookingDraftStore = defineStore('bookingDraft', () => {
  const drafts = ref<Record<string, BookingDraft>>(loadFromStorage())

  watch(
    drafts,
    (value) => {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(value))
    },
    { deep: true },
  )

  function draftKey(slug: string, bookingType: string, itemId?: string): string {
    return `${slug}:${bookingType}${itemId ? ':' + itemId : ''}`
  }

  function getDraft(slug: string, bookingType: string, itemId?: string): BookingDraft {
    return drafts.value[draftKey(slug, bookingType, itemId)] ?? {}
  }

  function saveDraft(
    slug: string,
    bookingType: string,
    itemId: string | undefined,
    data: BookingDraft,
  ) {
    drafts.value[draftKey(slug, bookingType, itemId)] = { ...data }
  }

  function clearDraft(slug: string, bookingType: string, itemId?: string) {
    delete drafts.value[draftKey(slug, bookingType, itemId)]
  }

  return {
    drafts,
    getDraft,
    saveDraft,
    clearDraft,
  }
})
