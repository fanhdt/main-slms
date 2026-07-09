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
  async function register(payload: {
    name: string
    email: string
    password: string
    password_confirmation: string
    phone?: string
  }) {
    loading.value = true
    try {
      const response = await api.post('/auth/register', payload)
      return { success: true, message: response.data.message }
    } catch (error: any) {
      return {
        success: false,
        message: error.response?.data?.message ?? 'Registrasi gagal.',
        errors: error.response?.data?.errors,
      }
    } finally {
      loading.value = false
    }
  }

  async function resendVerification(email: string) {
    loading.value = true
    try {
      const response = await api.post('/auth/email/resend', { email })
      return { success: true, message: response.data.message }
    } catch (error: any) {
      return {
        success: false,
        message: error.response?.data?.message ?? 'Gagal mengirim ulang email verifikasi.',
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

  // NEW — kirim link reset password ke email
  async function forgotPassword(email: string) {
    loading.value = true
    try {
      await api.post('/auth/forgot-password', { email })
      return { success: true }
    } catch (error: any) {
      return {
        success: false,
        message: error.response?.data?.message ?? 'Gagal mengirim link reset password.',
      }
    } finally {
      loading.value = false
    }
  }

  // NEW — reset password pakai token dari email
  async function resetPassword(payload: {
    token: string
    email: string
    password: string
    password_confirmation: string
  }) {
    loading.value = true
    try {
      await api.post('/auth/reset-password', payload)
      return { success: true }
    } catch (error: any) {
      return {
        success: false,
        message: error.response?.data?.message ?? 'Gagal mereset password.',
      }
    } finally {
      loading.value = false
    }
  }

  async function updateAvatar(file: File) {
    loading.value = true
    try {
      const formData = new FormData()
      formData.append('avatar', file)
      const response = await api.post('/auth/me/avatar', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      user.value = response.data.data
      return { success: true }
    } catch (error: any) {
      return { success: false, message: error.response?.data?.message ?? 'Gagal upload avatar.' }
    } finally {
      loading.value = false
    }
  }

  async function updateProfile(payload: { name?: string; email?: string; phone?: string }) {
    loading.value = true
    try {
      const response = await api.put('/auth/me', payload)
      user.value = response.data.data
      return { success: true }
    } catch (error: any) {
      return {
        success: false,
        message: error.response?.data?.message ?? 'Gagal update profil.',
        errors: error.response?.data?.errors,
      }
    } finally {
      loading.value = false
    }
  }

  async function deleteAccount(password: string) {
    loading.value = true
    try {
      await api.delete('/auth/me', { data: { password } })
      user.value = null
      token.value = null
      localStorage.removeItem('token')
      return { success: true }
    } catch (error: any) {
      return { success: false, message: error.response?.data?.message ?? 'Gagal menghapus akun.' }
    } finally {
      loading.value = false
    }
  }

  async function changePassword(payload: {
    current_password: string
    password: string
    password_confirmation: string
  }) {
    loading.value = true
    try {
      await api.put('/auth/me/password', payload)
      return { success: true }
    } catch (error: any) {
      return {
        success: false,
        message: error.response?.data?.message ?? 'Gagal mengubah password.',
        errors: error.response?.data?.errors,
      }
    } finally {
      loading.value = false
    }
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
    forgotPassword,
    resetPassword,
    register,
    resendVerification,
    updateProfile,
    updateAvatar,
    deleteAccount,
    changePassword,
  }
})
