<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { photoApi } from '@/features/photo/api/photoApi'
import PhotoStatusBadge from '@/features/photo/components/PhotoStatusBadge.vue'
import PhotoGrid from '@/features/photo/components/PhotoGrid.vue'
import PhotoUploader from '@/features/photo/components/PhotoUploader.vue'
import { toast } from 'vue-sonner'

const route = useRoute()
const uuid = computed(() => route.params.uuid as string)
const queryClient = useQueryClient()

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
// NEW — pecah preview jadi dipilih vs tidak, berdasarkan flag is_selected dari backend
const selectedPreviews = computed(() => previews.value.filter((f) => f.is_selected))
const unselectedPreviews = computed(() => previews.value.filter((f) => !f.is_selected))

const editedFiles = computed(
  () => project.value?.files.filter((f) => f.type.value === 'edited') ?? [],
)
const finalFiles = computed(
  () => project.value?.files.filter((f) => f.type.value === 'final') ?? [],
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
  <div v-if="isLoading" class="p-8 text-center text-gray-500 text-sm">Memuat data...</div>

  <div v-else-if="project" class="space-y-8">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ project.booking.booking_code }}</h2>
        <p class="text-gray-500 mt-1 text-sm">{{ project.booking.user?.name }}</p>
      </div>
      <PhotoStatusBadge :status="project.status" />
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-6 text-sm">
      <div>
        <span class="text-gray-400">Preview:</span> <b>{{ project.preview_count }}</b>
      </div>
      <div>
        <span class="text-gray-400">Dipilih:</span>
        <b>{{ project.selection_count }} / {{ project.max_selection || '∞' }}</b>
      </div>
      <div v-if="project.customer_note">
        <span class="text-gray-400">Catatan customer:</span> {{ project.customer_note }}
      </div>
      <div v-if="project.expires_at">
        <span class="text-gray-400">Berlaku sampai:</span>
        {{ new Date(project.expires_at).toLocaleDateString('id-ID') }}
      </div>
    </div>

    <!-- Upload Preview -->
    <section class="space-y-3">
      <h3 class="font-semibold text-gray-900">Upload Preview</h3>
      <PhotoUploader :disabled="isUploadingPreview" @upload="uploadPreviews" />
    </section>

    <!-- NEW: section foto yang dipilih customer, ditonjolkan dengan border biru -->
    <section v-if="selectedPreviews.length" class="space-y-3">
      <h3 class="font-semibold text-gray-900 flex items-center gap-2">
        <span
          class="w-5 h-5 rounded-full bg-blue-500 text-white text-xs flex items-center justify-center"
          >✓</span
        >
        Dipilih Customer ({{ selectedPreviews.length }})
        <span class="text-xs font-normal text-gray-400">— kerjakan ini dulu</span>
      </h3>
      <div class="rounded-xl border-2 border-blue-200 bg-blue-50/40 p-3">
        <PhotoGrid :files="selectedPreviews" />
      </div>
    </section>

    <!-- Sisa preview yang tidak dipilih -->
    <section v-if="unselectedPreviews.length" class="space-y-3">
      <h3 class="font-semibold text-gray-500">Tidak Dipilih ({{ unselectedPreviews.length }})</h3>
      <div class="opacity-60">
        <PhotoGrid :files="unselectedPreviews" />
      </div>
    </section>

    <section v-else-if="!selectedPreviews.length" class="space-y-3">
      <h3 class="font-semibold text-gray-900">Semua Preview ({{ previews.length }})</h3>
      <PhotoGrid :files="previews" />
    </section>

    <!-- Upload Hasil Edit -->
    <section
      v-if="['selection', 'editing', 'approval'].includes(project.status.value)"
      class="space-y-3"
    >
      <h3 class="font-semibold text-gray-900">Hasil Edit ({{ editedFiles.length }})</h3>
      <PhotoUploader :disabled="isUploadingEdited" @upload="uploadEdited" />
      <PhotoGrid :files="editedFiles" />

      <button
        v-if="project.status.value === 'editing'"
        :disabled="isSubmitting || editedFiles.length === 0"
        @click="submitApproval()"
        class="px-4 py-2 rounded-xl bg-gray-900 text-white text-sm font-medium disabled:opacity-40"
      >
        {{ isSubmitting ? 'Mengirim...' : 'Kirim untuk Approval Customer' }}
      </button>
    </section>

    <!-- Final -->
    <section v-if="finalFiles.length" class="space-y-3">
      <h3 class="font-semibold text-gray-900">File Final ({{ finalFiles.length }})</h3>
      <PhotoGrid :files="finalFiles" />
    </section>
  </div>
</template>
