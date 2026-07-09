<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import UserFormModal from '@/features/user/components/UserFormModal.vue'
import type { User } from '@/types'
import { userApi } from '@/features/user/api/userApi'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { toast } from 'vue-sonner'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Plus,
  Search,
  Pencil,
  Trash2,
  Users as UsersIcon,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'

const queryClient = useQueryClient()
const route = useRoute()
const authStore = useAuthStore()
const labStore = useLabStore()

const search = ref('')
const page = ref(1)

// Halaman ini dipakai di 2 tempat:
// - /admin/users        -> global, super_admin, semua user, full CRUD
// - /dashboard/lab/:slug/users -> lab_admin, HANYA customer di lab ini, hapus saja
const isLabContext = computed(() => !!route.params.labSlug)
const isSuperAdmin = computed(() => authStore.hasRole('super_admin'))
const canManageFully = computed(() => isSuperAdmin.value && !isLabContext.value)

const { data, isLoading } = useQuery({
  queryKey: ['users', search, page, isLabContext, labStore.activeLab?.id],
  queryFn: async () => {
    if (isLabContext.value) {
      const labId = labStore.activeLab?.id
      if (!labId) return null
      const res = await userApi.getLabCustomers(labId, {
        search: search.value || undefined,
        page: page.value,
      })
      return res.data.data
    }
    const res = await userApi.getAll({
      search: search.value || undefined,
      page: page.value,
    })
    return res.data.data
  },
  enabled: computed(() => !isLabContext.value || !!labStore.activeLab),
})

const { mutate: deleteUser } = useMutation({
  mutationFn: (uuid: string) => userApi.delete(uuid),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['users'] })
    toast.success('User berhasil dihapus.')
  },
  onError: (error: any) => {
    toast.error(error.response?.data?.message ?? 'Gagal menghapus user.')
  },
})

function confirmDelete(user: User) {
  const msg = isLabContext.value
    ? `Hapus akun customer "${user.name}"? Tindakan ini biasanya untuk pelanggar aturan lab.`
    : `Hapus user "${user.name}"?`
  if (confirm(msg)) {
    deleteUser(user.uuid)
  }
}

const ROLE_STYLES: Record<string, string> = {
  super_admin: 'bg-purple-50 text-purple-700',
  lab_admin: 'bg-blue-50 text-blue-700',
  operator: 'bg-yellow-50 text-yellow-700',
  photographer: 'bg-green-50 text-green-700',
  editor: 'bg-orange-50 text-orange-700',
  customer: 'bg-gray-100 text-gray-700',
  guest: 'bg-slate-100 text-slate-600',
}
function roleColor(role: string) {
  return ROLE_STYLES[role] ?? 'bg-gray-100 text-gray-700'
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
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">
          {{ isLabContext ? 'Customer Lab Ini' : 'Pengguna' }}
        </h1>
        <p class="text-sm text-gray-500 mt-0.5">
          {{
            isLabContext
              ? 'Customer yang pernah booking di lab ini. Hapus akun dipakai untuk pelanggar aturan.'
              : 'Kelola semua pengguna dalam sistem.'
          }}
        </p>
      </div>
      <Button v-if="canManageFully" size="sm" @click="openCreate">
        <Plus class="size-4" />
        Tambah User
      </Button>
    </div>

    <!-- Search -->
    <Card class="p-0">
      <CardContent class="p-4">
        <div class="relative max-w-sm">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400" />
          <input
            v-model="search"
            type="text"
            placeholder="Cari nama atau email..."
            class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            @input="page = 1"
          />
        </div>
      </CardContent>
    </Card>

    <!-- Table -->
    <Card class="p-0 overflow-hidden">
      <div v-if="isLoading" class="p-5 space-y-3">
        <Skeleton v-for="i in 5" :key="i" class="h-12 w-full" />
      </div>

      <div v-else-if="!data?.data?.length" class="p-10 text-center">
        <UsersIcon class="size-8 mx-auto text-gray-300 mb-2" />
        <p class="text-sm text-gray-500">
          {{
            isLabContext
              ? 'Belum ada customer yang booking di lab ini.'
              : 'Tidak ada pengguna ditemukan.'
          }}
        </p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
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
            <tr
              v-for="user in data.data"
              :key="user.uuid"
              class="hover:bg-gray-50 transition-colors"
            >
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div
                    class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center shrink-0 overflow-hidden"
                  >
                    <img
                      v-if="user.avatar"
                      :src="user.avatar"
                      :alt="user.name"
                      class="w-full h-full object-cover"
                    />
                    <span v-else class="text-xs font-medium text-blue-700">
                      {{ user.name.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                  <div class="min-w-0">
                    <div class="font-medium text-gray-900 truncate">{{ user.name }}</div>
                    <div class="text-xs text-gray-500 truncate">{{ user.phone ?? '-' }}</div>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 text-gray-600 truncate">{{ user.email }}</td>
              <td class="px-4 py-3">
                <div class="flex flex-wrap gap-1">
                  <Badge
                    v-for="role in user.roles"
                    :key="role"
                    variant="outline"
                    class="border-0 capitalize"
                    :class="roleColor(role)"
                  >
                    {{ role.replace('_', ' ') }}
                  </Badge>
                </div>
              </td>
              <td class="px-4 py-3">
                <Badge
                  variant="outline"
                  class="border-0"
                  :class="user.is_active ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'"
                >
                  {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                </Badge>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-1">
                  <button
                    v-if="canManageFully"
                    title="Edit"
                    @click="openEdit(user)"
                    class="p-1.5 rounded-md text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                  >
                    <Pencil class="size-4" />
                  </button>
                  <button
                    title="Hapus"
                    @click="confirmDelete(user)"
                    class="p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                  >
                    <Trash2 class="size-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="data?.meta"
        class="px-4 py-3 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600"
      >
        <span>
          Menampilkan {{ data.meta.from }}–{{ data.meta.to }} dari {{ data.meta.total }} data
        </span>
        <div class="flex gap-2">
          <Button variant="outline" size="icon-sm" :disabled="page <= 1" @click="page--">
            <ChevronLeft class="size-4" />
          </Button>
          <Button
            variant="outline"
            size="icon-sm"
            :disabled="page >= data.meta.last_page"
            @click="page++"
          >
            <ChevronRight class="size-4" />
          </Button>
        </div>
      </div>
    </Card>
  </div>
  <UserFormModal
    v-if="canManageFully"
    :show="showModal"
    :user="selectedUser"
    @close="showModal = false"
  />
</template>
