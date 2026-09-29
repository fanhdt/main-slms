import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

declare global {
  interface Window {
    Pusher: typeof Pusher
    Echo: Echo<'reverb'>
  }
}

window.Pusher = Pusher

let echoInstance: Echo<'reverb'> | null = null

/**
 * Inisialisasi (atau reuse) koneksi Echo ke Reverb.
 * Dipanggil setelah user login / token tersedia, karena
 * otorisasi private channel butuh Authorization header.
 */
export function initEcho(token: string): Echo<'reverb'> {
  if (echoInstance) {
    return echoInstance
  }

  echoInstance = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: 80,
    wssPort: 443,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: `${import.meta.env.VITE_API_BASE_URL ?? 'http://localhost/api/v1'}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    },
  })

  window.Echo = echoInstance

  return echoInstance
}

export function disconnectEcho(): void {
  echoInstance?.disconnect()
  echoInstance = null
}

export function getEcho(): Echo<'reverb'> | null {
  return echoInstance
}
