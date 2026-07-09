import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

/**
 * Satu sumber kebenaran untuk membedakan booking flow customer vs staff.
 * Staff masuk lewat query `?mode=staff` (di-set sekali dari tombol
 * "Buat Booking Manual" di LabDashboardPage), lalu di-forward otomatis
 * ke setiap navigasi berikutnya dalam flow (catalog -> cart -> form),
 * supaya admin tidak keluar context sampai booking selesai dibuat.
 */
export function useBookingFlowMode() {
  const route = useRoute()
  const router = useRouter()

  const isStaffMode = computed(() => route.query.mode === 'staff')

  // Bungkus query navigasi berikutnya, sisipkan mode=staff kalau lagi staff mode
  function withMode(query: Record<string, any> = {}) {
    return isStaffMode.value ? { ...query, mode: 'staff' } : query
  }

  function goBack(labSlug: string) {
    if (isStaffMode.value) {
      router.push({ name: 'lab-bookings', params: { labSlug } })
    } else {
      router.back()
    }
  }

  return { isStaffMode, withMode, goBack }
}
