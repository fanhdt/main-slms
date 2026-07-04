<script setup lang="ts">
import { ref, computed } from 'vue'
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

// Tab aktif: preview (semua preview + yang dipilih), edit (upload hasil edit + catatan revisi), final (hasil jadi)
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

// Badge kecil di tab, biar admin langsung tahu ada yang perlu dicek tanpa buka tab-nya dulu
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

  <div v-else-if="project" class="space-y-6">
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
        <span class="text-gray-400">Catatan awal customer:</span> {{ project.customer_note }}
      </div>
      <div v-if="project.expires_at">
        <span class="text-gray-400">Berlaku sampai:</span>
        {{ new Date(project.expires_at).toLocaleDateString('id-ID') }}
      </div>
    </div>

    <!-- Tab navigasi -->
    <div class="flex gap-1 border-b border-gray-200">
      <button
        @click="activeTab = 'preview'"
        class="relative px-4 py-2 text-sm font-medium border-b-2 transition-colors"
        :class="
          activeTab === 'preview'
            ? 'border-gray-900 text-gray-900'
            : 'border-transparent text-gray-400 hover:text-gray-600'
        "
      >
        📷 Preview
        <span
          v-if="hasPendingSelection"
          class="absolute -top-0.5 -right-1 w-2 h-2 rounded-full bg-yellow-500"
        />
      </button>
      <button
        @click="activeTab = 'edit'"
        class="relative px-4 py-2 text-sm font-medium border-b-2 transition-colors"
        :class="
          activeTab === 'edit'
            ? 'border-gray-900 text-gray-900'
            : 'border-transparent text-gray-400 hover:text-gray-600'
        "
      >
        ✏️ Edit & Revisi
        <span
          v-if="hasRevisionNote"
          class="absolute -top-0.5 -right-1 w-2 h-2 rounded-full bg-red-500"
        />
      </button>
      <button
        @click="activeTab = 'final'"
        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
        :class="
          activeTab === 'final'
            ? 'border-gray-900 text-gray-900'
            : 'border-transparent text-gray-400 hover:text-gray-600'
        "
      >
        ✅ Hasil Final
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
            class="w-5 h-5 rounded-full bg-blue-500 text-white text-xs flex items-center justify-center"
            >✓</span
          >
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
          <PhotoGrid :files="unselectedPreviews" />
        </div>
      </section>

      <section v-if="!previews.length" class="text-center text-gray-400 text-sm py-10">
        Belum ada preview diupload.
      </section>
    </template>

    <!-- TAB: Edit & Revisi -->
    <template v-else-if="activeTab === 'edit'">
      <div
        v-if="project.status.value === 'pending' || project.status.value === 'preview_uploaded'"
        class="bg-gray-50 text-gray-500 text-sm rounded-xl p-4"
      >
        Belum ada foto yang dipilih customer. Cek tab Preview dulu.
      </div>

      <template v-else>
        <!-- Catatan revisi dari customer, paling atas biar tidak kelewat -->
        <div
          v-if="project.editor_note"
          class="rounded-xl border-2 border-orange-200 bg-orange-50 p-4"
        >
          <p class="text-sm font-semibold text-orange-800 flex items-center gap-2">
            ⚠ Customer meminta revisi
          </p>
          <p class="text-sm text-orange-700 mt-1">{{ project.editor_note }}</p>
        </div>

        <!-- Referensi foto yang dipilih, biar editor gampang cocokin -->
        <section class="space-y-2">
          <h3 class="text-sm font-semibold text-gray-700">Referensi — Foto Dipilih Customer</h3>
          <PhotoGrid :files="selectedPreviews" />
        </section>

        <section class="space-y-3">
          <h3 class="font-semibold text-gray-900">Upload Hasil Edit ({{ editedFiles.length }})</h3>
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
