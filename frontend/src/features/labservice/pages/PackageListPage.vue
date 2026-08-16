<script setup lang="ts">
import { ref } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { packageApi } from '@/features/labservice/api/packageApi'
import PackageFormModal from '@/features/labservice/components/PackageFormModal.vue'
import { toast } from 'vue-sonner'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Plus,
  Pencil,
  Trash2,
  PackageOpen,
  Wrench,
  ImageOff,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'

const labStore = useLabStore()
const showModal = ref(false)
const editingPackage = ref<any | null>(null)
const queryClient = useQueryClient()

const { data, isLoading } = useQuery({
  queryKey: ['packages', labStore.activeLab?.id],
  queryFn: async () => {
    const res = await packageApi.getAll({ lab_id: labStore.activeLab?.id })
    return res.data.data
  },
})

const { mutate: deletePackage } = useMutation({
  mutationFn: (uuid: string) => packageApi.delete(uuid),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['packages'] })
    toast.success('Package berhasil dihapus.')
  },
})

function openCreate() {
  editingPackage.value = null
  showModal.value = true
}

function openEdit(pkg: any) {
  editingPackage.value = pkg
  showModal.value = true
}

function confirmDelete(pkg: any) {
  if (confirm(`Hapus package "${pkg.name}"?`)) {
    deletePackage(pkg.uuid)
  }
}

function formatPrice(price: string) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(Number(price))
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Packages</h1>
        <p class="text-sm text-gray-500 mt-0.5">Bundling jasa dan alat jadi 1 paket harga.</p>
      </div>
      <Button size="sm" @click="openCreate">
        <Plus class="size-4" />
        Tambah Package
      </Button>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="grid grid-cols-1 xs:grid-cols-2 lg:grid-cols-3 gap-4">
      <Skeleton v-for="i in 3" :key="i" class="h-72 w-full rounded-xl" />
    </div>

    <!-- Empty -->
    <Card v-else-if="!data?.data?.length" class="p-0">
      <CardContent class="p-10 text-center">
        <PackageOpen class="size-8 mx-auto text-gray-300 mb-2" />
        <p class="text-sm text-gray-500">Belum ada package. Klik "Tambah Package" untuk membuat.</p>
      </CardContent>
    </Card>

    <!-- Grid -->
    <div v-else class="grid grid-cols-1 xs:grid-cols-2 lg:grid-cols-3 gap-4">
      <Card v-for="pkg in data.data" :key="pkg.uuid" class="p-0 overflow-hidden">
        <!-- Thumbnail gambar paket -->
        <div class="aspect-video bg-gray-100 flex items-center justify-center overflow-hidden">
          <img
            v-if="pkg.image"
            :src="pkg.image"
            :alt="pkg.name"
            class="w-full h-full object-cover"
            loading="lazy"
          />
          <ImageOff v-else class="size-7 text-gray-300" />
        </div>

        <CardContent class="p-5 space-y-3">
          <div class="flex items-start justify-between gap-2">
            <h3 class="font-semibold text-gray-900 truncate">{{ pkg.name }}</h3>
            <Badge
              v-if="!pkg.is_active"
              variant="outline"
              class="border-0 bg-gray-100 text-gray-500 shrink-0"
            >
              Nonaktif
            </Badge>
          </div>
          <p class="text-sm text-gray-500 line-clamp-2">{{ pkg.description }}</p>

          <p class="text-xl font-bold text-gray-900">
            {{ formatPrice(pkg.final_price ?? pkg.price) }}
          </p>

          <ul class="space-y-1.5">
            <li
              v-for="item in pkg.items"
              :key="item.id"
              class="text-xs text-gray-600 flex items-center gap-1.5"
            >
              <component
                :is="item.type === 'service' ? Wrench : PackageOpen"
                class="size-3.5 text-gray-400 shrink-0"
              />
              <span class="truncate">{{ item.service?.name ?? item.asset?.name }}</span>
              <span class="text-gray-400 shrink-0">× {{ item.quantity }}</span>
            </li>
          </ul>

          <div class="flex gap-1 pt-2 border-t border-gray-100">
            <button
              title="Edit"
              @click="openEdit(pkg)"
              class="p-1.5 rounded-md text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
            >
              <Pencil class="size-4" />
            </button>
            <button
              title="Hapus"
              @click="confirmDelete(pkg)"
              class="p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
            >
              <Trash2 class="size-4" />
            </button>
          </div>
        </CardContent>
      </Card>
    </div>

    <PackageFormModal :show="showModal" :pkg="editingPackage" @close="showModal = false" />
  </div>
</template>