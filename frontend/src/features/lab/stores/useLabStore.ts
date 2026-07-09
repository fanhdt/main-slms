import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Lab } from '@/types'
import { labApi } from '@/features/lab/api/labApi'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { toast } from 'vue-sonner'
import api from '@/lib/axios'

export const useLabStore = defineStore('lab', () => {
  const activeLab = ref<Lab | null>(null)
  const labs = ref<Lab[]>([])
  const managedLabs = ref<Lab[]>([])
  const loading = ref(false)

  const branding = computed(() => activeLab.value?.branding ?? null)
  const primaryColor = computed(() => branding.value?.primary_color ?? '#3b82f6')
  const secondaryColor = computed(() => branding.value?.secondary_color ?? '#e94560')

  async function fetchLabs() {
    loading.value = true
    try {
      const res = await labApi.getAll({ per_page: 100, is_active: true })
      labs.value = res.data.data.data
    } finally {
      loading.value = false
    }
  }

  /**
   * Daftar lab yang user PUNYA AKSES ADMIN atasnya — SATU-SATUNYA sumber
   * kebenaran untuk otorisasi (router guard requiresLabAccess) dan sidebar
   * "masuk ke lab" di admin layout. Jangan pernah dicampur dengan fetchLabs().
   */
  async function fetchManagedLabs() {
    loading.value = true
    const authStore = useAuthStore()
    try {
      if (authStore.hasRole('super_admin')) {
        const res = await labApi.getAll({ per_page: 100 })
        managedLabs.value = res.data.data.data
      } else {
        if (!authStore.user?.uuid) {
          console.warn('[fetchManagedLabs] authStore.user belum ter-load, skip fetch.')
          return
        }
        const res = await api.get(`/users/${authStore.user?.uuid}/labs`)
        managedLabs.value = res.data.data.data
      }
    } catch (err: any) {
      console.error('[fetchManagedLabs] gagal:', err)
      toast.error('Gagal memuat daftar lab: ' + (err.response?.data?.message ?? err.message))
    } finally {
      loading.value = false
    }
  }

  async function setActiveLab(slug: string) {
    loading.value = true
    try {
      const res = await labApi.getBySlug(slug)
      activeLab.value = res.data.data
      applyBranding()
      localStorage.setItem('active_lab', slug)
    } finally {
      loading.value = false
    }
  }

  function applyBranding() {
    if (!activeLab.value) return
    const root = document.documentElement
    const { primary_color, secondary_color } = activeLab.value.branding
    if (primary_color) root.style.setProperty('--color-primary', primary_color)
    if (secondary_color) root.style.setProperty('--color-secondary', secondary_color)
  }

  async function restoreFromStorage() {
    const slug = localStorage.getItem('active_lab')
    if (slug) await setActiveLab(slug)
  }

  const labName = computed(() => activeLab.value?.name ?? 'SLMS')

  return {
    activeLab,
    labs,
    managedLabs,
    loading,
    branding,
    primaryColor,
    secondaryColor,
    labName,
    fetchLabs,
    fetchManagedLabs,
    setActiveLab,
    applyBranding,
    restoreFromStorage,
  }
})
