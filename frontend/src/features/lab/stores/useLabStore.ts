import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Lab } from '@/types'
import { labApi } from '@/features/lab/api/labApi'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import api from '@/lib/axios'

export const useLabStore = defineStore('lab', () => {
  const activeLab = ref<Lab | null>(null)
  const labs = ref<Lab[]>([])
  const loading = ref(false)

  const branding = computed(() => activeLab.value?.branding ?? null)
  const primaryColor = computed(() => branding.value?.primary_color ?? '#3b82f6')
  const secondaryColor = computed(() => branding.value?.secondary_color ?? '#e94560')

  async function fetchLabs() {
    loading.value = true
    const authStore = useAuthStore()

    try {
      if (authStore.hasRole('super_admin')) {
        // Super admin lihat semua lab
        const res = await labApi.getAll({ per_page: 100 })
        labs.value = res.data.data.data
      } else if (authStore.hasRole('customer') || authStore.hasRole('guest')) {
        // Customer lihat semua lab aktif (public)
        const res = await labApi.getAll({ per_page: 100, is_active: true })
        labs.value = res.data.data.data
      } else {
        // Staff — hanya lab yang mereka punya akses
        const res = await api.get(`/users/${authStore.user?.uuid}/labs`)
        labs.value = res.data.data.data
      }
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
    loading,
    branding,
    primaryColor,
    secondaryColor,
    labName,
    fetchLabs,
    setActiveLab,
    applyBranding,
    restoreFromStorage,
  }
})
