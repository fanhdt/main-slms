import { useRouter } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'

/**
 * Logout manual dari semua layout/navbar.
 * Setelah state auth dihapus, user diarahkan ke landing page publik
 * (bukan /login). `replace` dipakai supaya tombol Back tidak kembali
 * ke halaman terproteksi. Logout otomatis kios memakai alur sendiri
 * di useKioskSession dan tidak lewat sini.
 */
export function useLogout() {
  const router = useRouter()
  const authStore = useAuthStore()

  return async function logout() {
    await authStore.logout()
    await router.replace({ name: 'home' })
  }
}
