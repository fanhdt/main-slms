<script setup lang="ts">
import { ref } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { useRouter } from 'vue-router'
import { photoApi } from '@/features/photo/api/photoApi'
import { bookingApi } from '@/features/booking/api/bookingApi'
import PhotoStatusBadge from '@/features/photo/components/PhotoStatusBadge.vue'
import BaseModal from '@/components/BaseModal.vue'
import { toast } from 'vue-sonner'

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
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Photo Delivery</h2>
        <p class="text-gray-500 mt-1 text-sm">
          Kelola project foto untuk booking yang sudah selesai.
        </p>
      </div>
      <button
        @click="showCreateModal = true"
        class="px-4 py-2 rounded-xl bg-gray-900 text-white text-sm font-medium hover:bg-gray-800"
      >
        + Buka Project Baru
      </button>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div v-if="isLoading" class="p-8 text-center text-gray-500 text-sm">Memuat data...</div>
      <div v-else-if="!data?.data?.length" class="p-8 text-center text-gray-500 text-sm">
        Belum ada photo project.
      </div>
      <table v-else class="w-full text-sm">
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
            class="hover:bg-gray-50 cursor-pointer"
            @click="openDetail(project.uuid)"
          >
            <td class="px-4 py-3 font-mono text-xs">{{ project.booking.booking_code }}</td>
            <td class="px-4 py-3">{{ project.booking.user?.name }}</td>
            <td class="px-4 py-3">{{ project.preview_count }}</td>
            <td class="px-4 py-3">
              {{ project.selection_count }} / {{ project.max_selection || '∞' }}
            </td>
            <td class="px-4 py-3"><PhotoStatusBadge :status="project.status" /></td>
            <td class="px-4 py-3 text-right text-blue-600 text-xs hover:underline">Buka →</td>
          </tr>
        </tbody>
      </table>
    </div>

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
            class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
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
            class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
          />
        </div>
      </div>
      <template #footer>
        <button
          @click="showCreateModal = false"
          class="px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100"
        >
          Batal
        </button>
        <button
          :disabled="!selectedBookingUuid || isCreating"
          @click="createProject()"
          class="px-4 py-2 rounded-lg text-sm bg-gray-900 text-white disabled:opacity-40"
        >
          {{ isCreating ? 'Membuat...' : 'Buat Project' }}
        </button>
      </template>
    </BaseModal>
  </div>
</template>
