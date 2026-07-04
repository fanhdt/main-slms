<script setup lang="ts">
import { ref } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { packageApi } from '@/features/labservice/api/packageApi'
import PackageFormModal from '@/features/labservice/components/PackageFormModal.vue'
import { toast } from 'vue-sonner'
import { useLabStore } from '@/features/lab/stores/useLabStore'

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
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Packages</h2>
        <p class="text-gray-500 mt-1 text-sm">Bundling jasa dan alat jadi 1 paket harga.</p>
      </div>
      <button
        @click="openCreate"
        class="px-4 py-2 rounded-xl bg-gray-900 text-white text-sm font-medium hover:bg-gray-800"
      >
        + Tambah Package
      </button>
    </div>

    <div v-if="isLoading" class="p-8 text-center text-gray-500 text-sm">Memuat data...</div>

    <div
      v-else-if="!data?.data?.length"
      class="p-8 text-center text-gray-400 text-sm bg-white rounded-xl border border-gray-200"
    >
      Belum ada package. Klik "+ Tambah Package" untuk membuat.
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="pkg in data.data"
        :key="pkg.uuid"
        class="bg-white rounded-xl border border-gray-200 p-5 space-y-3"
      >
        <div class="flex items-start justify-between">
          <h3 class="font-semibold text-gray-900">{{ pkg.name }}</h3>
          <span
            v-if="!pkg.is_active"
            class="text-[11px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-500"
            >Nonaktif</span
          >
        </div>
        <p class="text-sm text-gray-500 line-clamp-2">{{ pkg.description }}</p>

        <div>
          <p class="text-xl font-bold text-gray-900">
            {{ formatPrice(pkg.final_price ?? pkg.price) }}
          </p>
        </div>

        <ul class="space-y-1">
          <li
            v-for="item in pkg.items"
            :key="item.id"
            class="text-xs text-gray-600 flex items-center gap-1.5"
          >
            <span>{{ item.type === 'service' ? '🛎' : '📦' }}</span>
            {{ item.service?.name ?? item.asset?.name }}
            <span class="text-gray-400">× {{ item.quantity }}</span>
          </li>
        </ul>

        <div class="flex gap-2 pt-2 border-t border-gray-100">
          <button @click="openEdit(pkg)" class="text-xs text-blue-600 hover:underline">Edit</button>
          <button @click="confirmDelete(pkg)" class="text-xs text-red-600 hover:underline">
            Hapus
          </button>
        </div>
      </div>
    </div>

    <PackageFormModal :show="showModal" :pkg="editingPackage" @close="showModal = false" />
  </div>
</template>
