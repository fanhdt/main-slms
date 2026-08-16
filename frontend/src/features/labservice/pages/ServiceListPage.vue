<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import ServiceFormModal from '@/features/labservice/components/ServiceFormModal.vue'
import { serviceApi } from '@/features/labservice/api/serviceApi'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { toast } from 'vue-sonner'
import type { Service } from '@/types'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Plus,
  Search,
  Pencil,
  Trash2,
  ClipboardList,
  ImageOff,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'

const queryClient = useQueryClient()
const labStore = useLabStore()
const search = ref('')
const page = ref(1)
const filterType = ref('')
const filterActive = ref('')

const { data, isLoading } = useQuery({
  queryKey: ['services', labStore.activeLab?.id, search, page, filterType, filterActive],
  queryFn: async () => {
    const res = await serviceApi.getAll({
      lab_id: labStore.activeLab?.id,
      search: search.value || undefined,
      page: page.value,
      type: filterType.value || undefined,
      is_active: filterActive.value || undefined,
    })
    return res.data.data
  },
  enabled: computed(() => !!labStore.activeLab?.id),
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

function formatPrice(price: string | number | null) {
  if (price === null || price === undefined) return null
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
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Layanan</h1>
        <p class="text-sm text-gray-500 mt-0.5">
          Kelola jasa (fotografi, editing, dll) dan sewa (studio, peralatan) yang ditawarkan lab.
        </p>
      </div>
      <Button size="sm" @click="openCreate">
        <Plus class="size-4" />
        Tambah Layanan
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
            placeholder="Cari nama layanan..."
            class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            @input="page = 1"
          />
        </div>
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
      </CardContent>
    </Card>

    <!-- Loading -->
    <div v-if="isLoading" class="space-y-3">
      <!-- Skeleton kartu di layar kecil -->
      <div class="grid grid-cols-1 xs:grid-cols-2 lg:hidden gap-4">
        <Skeleton v-for="i in 4" :key="i" class="h-64 w-full rounded-xl" />
      </div>
      <!-- Skeleton tabel di layar besar -->
      <Card class="p-0 hidden lg:block">
        <CardContent class="p-5 space-y-3">
          <Skeleton v-for="i in 5" :key="i" class="h-14 w-full" />
        </CardContent>
      </Card>
    </div>

    <!-- Empty -->
    <Card v-else-if="!data?.data?.length" class="p-0">
      <CardContent class="p-10 text-center">
        <ClipboardList class="size-8 mx-auto text-gray-300 mb-2" />
        <p class="text-sm text-gray-500">
          Belum ada layanan. Klik "Tambah Layanan" untuk membuat yang pertama.
        </p>
      </CardContent>
    </Card>

    <template v-else>
      <!-- ============================================================
           MOBILE / TABLET — grid kartu bergambar (di bawah breakpoint lg)
      ============================================================= -->
      <div class="grid grid-cols-1 xs:grid-cols-2 lg:hidden gap-4">
        <Card v-for="service in data.data" :key="service.uuid" class="p-0 overflow-hidden">
          <div class="aspect-video bg-gray-100 flex items-center justify-center overflow-hidden">
            <img
              v-if="service.image"
              :src="service.image"
              :alt="service.name"
              class="w-full h-full object-cover"
              loading="lazy"
            />
            <ImageOff v-else class="size-7 text-gray-300" />
          </div>
          <CardContent class="p-4 space-y-2.5">
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <p class="font-medium text-gray-900 truncate">{{ service.name }}</p>
                <p class="text-xs text-gray-500 truncate">{{ service.description }}</p>
              </div>
            </div>

            <div class="flex flex-wrap gap-1.5">
              <Badge
                variant="outline"
                class="border-0 text-[11px]"
                :class="
                  isRental(service) ? 'bg-purple-50 text-purple-700' : 'bg-blue-50 text-blue-700'
                "
              >
                {{ isRental(service) ? 'Sewa' : 'Jasa' }}
              </Badge>
              <Badge variant="outline" class="border-0 text-[11px] bg-gray-100 text-gray-600">
                {{ service.type.label }}
              </Badge>
              <Badge
                variant="outline"
                class="border-0 text-[11px]"
                :class="
                  service.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'
                "
              >
                {{ service.is_active ? 'Aktif' : 'Nonaktif' }}
              </Badge>
            </div>

            <div class="pt-2 border-t border-gray-100">
              <template v-if="service.pricing_type && service.price !== null">
                <p class="text-sm font-semibold text-gray-900">{{ formatPrice(service.price) }}</p>
                <p class="text-xs text-gray-400">{{ service.pricing_type.label }}</p>
              </template>
              <p v-else class="text-xs text-gray-400 italic">Harga dari pilihan editing</p>
            </div>

            <div class="flex gap-2 pt-1">
              <Button variant="outline" size="sm" class="flex-1" @click="openEdit(service)">
                <Pencil class="size-3.5" />
                Edit
              </Button>
              <Button
                variant="outline"
                size="sm"
                class="flex-1 border-red-200 text-red-600 hover:bg-red-50"
                @click="confirmDelete(service)"
              >
                <Trash2 class="size-3.5" />
                Hapus
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- ============================================================
           DESKTOP — tabel dengan thumbnail gambar (lg ke atas)
      ============================================================= -->
      <Card class="p-0 overflow-hidden hidden lg:block">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="text-left px-4 py-3 font-medium text-gray-600 w-16"></th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Nama</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Kategori</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Tipe</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Harga</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
                <th class="text-right px-4 py-3 font-medium text-gray-600">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="service in data.data"
                :key="service.uuid"
                class="hover:bg-gray-50 transition-colors"
              >
                <td class="px-4 py-3">
                  <div
                    class="w-12 h-12 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center shrink-0"
                  >
                    <img
                      v-if="service.image"
                      :src="service.image"
                      :alt="service.name"
                      class="w-full h-full object-cover"
                      loading="lazy"
                    />
                    <ImageOff v-else class="size-4 text-gray-300" />
                  </div>
                </td>
                <td class="px-4 py-3">
                  <p class="font-medium text-gray-900 truncate">{{ service.name }}</p>
                  <p class="text-xs text-gray-400 truncate">{{ service.description }}</p>
                </td>
                <td class="px-4 py-3">
                  <Badge
                    variant="outline"
                    class="border-0"
                    :class="
                      isRental(service)
                        ? 'bg-purple-50 text-purple-700'
                        : 'bg-blue-50 text-blue-700'
                    "
                  >
                    {{ isRental(service) ? 'Sewa' : 'Jasa' }}
                  </Badge>
                </td>
                <td class="px-4 py-3 text-gray-600">{{ service.type.label }}</td>
                <td class="px-4 py-3">
                  <template v-if="service.pricing_type && service.price !== null">
                    <span class="font-medium text-gray-900">{{ formatPrice(service.price) }}</span>
                    <span class="text-xs text-gray-400 block">{{ service.pricing_type.label }}</span>
                  </template>
                  <span v-else class="text-xs text-gray-400 italic">Harga dari pilihan editing</span>
                </td>
                <td class="px-4 py-3">
                  <Badge
                    variant="outline"
                    class="border-0"
                    :class="
                      service.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'
                    "
                  >
                    {{ service.is_active ? 'Aktif' : 'Nonaktif' }}
                  </Badge>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-1">
                    <button
                      title="Edit"
                      @click="openEdit(service)"
                      class="p-1.5 rounded-md text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                    >
                      <Pencil class="size-4" />
                    </button>
                    <button
                      title="Hapus"
                      @click="confirmDelete(service)"
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
      </Card>

      <!-- Pagination — dipakai bersama untuk kedua layout -->
      <div
        v-if="data && data.meta.last_page > 1"
        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-sm text-gray-600"
      >
        <span>Halaman {{ data.meta.current_page }} dari {{ data.meta.last_page }}</span>
        <div class="flex gap-2 justify-end">
          <Button variant="outline" size="icon-sm" :disabled="page <= 1" @click="page -= 1">
            <ChevronLeft class="size-4" />
          </Button>
          <Button
            variant="outline"
            size="icon-sm"
            :disabled="page >= data.meta.last_page"
            @click="page += 1"
          >
            <ChevronRight class="size-4" />
          </Button>
        </div>
      </div>
    </template>

    <ServiceFormModal :show="showModal" :service="selectedService" @close="showModal = false" />
  </div>
</template>