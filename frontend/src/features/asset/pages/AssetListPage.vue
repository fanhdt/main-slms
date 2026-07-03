<script setup lang="ts">
import { ref } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import AssetFormModal from '@/features/asset/components/AssetFormModal.vue'
import { assetApi } from '@/features/asset/api/assetApi'
import { toast } from 'vue-sonner'
import type { Asset } from '@/types'

const queryClient = useQueryClient()
const search = ref('')
const page = ref(1)
const filterStatus = ref('')
const filterCategory = ref('')

const { data, isLoading } = useQuery({
  queryKey: ['assets', search, page, filterStatus, filterCategory],
  queryFn: async () => {
    const res = await assetApi.getAll({
      search: search.value || undefined,
      page: page.value,
      status: filterStatus.value || undefined,
      category: filterCategory.value || undefined,
    })
    return res.data.data
  },
})

const { mutate: deleteAsset } = useMutation({
  mutationFn: (uuid: string) => assetApi.delete(uuid),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['assets'] })
    toast.success('Aset berhasil dihapus.')
  },
  onError: () => {
    toast.error('Gagal menghapus aset.')
  },
})

function confirmDelete(asset: Asset) {
  if (confirm(`Hapus aset "${asset.name}"?`)) {
    deleteAsset(asset.uuid)
  }
}

function statusColor(status: string) {
  const colors: Record<string, string> = {
    available: 'bg-green-100 text-green-700',
    in_use: 'bg-blue-100 text-blue-700',
    maintenance: 'bg-yellow-100 text-yellow-700',
    retired: 'bg-red-100 text-red-700',
  }
  return colors[status] ?? 'bg-gray-100 text-gray-600'
}

function formatPrice(price: string | null) {
  if (!price) return '-'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(Number(price))
}
const showModal = ref(false)
const selectedAsset = ref<Asset | null>(null)

function openCreate() {
  selectedAsset.value = null
  showModal.value = true
}

function openEdit(asset: Asset) {
  selectedAsset.value = asset
  showModal.value = true
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Aset</h2>
        <p class="text-gray-500 mt-1 text-sm">Kelola semua aset laboratorium.</p>
      </div>
      <button
        @click="openCreate"
        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors"
      >
        + Tambah Aset
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
      <input
        v-model="search"
        type="text"
        placeholder="Cari nama atau kode..."
        class="flex-1 min-w-48 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        @input="page = 1"
      />
      <select
        v-model="filterStatus"
        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        @change="page = 1"
      >
        <option value="">Semua Status</option>
        <option value="available">Tersedia</option>
        <option value="in_use">Sedang Dipakai</option>
        <option value="maintenance">Dalam Perbaikan</option>
        <option value="retired">Tidak Aktif</option>
      </select>
      <select
        v-model="filterCategory"
        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        @change="page = 1"
      >
        <option value="">Semua Kategori</option>
        <option value="camera">Kamera</option>
        <option value="lens">Lensa</option>
        <option value="lighting">Lighting</option>
        <option value="drone">Drone</option>
        <option value="tripod">Tripod</option>
        <option value="computer">Komputer</option>
        <option value="audio">Audio</option>
        <option value="other">Lainnya</option>
      </select>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div v-if="isLoading" class="p-8 text-center text-gray-500 text-sm">Memuat data...</div>

      <div v-else-if="!data?.data?.length" class="p-8 text-center text-gray-500 text-sm">
        Tidak ada aset ditemukan.
      </div>

      <table v-else class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Nama</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Kode</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Kategori</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Harga Sewa</th>
            <th class="text-right px-4 py-3 font-medium text-gray-600">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr
            v-for="asset in data.data"
            :key="asset.uuid"
            class="hover:bg-gray-50 transition-colors"
          >
            <td class="px-4 py-3">
              <div class="font-medium text-gray-900">{{ asset.name }}</div>
              <div class="text-xs text-gray-500 mt-0.5">{{ asset.brand }} {{ asset.model }}</div>
            </td>
            <td class="px-4 py-3 text-gray-600 font-mono text-xs">
              {{ asset.code }}
            </td>
            <td class="px-4 py-3 text-gray-600">
              {{ asset.category.label }}
            </td>
            <td class="px-4 py-3">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                :class="statusColor(asset.status.value)"
              >
                {{ asset.status.label }}
              </span>
            </td>
            <td class="px-4 py-3 text-gray-600">
              {{ formatPrice(asset.rental_price) }}
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-2">
                <button @click="openEdit(asset)" class="text-xs text-blue-600 hover:underline">
                  Edit
                </button>
                <button @click="confirmDelete(asset)" class="text-xs text-red-600 hover:underline">
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
  <AssetFormModal :show="showModal" :asset="selectedAsset" @close="showModal = false" />
</template>
