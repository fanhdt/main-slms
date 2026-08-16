import { describe, it, expect } from 'vitest'

/**
 * Fungsi ini diekstrak dari:
 * frontend/src/features/lab/components/LabFormModal.vue
 *
 * Kalau kamu mau test langsung dari komponen, pindahkan fungsi ini
 * ke file terpisah misalnya `src/utils/slug.ts` lalu import di sini
 * dan di LabFormModal.vue, supaya tidak duplikat logic.
 */
function generateSlug(name: string) {
  return name
    .toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-+|-+$/g, '') // hapus tanda hubung sisa di awal/akhir (trim() saja tidak cukup)
}

describe('generateSlug', () => {
  it('mengubah nama biasa menjadi slug huruf kecil dengan tanda hubung', () => {
    expect(generateSlug('Laboratorium Fotografi')).toBe('laboratorium-fotografi')
  })

  it('menghapus karakter spesial/simbol', () => {
    expect(generateSlug('Lab Fotografi & Multimedia!')).toBe('lab-fotografi-multimedia')
  })

  it('menggabungkan banyak spasi berturut-turut menjadi satu tanda hubung', () => {
    expect(generateSlug('Lab   Komputer   Dasar')).toBe('lab-komputer-dasar')
  })

  it('menggabungkan banyak tanda hubung berturut-turut menjadi satu', () => {
    expect(generateSlug('Lab--Fotografi---Studio')).toBe('lab-fotografi-studio')
  })

  it('menghapus spasi di awal dan akhir', () => {
    expect(generateSlug('  Lab Recording  ')).toBe('lab-recording')
  })

  it('mempertahankan angka', () => {
    expect(generateSlug('Studio 2 Lantai 3')).toBe('studio-2-lantai-3')
  })

  it('mengembalikan string kosong kalau input kosong', () => {
    expect(generateSlug('')).toBe('')
  })

  it('mengembalikan string kosong kalau input hanya berisi karakter spesial', () => {
    expect(generateSlug('!!!@@@###')).toBe('')
  })
})
