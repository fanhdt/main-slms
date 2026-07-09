<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { portfolioApi } from '@/features/portfolio/api/portfolioApi'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import PortfolioUploadForm from '@/features/portfolio/components/PortfolioUploadForm.vue'
import PortfolioEditModal from '@/features/portfolio/components/PortfolioEditModal.vue'
import { toast } from 'vue-sonner'
import type { PhotographerPortfolio } from '@/features/portfolio/types'
import { photographerApi } from '@/features/portfolio/api/photographerApi'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Pencil, Trash2, Images, ChevronLeft, ChevronRight, X } from 'lucide-vue-next'

const queryClient = useQueryClient()
const labStore = useLabStore()
const page = ref(1)
const filterPhotographerId = ref<number | null>(null)

const { data, isLoading } = useQuery({
  queryKey: ['portfolios', labStore.activeLab?.id, page, filterPhotographerId],
  queryFn: async () => {
    const res = await portfolioApi.getAll({
      lab_id: labStore.activeLab?.id,
      page: page.value,
      photographer_id: filterPhotographerId.value ?? undefined,
    })
    return res.data.data
  },
  enabled: computed(() => !!labStore.activeLab?.id),
})

const { data: photographers } = useQuery({
  queryKey: ['photographers-admin', labStore.activeLab?.id],
  queryFn: async () => {
    const res = await photographerApi.getAll({ lab_id: labStore.activeLab?.id })
    return res.data.data
  },
  enabled: computed(() => !!labStore.activeLab?.id),
})

const { mutate: deletePortfolio } = useMutation({
  mutationFn: (uuid: string) => portfolioApi.delete(uuid),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['portfolios'] })
    toast.success('Foto portofolio berhasil dihapus.')
  },
  onError: () => {
    toast.error('Gagal menghapus foto.')
  },
})

function confirmDelete(item: PhotographerPortfolio) {
  if (confirm(`Hapus foto dari ${item.photographer?.name ?? 'fotografer ini'}?`)) {
    deletePortfolio(item.uuid)
  }
}

const showEditModal = ref(false)
const selectedPortfolio = ref<PhotographerPortfolio | null>(null)

function openEdit(item: PhotographerPortfolio) {
  selectedPortfolio.value = item
  showEditModal.value = true
}

function closeEdit() {
  showEditModal.value = false
  selectedPortfolio.value = null
}

function resetFilter() {
  filterPhotographerId.value = null
  page.value = 1
}
</script>

<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-xl font-semibold tracking-tight text-gray-900">Portofolio Fotografer</h1>
      <p class="text-sm text-gray-500 mt-0.5">
        Kelola foto portofolio yang tampil di landing page lab ini.
      </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Form upload -->
      <div class="lg:col-span-1">
        <PortfolioUploadForm v-if="labStore.activeLab" :lab-id="labStore.activeLab.id" />
      </div>

      <!-- List + filter -->
      <div class="lg:col-span-2 space-y-4">
        <div class="flex items-center gap-3">
          <select
            v-model="filterPhotographerId"
            @change="page = 1"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option :value="null">Semua Fotografer</option>
            <option v-for="p in photographers" :key="p.uuid" :value="p.id">{{ p.name }}</option>
          </select>
          <Button v-if="filterPhotographerId" variant="ghost" size="sm" @click="resetFilter">
            <X class="size-3.5" />
            Reset filter
          </Button>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="grid grid-cols-2 sm:grid-cols-3 gap-4">
          <Skeleton v-for="i in 6" :key="i" class="aspect-square w-full rounded-xl" />
        </div>

        <!-- Empty -->
        <Card v-else-if="!data?.data.length" class="p-0">
          <CardContent class="p-10 text-center">
            <Images class="size-8 mx-auto text-gray-300 mb-2" />
            <p class="text-sm text-gray-500">Belum ada foto portofolio.</p>
          </CardContent>
        </Card>

        <!-- Grid -->
        <div v-else class="grid grid-cols-2 sm:grid-cols-3 gap-4">
          <Card
            v-for="item in data.data"
            :key="item.uuid"
            class="p-0 overflow-hidden group relative"
          >
            <div class="aspect-square bg-gray-100">
              <img :src="item.image" alt="" class="w-full h-full object-cover" />
            </div>
            <CardContent class="p-2">
              <p class="text-xs font-medium text-gray-900 truncate">
                {{ item.photographer?.name }}
              </p>
              <p v-if="item.caption" class="text-xs text-gray-400 truncate">{{ item.caption }}</p>
            </CardContent>
            <div
              class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity"
            >
              <button
                title="Edit"
                @click="openEdit(item)"
                class="p-1.5 rounded-md bg-white/90 text-gray-500 hover:text-blue-600 shadow transition-colors"
              >
                <Pencil class="size-3.5" />
              </button>
              <button
                title="Hapus"
                @click="confirmDelete(item)"
                class="p-1.5 rounded-md bg-white/90 text-gray-500 hover:text-red-600 shadow transition-colors"
              >
                <Trash2 class="size-3.5" />
              </button>
            </div>
          </Card>
        </div>

        <!-- Pagination -->
        <div
          v-if="data?.meta && data.meta.last_page > 1"
          class="flex items-center justify-between text-sm text-gray-600 pt-2"
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
      </div>
    </div>

    <PortfolioEditModal :show="showEditModal" :portfolio="selectedPortfolio" @close="closeEdit" />
  </div>
</template>
