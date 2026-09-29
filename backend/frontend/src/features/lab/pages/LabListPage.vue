<script setup lang="ts">
import { ref } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { labApi } from '@/features/lab/api/labApi'
import { toast } from 'vue-sonner'
import type { Lab } from '@/types'
import LabFormModal from '@/features/lab/components/LabFormModal.vue'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Plus,
  Search,
  Eye,
  Pencil,
  Trash2,
  FlaskConical,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'
import { useConfirmDialog } from '@/composables/useConfirmDialog'

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

async function handleDelete(lab: Lab) {
  const ok = await confirmDelete({ title: `Hapus lab "${lab.name}"?` })
  if (ok) deleteLab(lab.uuid)
}

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

const { confirmDelete } = useConfirmDialog()

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
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Laboratorium</h1>
        <p class="text-sm text-gray-500 mt-0.5">Kelola semua laboratorium dalam sistem.</p>
      </div>
      <Button size="sm" @click="openCreate">
        <Plus class="size-4" />
        Tambah Lab
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
            placeholder="Cari laboratorium..."
            class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            @input="page = 1"
          />
        </div>
      </CardContent>
    </Card>

    <!-- Table -->
    <Card class="p-0 overflow-hidden">
      <!-- Loading -->
      <div v-if="isLoading" class="p-5 space-y-3">
        <Skeleton v-for="i in 5" :key="i" class="h-12 w-full" />
      </div>

      <!-- Empty -->
      <div v-else-if="!data?.data?.length" class="p-10 text-center">
        <FlaskConical class="size-8 mx-auto text-gray-300 mb-2" />
        <p class="text-sm text-gray-500">Tidak ada laboratorium ditemukan.</p>
      </div>

      <!-- Table -->
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
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
                <div class="flex items-center gap-3">
                  <div
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold text-white shrink-0"
                    :style="{ backgroundColor: lab.branding.primary_color ?? '#94a3b8' }"
                  >
                    {{ lab.name.charAt(0).toUpperCase() }}
                  </div>
                  <div class="min-w-0">
                    <div class="font-medium text-gray-900 truncate">{{ lab.name }}</div>
                    <div class="text-xs text-gray-500 truncate">{{ lab.description ?? '-' }}</div>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ lab.slug }}</td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-1.5">
                  <span
                    class="w-3.5 h-3.5 rounded-full border border-gray-200 shrink-0"
                    :style="{ backgroundColor: lab.branding.primary_color ?? '#ccc' }"
                  />
                  <span
                    class="w-3.5 h-3.5 rounded-full border border-gray-200 shrink-0"
                    :style="{ backgroundColor: lab.branding.secondary_color ?? '#ccc' }"
                  />
                </div>
              </td>
              <td class="px-4 py-3">
                <Badge
                  variant="outline"
                  class="border-0"
                  :class="
                    lab.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'
                  "
                >
                  {{ lab.is_active ? 'Aktif' : 'Nonaktif' }}
                </Badge>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-1">
                  <a
                    :href="`/lab/${lab.slug}`"
                    target="_blank"
                    rel="noopener"
                    title="Preview landing page"
                    class="p-1.5 rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors"
                  >
                    <Eye class="size-4" />
                  </a>
                  <button
                    title="Edit"
                    @click="openEdit(lab)"
                    class="p-1.5 rounded-md text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                  >
                    <Pencil class="size-4" />
                  </button>
                  <button
                    title="Hapus"
                    @click="handleDelete(lab)"
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
  <LabFormModal :show="showModal" :lab="selectedLab" @close="showModal = false" />
</template>
