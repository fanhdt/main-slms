// src/composables/usePendingBookingRedirect.ts
//
// Dipakai untuk menyimpan "tujuan booking" yang sedang diproses user di
// landing page (misalnya jadwal yang sudah dipilih di kalender) sebelum
// mereka diarahkan ke halaman login. Setelah login berhasil, target ini
// dibaca lagi supaya user langsung diarahkan ke form booking yang sama,
// bukan ke dashboard/beranda seperti alur login biasa.
//
// Pakai sessionStorage (bukan localStorage) karena ini sinyal sekali-pakai
// yang hanya relevan untuk sesi tab saat ini — sama seperti pola di
// useKioskSession.ts, bukan data yang perlu bertahan lama seperti cart
// (useCartStore) atau draft form (useBookingDraftStore).

import type { RouteLocationNamedRaw } from 'vue-router'

const STORAGE_KEY = 'slms_pending_booking_redirect'

/**
 * Simpan target redirect sebelum mengarahkan user ke halaman login.
 */
export function setPendingBookingRedirect(target: RouteLocationNamedRaw) {
  try {
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(target))
  } catch {
    // Abaikan jika sessionStorage tidak tersedia (mis. private mode penuh)
  }
}

/**
 * Ambil & hapus target redirect yang tersimpan (sekali pakai).
 * Return null kalau tidak ada target yang tersimpan.
 */
export function consumePendingBookingRedirect(): RouteLocationNamedRaw | null {
  try {
    const raw = sessionStorage.getItem(STORAGE_KEY)
    if (!raw) return null
    sessionStorage.removeItem(STORAGE_KEY)
    return JSON.parse(raw) as RouteLocationNamedRaw
  } catch {
    return null
  }
}
