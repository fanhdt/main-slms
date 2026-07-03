<script setup lang="ts">
import { ref, watch } from 'vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { userApi } from '@/features/user/api/userApi'
import BaseModal from '@/components/BaseModal.vue'
import { toast } from 'vue-sonner'
import type { User } from '@/types'

const props = defineProps<{
  show: boolean
  user?: User | null
}>()

const emit = defineEmits<{
  close: []
}>()

const queryClient = useQueryClient()

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  phone: '',
  role: 'customer',
  is_active: true,
})

const errors = ref<Record<string, string>>({})

// Isi form saat mode edit
watch(
  () => props.user,
  (user) => {
    if (user) {
      form.value = {
        name: user.name,
        email: user.email,
        password: '',
        password_confirmation: '',
        phone: user.phone ?? '',
        role: user.roles[0] ?? 'customer',
        is_active: user.is_active,
      }
    } else {
      form.value = {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        phone: '',
        role: 'customer',
        is_active: true,
      }
    }
  },
  { immediate: true },
)

const isEdit = ref(false)
watch(
  () => props.user,
  (user) => {
    isEdit.value = !!user
  },
  { immediate: true },
)

const { mutate: saveUser, isPending } = useMutation({
  mutationFn: async () => {
    if (isEdit.value && props.user) {
      const data: Record<string, unknown> = {
        name: form.value.name,
        phone: form.value.phone || null,
        role: form.value.role,
        is_active: form.value.is_active,
      }
      if (form.value.password) {
        data.password = form.value.password
        data.password_confirmation = form.value.password_confirmation
      }
      return userApi.update(props.user.uuid, data)
    } else {
      return userApi.create(form.value)
    }
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['users'] })
    toast.success(isEdit.value ? 'User berhasil diupdate.' : 'User berhasil dibuat.')
    emit('close')
  },
  onError: (error: any) => {
    const errs = error.response?.data?.errors
    if (errs) {
      errors.value = Object.fromEntries(
        Object.entries(errs).map(([k, v]) => [k, (v as string[])[0]]),
      )
    } else {
      toast.error(error.response?.data?.message ?? 'Terjadi kesalahan.')
    }
  },
})
</script>

<template>
  <BaseModal
    :show="show"
    :title="isEdit ? 'Edit User' : 'Tambah User'"
    size="md"
    @close="$emit('close')"
  >
    <form @submit.prevent="saveUser" class="space-y-4">
      <!-- Nama -->
      <div class="space-y-1.5">
        <label class="text-sm font-medium text-gray-700">Nama Lengkap</label>
        <input
          v-model="form.name"
          type="text"
          placeholder="John Doe"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          :class="{ 'border-red-400': errors.name }"
        />
        <p v-if="errors.name" class="text-xs text-red-500">{{ errors.name }}</p>
      </div>

      <!-- Email — hanya saat create -->
      <div v-if="!isEdit" class="space-y-1.5">
        <label class="text-sm font-medium text-gray-700">Email</label>
        <input
          v-model="form.email"
          type="email"
          placeholder="john@example.com"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          :class="{ 'border-red-400': errors.email }"
        />
        <p v-if="errors.email" class="text-xs text-red-500">{{ errors.email }}</p>
      </div>

      <!-- Phone -->
      <div class="space-y-1.5">
        <label class="text-sm font-medium text-gray-700">No. Telepon</label>
        <input
          v-model="form.phone"
          type="tel"
          placeholder="08123456789"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <!-- Role -->
      <div class="space-y-1.5">
        <label class="text-sm font-medium text-gray-700">Role</label>
        <select
          v-model="form.role"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="super_admin">Super Admin</option>
          <option value="lab_admin">Lab Admin</option>
          <option value="operator">Operator</option>
          <option value="photographer">Photographer</option>
          <option value="editor">Editor</option>
          <option value="customer">Customer</option>
          <option value="guest">Guest</option>
        </select>
      </div>

      <!-- Password -->
      <div class="space-y-1.5">
        <label class="text-sm font-medium text-gray-700">
          Password
          <span v-if="isEdit" class="text-gray-400 font-normal">(kosongkan jika tidak diubah)</span>
        </label>
        <input
          v-model="form.password"
          type="password"
          placeholder="••••••••"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          :class="{ 'border-red-400': errors.password }"
        />
        <p v-if="errors.password" class="text-xs text-red-500">{{ errors.password }}</p>
      </div>

      <div class="space-y-1.5">
        <label class="text-sm font-medium text-gray-700">Konfirmasi Password</label>
        <input
          v-model="form.password_confirmation"
          type="password"
          placeholder="••••••••"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <!-- Status -->
      <div class="flex items-center gap-3">
        <input
          v-model="form.is_active"
          type="checkbox"
          id="is_active"
          class="w-4 h-4 rounded border-gray-300"
        />
        <label for="is_active" class="text-sm font-medium text-gray-700"> Akun Aktif </label>
      </div>
    </form>

    <template #footer>
      <button
        @click="$emit('close')"
        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
      >
        Batal
      </button>
      <button
        @click="saveUser()"
        :disabled="isPending"
        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 rounded-lg transition-colors"
      >
        {{ isPending ? 'Menyimpan...' : isEdit ? 'Update' : 'Simpan' }}
      </button>
    </template>
  </BaseModal>
</template>
