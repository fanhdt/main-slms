<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery, useMutation } from '@tanstack/vue-query'
import { photoApi } from '@/features/photo/api/photoApi'
import PhotoGrid from '@/features/photo/components/PhotoGrid.vue'
import CustomerNavbar from '@/components/CustomerNavbar.vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { CheckCheck } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const uuid = computed(() => route.params.uuid as string)
const selected = ref<string[]>([])
const note = ref('')

const { data: project, isLoading } = useQuery({
  queryKey: ['photo-project-selection', uuid],
  queryFn: async () => {
    const res = await photoApi.getByUuid(uuid.value)
    return res.data.data
  },
})

const previews = computed(
  () => project.value?.files.filter((f) => f.type.value === 'preview') ?? [],
)

const selectedFiles = computed(() => previews.value.filter((f) => selected.value.includes(f.uuid)))
const unselectedFiles = computed(() =>
  previews.value.filter((f) => !selected.value.includes(f.uuid)),
)

const quota = computed(() => project.value?.max_selection ?? 0)
const quotaReached = computed(() => quota.value > 0 && selected.value.length >= quota.value)

function toggle(fileUuid: string) {
  if (selected.value.includes(fileUuid)) {
    selected.value = selected.value.filter((u) => u !== fileUuid)
    return
  }
  if (quotaReached.value) {
    toast.error(`Maksimal ${quota.value} foto.`)
    return
  }
  selected.value = [...selected.value, fileUuid]
}

const { mutate: submit, isPending } = useMutation({
  mutationFn: () => photoApi.submitSelection(uuid.value, selected.value, note.value || undefined),
  onSuccess: () => {
    toast.success('Pilihan foto berhasil dikirim!')
    router.push('/my-bookings')
  },
  onError: (err: any) => toast.error(err.response?.data?.message ?? 'Gagal mengirim pilihan.'),
})
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <CustomerNavbar back-to="/my-bookings" title="Pilih Foto" />

    <div v-if="isLoading" class="max-w-4xl mx-auto p-4 space-y-4">
      <Skeleton class="h-10 w-64" />
      <Skeleton class="h-64 w-full" />
    </div>

    <div v-else-if="project" class="max-w-4xl mx-auto p-4 space-y-6">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Pilih Foto Favoritmu</h1>
        <p class="text-gray-500 text-sm mt-1">
          Booking {{ project.booking.booking_code }} — pilih
          {{ quota > 0 ? `maksimal ${quota} foto` : 'sebanyak yang kamu mau' }}.
        </p>
      </div>

      <div
        v-if="project.status.value !== 'preview_uploaded'"
        class="bg-yellow-50 text-yellow-700 text-sm rounded-xl p-4"
      >
        Project ini sudah tidak dalam tahap pemilihan foto (status: {{ project.status.label }}).
      </div>

      <template v-else>
        <div
          class="sticky top-16 z-10 bg-gray-50/95 backdrop-blur py-2 flex items-center justify-between border-b border-gray-200"
        >
          <p class="text-sm font-medium text-gray-700">
            {{ selected.length }} {{ quota > 0 ? `/ ${quota}` : '' }} foto dipilih
          </p>
          <Button :disabled="selected.length === 0 || isPending" @click="submit()">
            {{ isPending ? 'Mengirim...' : 'Kirim Pilihan' }}
          </Button>
        </div>

        <section v-if="selectedFiles.length" class="space-y-3">
          <h3 class="font-semibold text-gray-900 flex items-center gap-2">
            <span
              class="w-5 h-5 rounded-full bg-blue-500 text-white flex items-center justify-center"
            >
              <CheckCheck class="size-3" />
            </span>
            Foto yang Kamu Pilih ({{ selectedFiles.length }})
          </h3>
          <PhotoGrid
            :files="selectedFiles"
            selectable
            :selected-uuids="selected"
            @toggle="toggle"
          />
        </section>

        <section class="space-y-3">
          <h3 class="font-semibold text-gray-900">Semua Preview ({{ unselectedFiles.length }})</h3>
          <PhotoGrid
            :files="unselectedFiles"
            selectable
            :selected-uuids="selected"
            @toggle="toggle"
          />
        </section>

        <div>
          <label class="text-sm font-medium text-gray-700">Catatan untuk editor (opsional)</label>
          <textarea
            v-model="note"
            rows="3"
            class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Misal: tolong dibuat lebih cerah, dsb."
          />
        </div>
      </template>
    </div>
  </div>
</template>
