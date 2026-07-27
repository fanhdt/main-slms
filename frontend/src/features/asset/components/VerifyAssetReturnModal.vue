<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import api from '@/lib/axios'
import { toast } from 'vue-sonner'
import BaseModal from '@/components/BaseModal.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { CheckCircle2, TriangleAlert } from 'lucide-vue-next'

const props = defineProps<{
  show: boolean
  booking: any | null
}>()

const emit = defineEmits<{ close: [] }>()
const queryClient = useQueryClient()

// State catatan per item — di-reset tiap modal dibuka dengan booking baru
const notesByItem = ref<Record<number, string>>({})

watch(
  () => props.booking,
  () => {
    notesByItem.value = {}
  },
)

const assets = computed(() => props.booking?.assets ?? [])
const allVerified = computed(
  () =>
    assets.value.length > 0 &&
    assets.value.every((a: any) => a.status.value !== 'reserved' && a.status.value !== 'borrowed'),
)

const {
  mutate: verify,
  isPending,
  variables: pendingVariables,
} = useMutation({
  mutationFn: ({
    bookingAssetId,
    status,
  }: {
    bookingAssetId: number
    status: 'returned' | 'damaged'
  }) =>
    api.post(`/bookings/${props.booking.uuid}/assets/${bookingAssetId}/return`, {
      status,
      return_notes: notesByItem.value[bookingAssetId] || undefined,
    }),
  onSuccess: (res) => {
    toast.success('Status pengembalian tersimpan.')
    queryClient.invalidateQueries({ queryKey: ['bookings'] })
    // update booking lokal biar modal langsung reflect status baru tanpa perlu refetch manual
    Object.assign(props.booking, res.data.data)

    if (res.data.data.status.value === 'completed') {
      toast.success('Semua alat sudah diverifikasi — booking ditandai selesai.')
      emit('close')
    }
  },
  onError: (err: any) => {
    toast.error(err.response?.data?.message ?? 'Gagal menyimpan verifikasi.')
  },
})

function markReturned(bookingAssetId: number) {
  verify({ bookingAssetId, status: 'returned' })
}

function markDamaged(bookingAssetId: number) {
  if (
    !confirm(
      'Tandai alat ini rusak/hilang? Stoknya akan tetap ditahan sampai admin memperbarui status aset secara manual.',
    )
  ) {
    return
  }
  verify({ bookingAssetId, status: 'damaged' })
}

function isProcessing(bookingAssetId: number) {
  return isPending.value && pendingVariables.value?.bookingAssetId === bookingAssetId
}

const STATUS_STYLES: Record<string, string> = {
  reserved: 'bg-gray-100 text-gray-600',
  borrowed: 'bg-blue-50 text-blue-700',
  returned: 'bg-green-50 text-green-700',
  damaged: 'bg-red-50 text-red-700',
}
</script>

<template>
  <BaseModal :show="show" title="Verifikasi Pengembalian Alat" size="md" @close="$emit('close')">
    <div v-if="booking" class="space-y-4">
      <div class="text-sm text-gray-500">
        Booking
        <span class="font-mono font-medium text-gray-900">{{ booking.booking_code }}</span> —
        {{ booking.user?.name }}
      </div>

      <div
        v-if="allVerified"
        class="flex items-center gap-2 bg-green-50 text-green-700 text-sm rounded-lg px-3 py-2.5"
      >
        <CheckCircle2 class="size-4 shrink-0" />
        Semua alat sudah diverifikasi.
      </div>

      <div class="space-y-3">
        <div
          v-for="asset in assets"
          :key="asset.id"
          class="border border-gray-200 rounded-xl p-3 space-y-2"
        >
          <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">
                {{ asset.asset_name }} × {{ asset.quantity }}
              </p>
            </div>
            <Badge
              variant="outline"
              class="border-0 shrink-0"
              :class="STATUS_STYLES[asset.status.value]"
            >
              {{ asset.status.label }}
            </Badge>
          </div>

          <!-- Aksi verifikasi — hanya muncul kalau belum diverifikasi -->
          <template v-if="asset.status.value === 'reserved' || asset.status.value === 'borrowed'">
            <textarea
              v-model="notesByItem[asset.id]"
              rows="2"
              placeholder="Catatan kondisi alat (opsional)..."
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
            />
            <div class="flex gap-2">
              <Button
                size="sm"
                class="flex-1 bg-green-600 hover:bg-green-700"
                :disabled="isProcessing(asset.id)"
                @click="markReturned(asset.id)"
              >
                <CheckCircle2 class="size-3.5" />
                {{ isProcessing(asset.id) ? 'Menyimpan...' : 'Kembali Normal' }}
              </Button>
              <Button
                size="sm"
                variant="outline"
                class="flex-1 border-red-200 text-red-600 hover:bg-red-50"
                :disabled="isProcessing(asset.id)"
                @click="markDamaged(asset.id)"
              >
                <TriangleAlert class="size-3.5" />
                Rusak/Hilang
              </Button>
            </div>
          </template>

          <!-- Sudah diverifikasi -->
          <p v-else-if="asset.return_notes" class="text-xs text-gray-500">
            Catatan: {{ asset.return_notes }}
          </p>
        </div>
      </div>
    </div>

    <template #footer>
      <Button variant="ghost" @click="$emit('close')">Tutup</Button>
    </template>
  </BaseModal>
</template>
