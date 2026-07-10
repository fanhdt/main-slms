<script setup lang="ts">
import { ref } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { useRouter } from 'vue-router'
import { photoApi } from '@/features/photo/api/photoApi'
import { bookingApi } from '@/features/booking/api/bookingApi'
import PhotoStatusBadge from '@/features/photo/components/PhotoStatusBadge.vue'
import BaseModal from '@/components/BaseModal.vue'
import { toast } from 'vue-sonner'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Plus, Images, ArrowRight, ChevronLeft, ChevronRight } from 'lucide-vue-next'

const router = useRouter()
const page = ref(1)
const showCreateModal = ref(false)
const selectedBookingUuid = ref('')
const maxSelection = ref(10)
const queryClient = useQueryClient()

const { data, isLoading } = useQuery({
  queryKey: ['photo-projects', page],
  queryFn: async () => {
    const res = await photoApi.getAll({ page: page.value })
    return res.data.data
  },
})

const { data: completedBookings } = useQuery({
  queryKey: ['bookings-completed-for-photo', showCreateModal],
  queryFn: async () => {
    const res = await bookingApi.getAll({ status: 'completed', per_page: 50 })
    return res.data.data.data
  },
  enabled: showCreateModal,
})

const { mutate: createProject, isPending: isCreating } = useMutation({
  mutationFn: () =>
    photoApi.create({ booking_uuid: selectedBookingUuid.value, max_selection: maxSelection.value }),
  onSuccess: (res) => {
    toast.success('Photo project berhasil dibuat.')
    queryClient.invalidateQueries({ queryKey: ['photo-projects'] })
    showCreateModal.value = false
    router.push({ name: 'lab-photo-project-detail', params: { uuid: res.data.data.uuid } })
  },
  onError: (err: any) => {
    toast.error(err.response?.data?.message ?? 'Gagal membuat photo project.')
  },
})

function openDetail(uuid: string) {
  router.push({ name: 'lab-photo-project-detail', params: { uuid } })
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Photo Delivery</h1>
        <p class="text-sm text-gray-500 mt-0.5">
          Kelola project foto untuk booking yang sudah selesai.
        </p>
      </div>
      <Button size="sm" @click="showCreateModal = true">
        <Plus class="size-4" />
        Buka Project Baru
      </Button>
    </div>

    <Card class="p-0 overflow-hidden">
      <div v-if="isLoading" class="p-5 space-y-3">
        <Skeleton v-for="i in 5" :key="i" class="h-12 w-full" />
      </div>

      <div v-else-if="!data?.data?.length" class="p-10 text-center">
        <Images class="size-8 mx-auto text-gray-300 mb-2" />
        <p class="text-sm text-gray-500">Belum ada photo project.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Kode Booking</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Customer</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Preview</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Dipilih</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
              <th class="text-right px-4 py-3 font-medium text-gray-600">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="project in data.data"
              :key="project.uuid"
              class="hover:bg-gray-50 cursor-pointer transition-colors"
              @click="openDetail(project.uuid)"
            >
              <td class="px-4 py-3 font-mono text-xs">{{ project.booking.booking_code }}</td>
              <td class="px-4 py-3 truncate">{{ project.booking.user?.name }}</td>
              <td class="px-4 py-3">{{ project.preview_count }}</td>
              <td class="px-4 py-3">
                {{ project.selection_count }} / {{ project.max_selection || '∞' }}
              </td>
              <td class="px-4 py-3"><PhotoStatusBadge :status="project.status" /></td>
              <td class="px-4 py-3 text-right">
                <span class="text-blue-600 text-xs font-medium flex items-center justify-end gap-1">
                  Buka <ArrowRight class="size-3.5" />
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

    <BaseModal
      :show="showCreateModal"
      title="Buka Photo Project Baru"
      @close="showCreateModal = false"
    >
      <div class="space-y-4">
        <div>
          <label class="text-sm font-medium text-gray-700">Booking (status completed)</label>
          <select
            v-model="selectedBookingUuid"
            class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">-- Pilih booking --</option>
            <option v-for="b in completedBookings" :key="b.uuid" :value="b.uuid">
              {{ b.booking_code }} — {{ b.user?.name }}
            </option>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700"
            >Kuota Foto Dipilih (0 = unlimited)</label
          >
          <input
            v-model.number="maxSelection"
            type="number"
            min="0"
            class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>
      <template #footer>
        <Button variant="ghost" @click="showCreateModal = false">Batal</Button>
        <Button :disabled="!selectedBookingUuid || isCreating" @click="createProject()">
          {{ isCreating ? 'Membuat...' : 'Buat Project' }}
        </Button>
      </template>
    </BaseModal>
  </div>
</template>