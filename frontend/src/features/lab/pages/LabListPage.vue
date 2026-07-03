<script setup lang="ts">
import { ref } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { labApi } from '@/features/lab/api/labApi'
import { toast } from 'vue-sonner'
import type { Lab } from '@/types'
import LabFormModal from '@/features/lab/components/LabFormModal.vue'

const queryClient = useQueryClient()
const search = ref('')
const page = ref(1)
const showModal = ref(false)
const selectedLab = ref<Lab | null>(null)

const { data, isLoading } = useQuery({
  queryKey: ['labs', search, page],
  queryFn: async () => {
    const res = await labApi.getAll({
      search: search.value || undefined,
      page: page.value,
    })
    return res.data.data
  },
})

const { mutate: deleteLab } = useMutation({
  mutationFn: (uuid: string) => labApi.delete(uuid),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['labs'] })
    toast.success('Lab berhasil dihapus.')
  },
  onError: () => {
    toast.error('Gagal menghapus lab.')
  },
})

function confirmDelete(lab: Lab) {
  if (confirm(`Hapus lab "${lab.name}"?`)) {
    deleteLab(lab.uuid)
  }
}

function formatColor(color: string | null) {
  return color ?? '-'
}

function openCreate() {
  selectedLab.value = null
  showModal.value = true
}

function openEdit(lab: Lab) {
  selectedLab.value = lab
  showModal.value = true
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Laboratorium</h2>
        <p class="text-gray-500 mt-1 text-sm">Kelola semua laboratorium dalam sistem.</p>
      </div>
      <button
        @click="openCreate"
        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors"
      >
        + Tambah Lab
      </button>
    </div>

    <!-- Search -->
    <div class="bg-white rounded-xl border border-gray-200 p-4">
      <input
        v-model="search"
        type="text"
        placeholder="Cari laboratorium..."
        class="w-full max-w-sm px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        @input="page = 1"
      />
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <!-- Loading -->
      <div v-if="isLoading" class="p-8 text-center text-gray-500 text-sm">Memuat data...</div>

      <!-- Empty -->
      <div v-else-if="!data?.data?.length" class="p-8 text-center text-gray-500 text-sm">
        Tidak ada laboratorium ditemukan.
      </div>

      <!-- Table -->
      <table v-else class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Nama</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Slug</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Warna</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
            <th class="text-right px-4 py-3 font-medium text-gray-600">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="lab in data.data" :key="lab.uuid" class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3">
              <div class="font-medium text-gray-900">{{ lab.name }}</div>
              <div class="text-xs text-gray-500 mt-0.5">{{ lab.description ?? '-' }}</div>
            </td>
            <td class="px-4 py-3 text-gray-600">{{ lab.slug }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <div
                  class="w-4 h-4 rounded-full border border-gray-200"
                  :style="{ backgroundColor: lab.branding.primary_color ?? '#ccc' }"
                />
                <span class="text-gray-600 text-xs">
                  {{ formatColor(lab.branding.primary_color) }}
                </span>
              </div>
            </td>
            <td class="px-4 py-3">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                :class="lab.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
              >
                {{ lab.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-2">
                <button @click="openEdit(lab)" class="text-xs text-blue-600 hover:underline">
                  Edit
                </button>
                <button @click="confirmDelete(lab)" class="text-xs text-red-600 hover:underline">
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
  <LabFormModal :show="showModal" :lab="selectedLab" @close="showModal = false" />
</template>
