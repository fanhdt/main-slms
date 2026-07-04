<script setup lang="ts">
import { ref } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import ServiceFormModal from '@/features/labservice/components/ServiceFormModal.vue'
import { serviceApi } from '@/features/labservice/api/serviceApi'
import { toast } from 'vue-sonner'
import type { Service } from '@/types'

const queryClient = useQueryClient()
const search = ref('')
const page = ref(1)
const filterType = ref('')
const filterActive = ref('')

const { data, isLoading } = useQuery({
  queryKey: ['services', search, page, filterType, filterActive],
  queryFn: async () => {
    const res = await serviceApi.getAll({
      search: search.value || undefined,
      page: page.value,
      type: filterType.value || undefined,
      is_active: filterActive.value || undefined,
    })
    return res.data.data
  },
})

const { mutate: deleteService } = useMutation({
  mutationFn: (uuid: string) => serviceApi.delete(uuid),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['services'] })
    toast.success('Layanan berhasil dihapus.')
  },
  onError: () => {
    toast.error('Gagal menghapus layanan.')
  },
})

function confirmDelete(service: Service) {
  if (confirm(`Hapus layanan "${service.name}"?`)) {
    deleteService(service.uuid)
  }
}

function formatPrice(price: string) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(Number(price))
}

const rentalTypes = ['studio_rental', 'equipment_rental']
function isRental(service: Service) {
  return rentalTypes.includes(service.type.value)
}

const showModal = ref(false)
const selectedService = ref<Service | null>(null)

function openCreate() {
  selectedService.value = null
  showModal.value = true
}

function openEdit(service: Service) {
  selectedService.value = service
  showModal.value = true
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Layanan</h2>
        <p class="text-gray-500 mt-1 text-sm">
          Kelola jasa (fotografi, editing, dll) dan sewa (studio, peralatan) yang ditawarkan lab.
        </p>
      </div>
      <button
        @click="openCreate"
        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors"
      >
        + Tambah Layanan
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
      <input
        v-model="search"
        type="text"
        placeholder="Cari nama layanan..."
        class="flex-1 min-w-48 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        @input="page = 1"
      />
      <select
        v-model="filterType"
        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        @change="page = 1"
      >
        <option value="">Semua Tipe</option>
        <optgroup label="Jasa">
          <option value="photography">Fotografi</option>
          <option value="photo_editing">Editing Foto</option>
          <option value="recording">Recording</option>
          <option value="training">Pelatihan</option>
          <option value="printing">Cetak Foto</option>
        </optgroup>
        <option value="other">Lainnya</option>
      </select>
      <select
        v-model="filterActive"
        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        @change="page = 1"
      >
        <option value="">Semua Status</option>
        <option value="1">Aktif</option>
        <option value="0">Nonaktif</option>
      </select>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div v-if="isLoading" class="p-8 text-center text-gray-500 text-sm">Memuat data...</div>

      <div v-else-if="!data?.data?.length" class="p-8 text-center text-gray-500 text-sm">
        Belum ada layanan. Klik "+ Tambah Layanan" untuk membuat yang pertama.
      </div>

      <table v-else class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Nama</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Kategori</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Tipe</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Harga</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
            <th class="text-right px-4 py-3 font-medium text-gray-600">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="service in data.data" :key="service.uuid" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <p class="font-medium text-gray-900">{{ service.name }}</p>
              <p class="text-xs text-gray-400 line-clamp-1">{{ service.description }}</p>
            </td>
            <td class="px-4 py-3">
              <span
                class="text-xs font-medium px-2 py-0.5 rounded-full"
                :class="
                  isRental(service) ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'
                "
              >
                {{ isRental(service) ? 'Sewa' : 'Jasa' }}
              </span>
            </td>
            <td class="px-4 py-3 text-gray-600">{{ service.type.label }}</td>
            <td class="px-4 py-3">
              <span class="font-medium text-gray-900">{{ formatPrice(service.price) }}</span>
              <span class="text-xs text-gray-400 block">{{ service.pricing_type.label }}</span>
            </td>
            <td class="px-4 py-3">
              <span
                class="text-xs font-medium px-2 py-0.5 rounded-full"
                :class="
                  service.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'
                "
              >
                {{ service.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <button
                @click="openEdit(service)"
                class="text-blue-600 hover:underline text-xs font-medium mr-3"
              >
                Edit
              </button>
              <button
                @click="confirmDelete(service)"
                class="text-red-600 hover:underline text-xs font-medium"
              >
                Hapus
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="data && data.meta.last_page > 1" class="flex items-center justify-between">
      <p class="text-sm text-gray-500">
        Halaman {{ data.meta.current_page }} dari {{ data.meta.last_page }}
      </p>
      <div class="flex gap-2">
        <button
          :disabled="page <= 1"
          @click="page -= 1"
          class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg disabled:opacity-40"
        >
          ‹ Sebelumnya
        </button>
        <button
          :disabled="page >= data.meta.last_page"
          @click="page += 1"
          class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg disabled:opacity-40"
        >
          Berikutnya ›
        </button>
      </div>
    </div>

    <ServiceFormModal :show="showModal" :service="selectedService" @close="showModal = false" />
  </div>
</template>
