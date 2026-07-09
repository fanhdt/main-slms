import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { useRouter } from 'vue-router'

const KIOSK_FLAG_KEY = 'kiosk_session_lab_slug'
const IDLE_TIMEOUT_MS = 3 * 60 * 1000 // 3 menit tanpa aktivitas -> auto logout

let idleTimer: ReturnType<typeof setTimeout> | null = null
let listenersAttached = false

/**
 * Dipanggil sekali begitu tap kartu berhasil di RfidKioskPage,
 * menandai sesi ini sebagai sesi kios (bukan login manual biasa).
 */
export function markKioskSession(labSlug: string) {
  sessionStorage.setItem(KIOSK_FLAG_KEY, labSlug)
}

export function isKioskSession(): string | null {
  return sessionStorage.getItem(KIOSK_FLAG_KEY)
}

export function clearKioskSession() {
  sessionStorage.removeItem(KIOSK_FLAG_KEY)
}

/**
 * Dipasang sekali di App.vue. Memantau aktivitas user (klik, ketik, sentuh)
 * di seluruh aplikasi. Kalau sesi ini ditandai sebagai kios DAN idle melebihi
 * batas waktu, otomatis logout dan kembali ke halaman tap kartu.
 */
export function useKioskIdleWatcher() {
  const authStore = useAuthStore()
  const router = useRouter()

  function resetTimer() {
    const labSlug = isKioskSession()
    if (!labSlug) return // bukan sesi kios, tidak perlu auto-logout

    if (idleTimer) clearTimeout(idleTimer)
    idleTimer = setTimeout(async () => {
      authStore.user = null
      authStore.token = null
      localStorage.removeItem('token')
      clearKioskSession()
      router.push({ name: 'lab-rfid-kiosk', params: { slug: labSlug } })
    }, IDLE_TIMEOUT_MS)
  }

  function attachListeners() {
    if (listenersAttached) return
    listenersAttached = true
    ;['click', 'keydown', 'touchstart', 'mousemove'].forEach((event) => {
      window.addEventListener(event, resetTimer, { passive: true })
    })
    resetTimer()
  }

  attachListeners()
}
