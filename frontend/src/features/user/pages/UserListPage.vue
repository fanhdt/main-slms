<script setup lang="ts">
import { ref } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import UserFormModal from '@/features/user/components/UserFormModal.vue'
import type { User } from '@/types'
import { userApi } from '@/features/user/api/userApi'
import { toast } from 'vue-sonner'

const queryClient = useQueryClient()
const search = ref('')
const page = ref(1)

const { data, isLoading } = useQuery({
  queryKey: ['users', search, page],
  queryFn: async () => {
    const res = await userApi.getAll({
      search: search.value || undefined,
      page: page.value,
    })
    return res.data.data
  },
})

const { mutate: deleteUser } = useMutation({
  mutationFn: (uuid: string) => userApi.delete(uuid),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['users'] })
    toast.success('User berhasil dihapus.')
  },
  onError: () => {
    toast.error('Gagal menghapus user.')
  },
})

function confirmDelete(user: User) {
  if (confirm(`Hapus user "${user.name}"?`)) {
    deleteUser(user.uuid)
  }
}

function roleColor(role: string) {
  const colors: Record<string, string> = {
    super_admin: 'bg-purple-100 text-purple-700',
    lab_admin: 'bg-blue-100 text-blue-700',
    operator: 'bg-yellow-100 text-yellow-700',
    photographer: 'bg-green-100 text-green-700',
    editor: 'bg-orange-100 text-orange-700',
    customer: 'bg-gray-100 text-gray-700',
    guest: 'bg-slate-100 text-slate-600',
  }
  return colors[role] ?? 'bg-gray-100 text-gray-700'
}

const showModal = ref(false)
const selectedUser = ref<User | null>(null)

function openCreate() {
  selectedUser.value = null
  showModal.value = true
}

function openEdit(user: User) {
  selectedUser.value = user
  showModal.value = true
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Pengguna</h2>
        <p class="text-gray-500 mt-1 text-sm">Kelola semua pengguna dalam sistem.</p>
      </div>
      <!-- Header button -->
      <button
        @click="openCreate"
        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors"
      >
        + Tambah User
      </button>
    </div>

    <!-- Search -->
    <div class="bg-white rounded-xl border border-gray-200 p-4">
      <input
        v-model="search"
        type="text"
        placeholder="Cari nama atau email..."
        class="w-full max-w-sm px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        @input="page = 1"
      />
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div v-if="isLoading" class="p-8 text-center text-gray-500 text-sm">Memuat data...</div>

      <div v-else-if="!data?.data?.length" class="p-8 text-center text-gray-500 text-sm">
        Tidak ada pengguna ditemukan.
      </div>

      <table v-else class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Nama</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Email</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Role</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
            <th class="text-right px-4 py-3 font-medium text-gray-600">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="user in data.data" :key="user.uuid" class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <div
                  class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0"
                >
                  <span class="text-xs font-medium text-blue-700">
                    {{ user.name.charAt(0).toUpperCase() }}
                  </span>
                </div>
                <div>
                  <div class="font-medium text-gray-900">{{ user.name }}</div>
                  <div class="text-xs text-gray-500">{{ user.phone ?? '-' }}</div>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 text-gray-600">{{ user.email }}</td>
            <td class="px-4 py-3">
              <span
                v-for="role in user.roles"
                :key="role"
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                :class="roleColor(role)"
              >
                {{ role.replace('_', ' ') }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                :class="user.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
              >
                {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-2">
                <!-- Edit button di table -->
                <button @click="openEdit(user)" class="text-xs text-blue-600 hover:underline">
                  Edit
                </button>
                <button @click="confirmDelete(user)" class="text-xs text-red-600 hover:underline">
                  Hapus
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div
        v-if="data?.meta"
        class="px-4 py-3 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600"
      >
        <span>
          Menampilkan {{ data.meta.from }}–{{ data.meta.to }} dari {{ data.meta.total }} data
        </span>
        <div class="flex gap-2">
          <button
            :disabled="page <= 1"
            @click="page--"
            class="px-3 py-1 rounded border border-gray-300 disabled:opacity-40 hover:bg-gray-50"
          >
            ←
          </button>
          <button
            :disabled="page >= data.meta.last_page"
            @click="page++"
            class="px-3 py-1 rounded border border-gray-300 disabled:opacity-40 hover:bg-gray-50"
          >
            →
          </button>
        </div>
      </div>
    </div>
  </div>
  <UserFormModal :show="showModal" :user="selectedUser" @close="showModal = false" />
</template>
