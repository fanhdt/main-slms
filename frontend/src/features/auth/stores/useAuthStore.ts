import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/types'
import api from '@/lib/axios'

export const useAuthStore = defineStore('auth', () => {
  // ---- State ----
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('token'))
  const loading = ref(false)

  // ---- Getters ----
  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(
    () => user.value?.roles.includes('super_admin') || user.value?.roles.includes('lab_admin'),
  )
  const hasPermission = (permission: string) =>
    user.value?.permissions.includes(permission) ?? false

  // ---- Actions ----
  async function login(email: string, password: string) {
    loading.value = true
    try {
      const response = await api.post('/auth/login', { email, password })
      const { user: userData, token: tokenData } = response.data.data

      user.value = userData
      token.value = tokenData
      localStorage.setItem('token', tokenData)

      return { success: true }
    } catch (error: any) {
      return {
        success: false,
        message: error.response?.data?.message ?? 'Login gagal.',
      }
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    loading.value = true
    try {
      await api.post('/auth/logout')
    } finally {
      user.value = null
      token.value = null
      localStorage.removeItem('token')
      loading.value = false
    }
  }

  async function fetchUser() {
    if (!token.value) return
    try {
      const response = await api.get('/auth/me')
      user.value = response.data.data
    } catch {
      user.value = null
      token.value = null
      localStorage.removeItem('token')
    }
  }

  function hasRole(role: string) {
    return user.value?.roles.includes(role) ?? false
  }

  return {
    user,
    token,
    loading,
    isAuthenticated,
    isAdmin,
    login,
    logout,
    fetchUser,
    hasPermission,
    hasRole,
  }
})
