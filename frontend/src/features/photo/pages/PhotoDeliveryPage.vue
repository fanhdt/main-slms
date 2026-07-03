<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { photoApi } from '@/features/photo/api/photoApi'
import PhotoGrid from '@/features/photo/components/PhotoGrid.vue'
import PhotoStatusBadge from '@/features/photo/components/PhotoStatusBadge.vue'
import { toast } from 'vue-sonner'

const route = useRoute()
const uuid = computed(() => route.params.uuid as string)
const queryClient = useQueryClient()
const revisionNote = ref('')
const showRevisionForm = ref(false)

const { data: project, isLoading } = useQuery({
  queryKey: ['photo-project-delivery', uuid],
  queryFn: async () => {
    const res = await photoApi.getByUuid(uuid.value)
    return res.data.data
  },
})

const editedFiles = computed(
  () => project.value?.files.filter((f) => f.type.value === 'edited') ?? [],
)
const finalFiles = computed(
  () => project.value?.files.filter((f) => f.type.value === 'final') ?? [],
)

function invalidate() {
  queryClient.invalidateQueries({ queryKey: ['photo-project-delivery', uuid] })
}

const { mutate: resolve, isPending } = useMutation({
  mutationFn: (payload: { decision: 'approve' | 'revise'; note?: string }) =>
    photoApi.resolveApproval(uuid.value, payload.decision, payload.note),
  onSuccess: (_, variables) => {
    toast.success(
      variables.decision === 'approve' ? 'Hasil foto disetujui!' : 'Revisi dikirim ke editor.',
    )
    showRevisionForm.value = false
    revisionNote.value = ''
    invalidate()
  },
  onError: (err: any) => toast.error(err.response?.data?.message ?? 'Gagal memproses.'),
})

async function download(fileUuid: string, filename: string) {
  try {
    const res = await photoApi.downloadFile(uuid.value, fileUuid)
    const link = document.createElement('a')
    link.href = res.data.data.url
    link.download = filename
    link.target = '_blank'
    link.click()
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Gagal mendownload file.')
  }
}
</script>

<template>
  <div v-if="isLoading" class="p-8 text-center text-gray-500 text-sm">Memuat data...</div>

  <div v-else-if="project" class="max-w-4xl mx-auto p-4 space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-2xl font-bold text-gray-900">
        Hasil Foto — {{ project.booking.booking_code }}
      </h2>
      <PhotoStatusBadge :status="project.status" />
    </div>

    <template v-if="project.status.value === 'approval'">
      <p class="text-sm text-gray-500">Cek hasil edit di bawah, lalu setujui atau minta revisi.</p>
      <PhotoGrid :files="editedFiles" />

      <div v-if="!showRevisionForm" class="flex gap-3">
        <button
          :disabled="isPending"
          @click="resolve({ decision: 'approve' })"
          class="px-6 py-2.5 rounded-xl bg-green-600 text-white text-sm font-medium disabled:opacity-40"
        >
          Setujui Hasil
        </button>
        <button
          @click="showRevisionForm = true"
          class="px-6 py-2.5 rounded-xl border border-gray-300 text-sm font-medium hover:bg-gray-50"
        >
          Minta Revisi
        </button>
      </div>

      <div v-else class="space-y-3 bg-orange-50 rounded-xl p-4">
        <label class="text-sm font-medium text-gray-700">Apa yang perlu direvisi?</label>
        <textarea
          v-model="revisionNote"
          rows="3"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
        />
        <div class="flex gap-2">
          <button
            :disabled="!revisionNote || isPending"
            @click="resolve({ decision: 'revise', note: revisionNote })"
            class="px-4 py-2 rounded-lg bg-orange-600 text-white text-sm disabled:opacity-40"
          >
            Kirim Revisi
          </button>
          <button
            @click="showRevisionForm = false"
            class="px-4 py-2 rounded-lg text-sm text-gray-600"
          >
            Batal
          </button>
        </div>
      </div>
    </template>

    <template v-else-if="project.status.value === 'delivered'">
      <p class="text-sm text-gray-500">
        File siap didownload sampai {{ new Date(project.expires_at!).toLocaleDateString('id-ID') }}.
      </p>
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
        <div v-for="file in finalFiles" :key="file.uuid" class="space-y-2">
          <img :src="file.url" :alt="file.filename" class="w-full h-40 object-cover rounded-xl" />
          <button
            @click="download(file.uuid, file.filename)"
            class="w-full text-xs py-1.5 rounded-lg bg-gray-900 text-white hover:bg-gray-800"
          >
            Download
          </button>
        </div>
      </div>
    </template>

    <template v-else-if="project.status.value === 'expired'">
      <div class="bg-red-50 text-red-700 text-sm rounded-xl p-4">
        Masa akses file untuk booking ini sudah berakhir.
      </div>
    </template>

    <template v-else>
      <div class="bg-blue-50 text-blue-700 text-sm rounded-xl p-4">
        Fotomu masih dalam proses ({{ project.status.label }}). Cek lagi nanti ya.
      </div>
    </template>
  </div>
</template>
