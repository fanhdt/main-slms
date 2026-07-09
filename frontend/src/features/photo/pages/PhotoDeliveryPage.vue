<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { photoApi } from '@/features/photo/api/photoApi'
import PhotoGrid from '@/features/photo/components/PhotoGrid.vue'
import PhotoStatusBadge from '@/features/photo/components/PhotoStatusBadge.vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Camera, History, Download, CircleAlert } from 'lucide-vue-next'

const route = useRoute()
const uuid = computed(() => route.params.uuid as string)
const queryClient = useQueryClient()
const revisionNote = ref('')
const showRevisionForm = ref(false)
const isDownloadingAll = ref(false)

const activeTab = ref<'result' | 'history'>('result')

const { data: project, isLoading } = useQuery({
  queryKey: ['photo-project-delivery', uuid],
  queryFn: async () => {
    const res = await photoApi.getByUuid(uuid.value)
    return res.data.data
  },
})

const previews = computed(
  () => project.value?.files.filter((f) => f.type.value === 'preview') ?? [],
)
const editedFiles = computed(
  () => project.value?.files.filter((f) => f.type.value === 'edited') ?? [],
)
const finalFiles = computed(
  () => project.value?.files.filter((f) => f.type.value === 'final') ?? [],
)
const selectedPreviews = computed(() => previews.value.filter((f) => f.is_selected))

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

async function downloadAll() {
  isDownloadingAll.value = true
  try {
    const res = await photoApi.downloadAll(uuid.value)
    const blob = new Blob([res.data], { type: 'application/zip' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `foto-${project.value?.booking.booking_code}.zip`
    link.click()
    window.URL.revokeObjectURL(url)
    toast.success('Semua foto berhasil didownload.')
  } catch (err: any) {
    toast.error('Gagal download semua foto. Coba download satu-satu.')
  } finally {
    isDownloadingAll.value = false
  }
}
</script>

<template>
  <div v-if="isLoading" class="max-w-4xl mx-auto p-4 space-y-4">
    <Skeleton class="h-10 w-64" />
    <Skeleton class="h-64 w-full" />
  </div>

  <div v-else-if="project" class="max-w-4xl mx-auto p-4 space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold tracking-tight text-gray-900">
        Hasil Foto — {{ project.booking.booking_code }}
      </h1>
      <PhotoStatusBadge :status="project.status" />
    </div>

    <!-- Tab navigasi -->
    <div class="flex gap-1 border-b border-gray-200">
      <button
        @click="activeTab = 'result'"
        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors flex items-center gap-1.5"
        :class="
          activeTab === 'result'
            ? 'border-gray-900 text-gray-900'
            : 'border-transparent text-gray-400 hover:text-gray-600'
        "
      >
        <Camera class="size-4" /> Hasil Foto
      </button>
      <button
        @click="activeTab = 'history'"
        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors flex items-center gap-1.5"
        :class="
          activeTab === 'history'
            ? 'border-gray-900 text-gray-900'
            : 'border-transparent text-gray-400 hover:text-gray-600'
        "
      >
        <History class="size-4" /> Riwayat & Preview Awal
      </button>
    </div>

    <!-- TAB: Hasil Foto -->
    <template v-if="activeTab === 'result'">
      <template v-if="project.status.value === 'approval'">
        <p class="text-sm text-gray-500">
          Cek hasil edit di bawah, lalu setujui atau minta revisi.
        </p>
        <PhotoGrid :files="editedFiles" />

        <div v-if="!showRevisionForm" class="flex gap-3">
          <Button
            :disabled="isPending"
            class="bg-green-600 hover:bg-green-700"
            @click="resolve({ decision: 'approve' })"
          >
            Setujui Hasil
          </Button>
          <Button variant="outline" @click="showRevisionForm = true">Minta Revisi</Button>
        </div>

        <div v-else class="space-y-3 bg-orange-50 rounded-xl p-4">
          <label class="text-sm font-medium text-gray-700">Apa yang perlu direvisi?</label>
          <textarea
            v-model="revisionNote"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-400"
          />
          <div class="flex gap-2">
            <Button
              :disabled="!revisionNote || isPending"
              class="bg-orange-600 hover:bg-orange-700"
              @click="resolve({ decision: 'revise', note: revisionNote })"
            >
              Kirim Revisi
            </Button>
            <Button variant="ghost" @click="showRevisionForm = false">Batal</Button>
          </div>
        </div>
      </template>

      <template v-else-if="project.status.value === 'delivered'">
        <div class="flex items-center justify-between">
          <p class="text-sm text-gray-500">
            File siap didownload sampai
            {{ new Date(project.expires_at!).toLocaleDateString('id-ID') }}.
          </p>
          <Button size="sm" :disabled="isDownloadingAll" @click="downloadAll">
            <Download class="size-3.5" />
            {{ isDownloadingAll ? 'Menyiapkan ZIP...' : 'Download Semua (.zip)' }}
          </Button>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
          <div v-for="file in finalFiles" :key="file.uuid" class="space-y-2">
            <img :src="file.url" :alt="file.filename" class="w-full h-40 object-cover rounded-xl" />
            <Button size="sm" class="w-full" @click="download(file.uuid, file.filename)">
              Download
            </Button>
          </div>
        </div>
      </template>

      <template v-else-if="project.status.value === 'expired'">
        <div class="bg-red-50 text-red-700 text-sm rounded-xl p-4 flex items-center gap-2">
          <CircleAlert class="size-4 shrink-0" />
          Masa akses file untuk booking ini sudah berakhir.
        </div>
      </template>

      <template v-else>
        <div class="bg-blue-50 text-blue-700 text-sm rounded-xl p-4">
          Fotomu masih dalam proses ({{ project.status.label }}). Cek lagi nanti ya.
        </div>
      </template>
    </template>

    <!-- TAB: Riwayat & Preview Awal -->
    <template v-else>
      <div v-if="project.editor_note" class="rounded-xl border-2 border-orange-200 bg-orange-50 p-4">
        <p class="text-sm font-semibold text-orange-800">
          Catatan revisi terakhir yang kamu kirim:
        </p>
        <p class="text-sm text-orange-700 mt-1">{{ project.editor_note }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-gray-900 mb-2">
          Foto yang Kamu Pilih Sebelumnya ({{ selectedPreviews.length }})
        </h3>
        <PhotoGrid :files="selectedPreviews" />
      </div>

      <div
        v-if="!project.editor_note && !selectedPreviews.length"
        class="text-center text-gray-400 text-sm py-8"
      >
        Belum ada riwayat untuk ditampilkan.
      </div>
    </template>
  </div>
</template>