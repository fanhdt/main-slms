<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { photographerApi } from '@/features/portfolio/api/photographerApi'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import PhotographerFormModal from '@/features/portfolio/components/PhotographerFormModal.vue'
import { toast } from 'vue-sonner'
import type { Photographer } from '@/features/portfolio/types'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Plus, Pencil, Trash2, UserSquare2 } from 'lucide-vue-next'

const labStore = useLabStore()
const queryClient = useQueryClient()

const { data: photographers, isLoading } = useQuery({
  queryKey: ['photographers-admin', labStore.activeLab?.id],
  queryFn: async () => {
    const res = await photographerApi.getAll({ lab_id: labStore.activeLab?.id })
    return res.data.data
  },
  enabled: computed(() => !!labStore.activeLab?.id),
})

const { mutate: deletePhotographer } = useMutation({
  mutationFn: (uuid: string) => photographerApi.delete(uuid),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['photographers-admin'] })
    toast.success('Fotografer berhasil dihapus.')
  },
  onError: (err: any) => {
    toast.error(err.response?.data?.message ?? 'Gagal menghapus fotografer.')
  },
})

function confirmDelete(item: Photographer) {
  if (confirm(`Hapus profil ${item.name}? Portofolionya juga akan kehilangan pemilik.`)) {
    deletePhotographer(item.uuid)
  }
}

const showModal = ref(false)
const editingPhotographer = ref<Photographer | null>(null)

function openCreate() {
  editingPhotographer.value = null
  showModal.value = true
}

function openEdit(item: Photographer) {
  editingPhotographer.value = item
  showModal.value = true
}
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Profil Fotografer</h1>
        <p class="text-sm text-gray-500 mt-0.5">Kelola fotografer yang tersedia di lab ini.</p>
      </div>
      <Button size="sm" @click="openCreate">
        <Plus class="size-4" />
        Tambah Fotografer
      </Button>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
      <Skeleton v-for="i in 4" :key="i" class="h-48 w-full rounded-xl" />
    </div>

    <!-- Empty -->
    <Card v-else-if="!photographers?.length" class="p-0">
      <CardContent class="p-10 text-center">
        <UserSquare2 class="size-8 mx-auto text-gray-300 mb-2" />
        <p class="text-sm text-gray-500">Belum ada fotografer terdaftar.</p>
      </CardContent>
    </Card>

    <!-- Grid -->
    <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
      <Card v-for="p in photographers" :key="p.uuid" class="p-0 overflow-hidden group relative">
        <div class="aspect-square bg-gray-100 flex items-center justify-center">
          <img v-if="p.photo" :src="p.photo" :alt="p.name" class="w-full h-full object-cover" />
          <UserSquare2 v-else class="size-10 text-gray-300" />
        </div>
        <CardContent class="p-3">
          <p class="text-sm font-semibold text-gray-900 truncate">{{ p.name }}</p>
          <p v-if="p.instagram" class="text-xs text-gray-400 truncate">@{{ p.instagram }}</p>
          <Badge
            v-if="!p.is_active"
            variant="outline"
            class="border-0 bg-gray-100 text-gray-500 mt-1"
          >
            Nonaktif
          </Badge>
        </CardContent>
        <div
          class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity"
        >
          <button
            title="Edit"
            @click="openEdit(p)"
            class="p-1.5 rounded-md bg-white/90 text-gray-500 hover:text-blue-600 shadow transition-colors"
          >
            <Pencil class="size-3.5" />
          </button>
          <button
            title="Hapus"
            @click="confirmDelete(p)"
            class="p-1.5 rounded-md bg-white/90 text-gray-500 hover:text-red-600 shadow transition-colors"
          >
            <Trash2 class="size-3.5" />
          </button>
        </div>
      </Card>
    </div>

    <PhotographerFormModal
      :show="showModal"
      :photographer="editingPhotographer"
      :lab-id="labStore.activeLab?.id ?? 0"
      @close="showModal = false"
    />
  </div>
</template>
