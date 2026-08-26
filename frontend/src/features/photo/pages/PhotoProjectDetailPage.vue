<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { photoApi } from '@/features/photo/api/photoApi'
import PhotoStatusBadge from '@/features/photo/components/PhotoStatusBadge.vue'
import PhotoGrid from '@/features/photo/components/PhotoGrid.vue'
import PhotoUploader from '@/features/photo/components/PhotoUploader.vue'
import { toast } from 'vue-sonner'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Camera, Pencil, CheckCircle2, CheckCheck, AlertTriangle } from 'lucide-vue-next'
import { useConfirmDialog } from '@/composables/useConfirmDialog'

const route = useRoute()
const uuid = computed(() => route.params.uuid as string)
const queryClient = useQueryClient()
const { confirmDelete } = useConfirmDialog()

const activeTab = ref<'preview' | 'edit' | 'final'>('preview')

const { data: project, isLoading } = useQuery({
  queryKey: ['photo-project', uuid],
  queryFn: async () => {
    const res = await photoApi.getByUuid(uuid.value)
    return res.data.data
  },
})

const previews = computed(
  () => project.value?.files.filter((f) => f.type.value === 'preview') ?? [],
)
const selectedPreviews = computed(() => previews.value.filter((f) => f.is_selected))
const unselectedPreviews = computed(() => previews.value.filter((f) => !f.is_selected))

const editedFiles = computed(
  () => project.value?.files.filter((f) => f.type.value === 'edited') ?? [],
)
const finalFiles = computed(
  () => project.value?.files.filter((f) => f.type.value === 'final') ?? [],
)

const hasPendingSelection = computed(() => project.value?.status.value === 'preview_uploaded')
const hasRevisionNote = computed(
  () => !!project.value?.editor_note && project.value?.status.value === 'editing',
)

function invalidate() {
  queryClient.invalidateQueries({ queryKey: ['photo-project', uuid] })
  queryClient.invalidateQueries({ queryKey: ['photo-projects'] })
}

const { mutate: uploadPreviews, isPending: isUploadingPreview } = useMutation({
  mutationFn: (files: File[]) => photoApi.uploadPreviews(uuid.value, files),
  onSuccess: () => {
    toast.success('Preview berhasil diupload.')
    invalidate()
  },
  onError: (err: any) => toast.error(err.response?.data?.message ?? 'Gagal upload preview.'),
})

const { mutate: uploadEdited, isPending: isUploadingEdited } = useMutation({
  mutationFn: (files: File[]) => photoApi.uploadEdited(uuid.value, files),
  onSuccess: () => {
    toast.success('Hasil edit berhasil diupload.')
    invalidate()
  },
  onError: (err: any) => toast.error(err.response?.data?.message ?? 'Gagal upload hasil edit.'),
})

const { mutate: deleteFile } = useMutation({
  mutationFn: (fileUuid: string) => photoApi.deleteFile(uuid.value, fileUuid),
  onSuccess: () => {
    toast.success('Foto berhasil dihapus.')
    invalidate()
  },
  onError: (err: any) => toast.error(err.response?.data?.message ?? 'Gagal menghapus foto.'),
})

async function handleDeleteFile(fileUuid: string) {
  const ok = await confirmDelete({
    title: 'Hapus foto ini?',
    text: 'Tindakan ini tidak bisa dibatalkan.',
  })
  if (ok) deleteFile(fileUuid)
}

const { mutate: submitApproval, isPending: isSubmitting } = useMutation({
  mutationFn: () => photoApi.submitForApproval(uuid.value),
  onSuccess: () => {
    toast.success('Dikirim untuk approval customer.')
    invalidate()
  },
  onError: (err: any) => toast.error(err.response?.data?.message ?? 'Gagal mengirim approval.'),
})
</script>

<template>
  <div v-if="isLoading" class="space-y-4">
    <Skeleton class="h-10 w-64" />
    <Skeleton class="h-20 w-full" />
    <Skeleton class="h-64 w-full" />
  </div>

  <div v-else-if="project" class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">
          {{ project.booking.booking_code }}
        </h1>
        <p class="text-gray-500 mt-0.5 text-sm">{{ project.booking.user?.name }}</p>
      </div>
      <PhotoStatusBadge :status="project.status" />
    </div>

    <Card class="p-0">
      <CardContent class="p-4 flex flex-wrap gap-6 text-sm">
        <div>
          <span class="text-gray-400">Preview:</span> <b>{{ project.preview_count }}</b>
        </div>
        <div>
          <span class="text-gray-400">Dipilih:</span>
          <b>{{ project.selection_count }} / {{ project.max_selection || '∞' }}</b>
        </div>
        <div v-if="project.customer_note">
          <span class="text-gray-400">Catatan awal customer:</span> {{ project.customer_note }}
        </div>
        <div v-if="project.expires_at">
          <span class="text-gray-400">Berlaku sampai:</span>
          {{ new Date(project.expires_at).toLocaleDateString('id-ID') }}
        </div>
      </CardContent>
    </Card>

    <!-- Tab navigasi -->
    <div class="flex gap-1 border-b border-gray-200">
      <button
        @click="activeTab = 'preview'"
        class="relative px-4 py-2 text-sm font-medium border-b-2 transition-colors flex items-center gap-1.5"
        :class="
          activeTab === 'preview'
            ? 'border-gray-900 text-gray-900'
            : 'border-transparent text-gray-400 hover:text-gray-600'
        "
      >
        <Camera class="size-4" /> Preview
        <span v-if="hasPendingSelection" class="w-1.5 h-1.5 rounded-full bg-yellow-500" />
      </button>
      <button
        @click="activeTab = 'edit'"
        class="relative px-4 py-2 text-sm font-medium border-b-2 transition-colors flex items-center gap-1.5"
        :class="
          activeTab === 'edit'
            ? 'border-gray-900 text-gray-900'
            : 'border-transparent text-gray-400 hover:text-gray-600'
        "
      >
        <Pencil class="size-4" /> Edit & Revisi
        <span v-if="hasRevisionNote" class="w-1.5 h-1.5 rounded-full bg-red-500" />
      </button>
      <button
        @click="activeTab = 'final'"
        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors flex items-center gap-1.5"
        :class="
          activeTab === 'final'
            ? 'border-gray-900 text-gray-900'
            : 'border-transparent text-gray-400 hover:text-gray-600'
        "
      >
        <CheckCircle2 class="size-4" /> Hasil Final
      </button>
    </div>

    <!-- TAB: Preview -->
    <template v-if="activeTab === 'preview'">
      <section class="space-y-3">
        <h3 class="font-semibold text-gray-900">Upload Preview</h3>
        <PhotoUploader :disabled="isUploadingPreview" @upload="uploadPreviews" />
      </section>

      <section v-if="selectedPreviews.length" class="space-y-3">
        <h3 class="font-semibold text-gray-900 flex items-center gap-2">
          <span
            class="w-5 h-5 rounded-full bg-blue-500 text-white flex items-center justify-center"
          >
            <CheckCheck class="size-3" />
          </span>
          Dipilih Customer ({{ selectedPreviews.length }})
          <span class="text-xs font-normal text-gray-400">— kerjakan ini di tab Edit</span>
        </h3>
        <div class="rounded-xl border-2 border-blue-200 bg-blue-50/40 p-3">
          <PhotoGrid :files="selectedPreviews" />
        </div>
      </section>

      <section v-if="unselectedPreviews.length" class="space-y-3">
        <h3 class="font-semibold text-gray-500">Tidak Dipilih ({{ unselectedPreviews.length }})</h3>
        <div class="opacity-60">
          <PhotoGrid :files="unselectedPreviews" deletable @delete="handleDeleteFile" />
        </div>
      </section>

      <section v-if="!previews.length" class="text-center text-gray-400 text-sm py-10">
        Belum ada preview diupload.
      </section>
    </template>

    <!-- TAB: Edit & Revisi -->
    <template v-else-if="activeTab === 'edit'">
      <Card
        v-if="project.status.value === 'pending' || project.status.value === 'preview_uploaded'"
        class="p-0"
      >
        <CardContent class="p-4 text-sm text-gray-500">
          Belum ada foto yang dipilih customer. Cek tab Preview dulu.
        </CardContent>
      </Card>

      <template v-else>
        <div
          v-if="project.editor_note"
          class="rounded-xl border-2 border-orange-200 bg-orange-50 p-4"
        >
          <p class="text-sm font-semibold text-orange-800 flex items-center gap-2">
            <AlertTriangle class="size-4" />
            Customer meminta revisi
          </p>
          <p class="text-sm text-orange-700 mt-1">{{ project.editor_note }}</p>
        </div>

        <section class="space-y-2">
          <h3 class="text-sm font-semibold text-gray-700">Referensi — Foto Dipilih Customer</h3>
          <PhotoGrid :files="selectedPreviews" />
        </section>

        <section class="space-y-3">
          <h3 class="font-semibold text-gray-900">Upload Hasil Edit ({{ editedFiles.length }})</h3>
          <PhotoUploader :disabled="isUploadingEdited" @upload="uploadEdited" />
          <PhotoGrid :files="editedFiles" deletable @delete="handleDeleteFile" />

          <Button
            v-if="project.status.value === 'editing'"
            :disabled="isSubmitting || editedFiles.length === 0"
            @click="submitApproval()"
          >
            {{ isSubmitting ? 'Mengirim...' : 'Kirim untuk Approval Customer' }}
          </Button>

          <p
            v-if="project.status.value === 'approval'"
            class="text-sm text-blue-600 bg-blue-50 rounded-lg px-3 py-2"
          >
            Menunggu customer approve atau minta revisi.
          </p>
        </section>
      </template>
    </template>

    <!-- TAB: Hasil Final -->
    <template v-else>
      <section v-if="finalFiles.length" class="space-y-3">
        <h3 class="font-semibold text-gray-900">File Final ({{ finalFiles.length }})</h3>
        <PhotoGrid :files="finalFiles" />
      </section>
      <div v-else class="text-center text-gray-400 text-sm py-10">
        Belum ada file final — customer belum approve hasil edit.
      </div>
    </template>
  </div>
</template>
