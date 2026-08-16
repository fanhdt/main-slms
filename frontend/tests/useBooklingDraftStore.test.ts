import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'

// Sesuaikan path ini kalau lokasi asli beda:
// frontend/src/features/booking/stores/useBookingDraftStore.ts
import {
  useBookingDraftStore,
  type BookingDraft,
} from '@/features/booking/stores/useBookingDraftStore'

const SLUG = 'lab-fotografi-a'

const sampleDraft: BookingDraft = {
  purpose: 'organization',
  nim: '1234567890',
  notes: 'Butuh lighting tambahan',
  schedule: { date: '2026-08-10', start: '09:00', end: '11:00', durationHours: 2 },
}

describe('useBookingDraftStore', () => {
  beforeEach(() => {
    // Store ini persist ke localStorage (key 'slms_booking_draft'), sama
    // seperti useCartStore — WAJIB di-clear tiap test, kalau tidak draft
    // dari test sebelumnya bakal kebawa dan bikin assertion salah.
    localStorage.clear()
    setActivePinia(createPinia())
  })

  it('mengembalikan objek kosong kalau belum ada draft tersimpan', () => {
    const store = useBookingDraftStore()
    expect(store.getDraft(SLUG, 'lab_rental')).toEqual({})
  })

  it('menyimpan draft dan bisa dibaca kembali dengan kombinasi slug + bookingType yang sama', () => {
    const store = useBookingDraftStore()
    store.saveDraft(SLUG, 'lab_rental', undefined, sampleDraft)

    expect(store.getDraft(SLUG, 'lab_rental')).toEqual(sampleDraft)
  })

  it('draft "lab_rental" dan "asset_rental" di lab yang sama tidak saling bentrok', () => {
    const store = useBookingDraftStore()
    store.saveDraft(SLUG, 'lab_rental', undefined, { purpose: 'academic' })
    store.saveDraft(SLUG, 'asset_rental', undefined, { rentalStartDate: '2026-08-01' })

    expect(store.getDraft(SLUG, 'lab_rental')).toEqual({ purpose: 'academic' })
    expect(store.getDraft(SLUG, 'asset_rental')).toEqual({ rentalStartDate: '2026-08-01' })
  })

  it('draft dua lab (slug) berbeda tidak saling bentrok walau bookingType sama', () => {
    const store = useBookingDraftStore()
    store.saveDraft('lab-a', 'lab_rental', undefined, { purpose: 'academic' })
    store.saveDraft('lab-b', 'lab_rental', undefined, { purpose: 'public' })

    expect(store.getDraft('lab-a', 'lab_rental')).toEqual({ purpose: 'academic' })
    expect(store.getDraft('lab-b', 'lab_rental')).toEqual({ purpose: 'public' })
  })

  it('draft untuk 2 layanan/paket berbeda (itemId beda) di bookingType "service" tidak bentrok', () => {
    const store = useBookingDraftStore()
    store.saveDraft(SLUG, 'service', 'service-uuid-1', { notes: 'Foto rapor' })
    store.saveDraft(SLUG, 'service', 'service-uuid-2', { notes: 'Foto prewedding' })

    expect(store.getDraft(SLUG, 'service', 'service-uuid-1')).toEqual({ notes: 'Foto rapor' })
    expect(store.getDraft(SLUG, 'service', 'service-uuid-2')).toEqual({ notes: 'Foto prewedding' })
  })

  it('saveDraft menimpa (overwrite) draft lama sepenuhnya, bukan merge', () => {
    const store = useBookingDraftStore()
    store.saveDraft(SLUG, 'lab_rental', undefined, {
      purpose: 'organization',
      nim: '111',
      notes: 'Catatan lama',
    })

    // Simpan ulang tanpa field 'notes' dan 'nim'
    store.saveDraft(SLUG, 'lab_rental', undefined, { purpose: 'public' })

    // Field lama seharusnya HILANG, bukan tetap ada (karena implementasi
    // pakai `{ ...data }`, bukan merge dengan draft sebelumnya)
    expect(store.getDraft(SLUG, 'lab_rental')).toEqual({ purpose: 'public' })
  })

  it('clearDraft menghapus draft spesifik tanpa mempengaruhi draft lain', () => {
    const store = useBookingDraftStore()
    store.saveDraft(SLUG, 'lab_rental', undefined, { purpose: 'academic' })
    store.saveDraft(SLUG, 'asset_rental', undefined, { rentalStartDate: '2026-08-01' })

    store.clearDraft(SLUG, 'lab_rental')

    expect(store.getDraft(SLUG, 'lab_rental')).toEqual({})
    expect(store.getDraft(SLUG, 'asset_rental')).toEqual({ rentalStartDate: '2026-08-01' })
  })

  it('clearDraft pada key yang tidak pernah ada tidak menyebabkan error', () => {
    const store = useBookingDraftStore()
    expect(() => store.clearDraft(SLUG, 'lab_rental')).not.toThrow()
  })

  it('draft benar-benar tersimpan ke localStorage, bukan cuma di memory', async () => {
    const store = useBookingDraftStore()
    store.saveDraft(SLUG, 'lab_rental', undefined, sampleDraft)

    // watch({ deep: true }) berjalan async (microtask), tunggu sebentar
    await new Promise((resolve) => setTimeout(resolve, 0))

    const saved = JSON.parse(localStorage.getItem('slms_booking_draft') ?? '{}')
    expect(saved[`${SLUG}:lab_rental`]).toEqual(sampleDraft)
  })

  it('draft yang sudah tersimpan di localStorage otomatis ter-restore saat store dibuat ulang', async () => {
    const storeA = useBookingDraftStore()
    storeA.saveDraft(SLUG, 'service', 'pkg-uuid-1', { notes: 'Draft sebelum reload' })
    await new Promise((resolve) => setTimeout(resolve, 0))

    // Simulasikan reload halaman: buat Pinia instance baru, store baru
    // dibuat dari nol, satu-satunya sumber data adalah localStorage
    setActivePinia(createPinia())
    const storeB = useBookingDraftStore()

    expect(storeB.getDraft(SLUG, 'service', 'pkg-uuid-1')).toEqual({
      notes: 'Draft sebelum reload',
    })
  })
})
