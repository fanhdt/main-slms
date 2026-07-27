<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import AssetFormModal from '@/features/asset/components/AssetFormModal.vue'
import { assetApi } from '@/features/asset/api/assetApi'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { toast } from 'vue-sonner'
import type { Asset } from '@/types'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Plus, Search, Pencil, Trash2, Package, ChevronLeft, ChevronRight } from 'lucide-vue-next'

const queryClient = useQueryClient()
const labStore = useLabStore()
const search = ref('')
const page = ref(1)
const filterStatus = ref('')
const filterCategory = ref('')

const { data, isLoading } = useQuery({
  queryKey: ['assets', labStore.activeLab?.id, search, page, filterStatus, filterCategory],
  queryFn: async () => {
    const res = await assetApi.getAll({
      lab_id: labStore.activeLab?.id,
      search: search.value || undefined,
      page: page.value,
      status: filterStatus.value || undefined,
      category: filterCategory.value || undefined,
    })
    return res.data.data
  },
  enabled: computed(() => !!labStore.activeLab?.id),
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

const STATUS_STYLES: Record<string, string> = {
  available: 'bg-green-50 text-green-700',
  in_use: 'bg-blue-50 text-blue-700',
  maintenance: 'bg-yellow-50 text-yellow-700',
  retired: 'bg-red-50 text-red-700',
}
function statusColor(status: string) {
  return STATUS_STYLES[status] ?? 'bg-gray-100 text-gray-600'
}

// Warna badge per kategori — biar tabel lebih gampang dipindai matanya,
// tidak monoton satu warna untuk semua kategori.
const CATEGORY_STYLES: Record<string, string> = {
  camera: 'bg-purple-50 text-purple-700',
  lens: 'bg-indigo-50 text-indigo-700',
  lighting: 'bg-amber-50 text-amber-700',
  drone: 'bg-cyan-50 text-cyan-700',
  tripod: 'bg-teal-50 text-teal-700',
  computer: 'bg-slate-100 text-slate-700',
  projector: 'bg-orange-50 text-orange-700',
  audio: 'bg-pink-50 text-pink-700',
  microphone: 'bg-rose-50 text-rose-700',
  printer: 'bg-lime-50 text-lime-700',
  backdrop: 'bg-fuchsia-50 text-fuchsia-700',
  costume: 'bg-emerald-50 text-emerald-700',
  other: 'bg-gray-100 text-gray-600',
}
function categoryColor(category: string) {
  return CATEGORY_STYLES[category] ?? 'bg-gray-100 text-gray-600'
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
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Aset</h1>
        <p class="text-sm text-gray-500 mt-0.5">Kelola semua aset laboratorium.</p>
      </div>
      <Button size="sm" @click="openCreate">
        <Plus class="size-4" />
        Tambah Aset
      </Button>
    </div>

    <!-- Filters -->
    <Card class="p-0">
      <CardContent class="p-4 flex flex-wrap gap-3">
        <div class="relative flex-1 min-w-48">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400" />
          <input
            v-model="search"
            type="text"
            placeholder="Cari nama atau kode..."
            class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            @input="page = 1"
          />
        </div>
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
          <option value="projector">Proyektor</option>
          <option value="audio">Audio</option>
          <option value="microphone">Mikrofon</option>
          <option value="printer">Printer</option>
          <option value="backdrop">Backdrop / Properti</option>
          <option value="costume">Kostum / Aksesoris</option>
          <option value="other">Lainnya</option>
        </select>
      </CardContent>
    </Card>

    <!-- Table -->
    <Card class="p-0 overflow-hidden">
      <div v-if="isLoading" class="p-5 space-y-3">
        <Skeleton v-for="i in 5" :key="i" class="h-12 w-full" />
      </div>

      <div v-else-if="!data?.data?.length" class="p-10 text-center">
        <Package class="size-8 mx-auto text-gray-300 mb-2" />
        <p class="text-sm text-gray-500">Tidak ada aset ditemukan.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Nama</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Kode</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Kategori</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Harga Sewa</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Stok</th>
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
                <div class="font-medium text-gray-900 truncate">{{ asset.name }}</div>
                <div class="text-xs text-gray-500 truncate">
                  {{ asset.brand }} {{ asset.model }}
                </div>
              </td>
              <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ asset.code }}</td>
              <td class="px-4 py-3">
                <Badge
                  variant="outline"
                  class="border-0"
                  :class="categoryColor(asset.category.value)"
                >
                  {{ asset.category.label }}
                </Badge>
              </td>
              <td class="px-4 py-3">
                <Badge variant="outline" class="border-0" :class="statusColor(asset.status.value)">
                  {{ asset.status.label }}
                </Badge>
              </td>
              <td class="px-4 py-3 text-gray-600">{{ formatPrice(asset.rental_price) }}</td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-1">
                  <button
                    title="Edit"
                    @click="openEdit(asset)"
                    class="p-1.5 rounded-md text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                  >
                    <Pencil class="size-4" />
                  </button>
                  <button
                    title="Hapus"
                    @click="confirmDelete(asset)"
                    class="p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                  >
                    <Trash2 class="size-4" />
                  </button>
                </div>
              </td>
              <td class="px-4 py-3">
                <span
                  class="text-sm font-medium"
                  :class="asset.available_now > 0 ? 'text-gray-700' : 'text-red-600'"
                >
                  {{ asset.available_now }} / {{ asset.quantity }}
                </span>
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
  <AssetFormModal :show="showModal" :asset="selectedAsset" @close="showModal = false" />
</template>
