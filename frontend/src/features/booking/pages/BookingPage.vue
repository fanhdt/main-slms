<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { useQuery } from '@tanstack/vue-query'
import api from '@/lib/axios'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { ArrowRight, FlaskConical } from 'lucide-vue-next'

const labStore = useLabStore()
const router = useRouter()

onMounted(async () => {
  await labStore.fetchLabs()
})

const { data: summary } = useQuery({
  queryKey: ['booking-summary'],
  queryFn: async () => {
    const res = await api.get('/bookings/my', { params: { per_page: 50 } })
    return res.data.data.data as any[]
  },
})

const pendingCount = computed(
  () => summary.value?.filter((b) => b.status.value === 'pending').length ?? 0,
)
const unpaidCount = computed(
  () => summary.value?.filter((b) => b.payment_status.value === 'unpaid').length ?? 0,
)
const nextBooking = computed(() => {
  const upcoming = (summary.value ?? [])
    .filter((b) => ['pending', 'approved'].includes(b.status.value))
    .sort((a, b) => new Date(a.start_time).getTime() - new Date(b.start_time).getTime())
  return upcoming[0] ?? null
})

function formatShortDate(date: string) {
  return new Date(date).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function selectLab(slug: string) {
  router.push(`/booking/${slug}`)
}

function labCoverImage(lab: any): string | null {
  return lab.branding?.hero_image ?? lab.branding?.logo ?? null
}
</script>

<template>
  <div>
    <!-- Ringkasan personal -->
    <Card v-if="nextBooking || pendingCount > 0 || unpaidCount > 0" class="p-0 mb-8">
      <CardContent class="p-5 flex flex-wrap items-center gap-4">
        <div v-if="nextBooking" class="flex-1 min-w-50">
          <p class="text-xs text-gray-400">Booking berikutnya</p>
          <p class="text-sm font-semibold text-gray-900 mt-0.5">
            {{ nextBooking.booking_code }} &middot;
            {{ formatShortDate(nextBooking.start_time) }}
          </p>
        </div>
        <Badge
          v-if="pendingCount > 0"
          variant="outline"
          class="border-0 bg-amber-50 text-amber-700"
        >
          {{ pendingCount }} menunggu persetujuan
        </Badge>
        <Badge v-if="unpaidCount > 0" variant="outline" class="border-0 bg-red-50 text-red-700">
          {{ unpaidCount }} belum dibayar
        </Badge>
        <RouterLink
          to="/my-bookings"
          class="ml-auto text-sm font-medium text-blue-600 hover:underline whitespace-nowrap flex items-center gap-1"
        >
          Lihat detail
          <ArrowRight class="size-3.5" />
        </RouterLink>
      </CardContent>
    </Card>

    <div class="text-center mb-12">
      <h1 class="text-3xl font-bold text-gray-900">Pilih Laboratorium</h1>
      <p class="text-gray-500 mt-2">Pilih laboratorium yang ingin kamu booking layanannya.</p>
    </div>

    <div v-if="labStore.loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <Skeleton v-for="i in 3" :key="i" class="h-72 w-full rounded-2xl" />
    </div>

    <div v-else-if="!labStore.labs.length" class="text-center py-16">
      <FlaskConical class="size-8 mx-auto text-gray-300 mb-2" />
      <p class="text-gray-400">Belum ada laboratorium tersedia.</p>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <button
        v-for="lab in labStore.labs"
        :key="lab.uuid"
        @click="selectLab(lab.slug)"
        class="group text-left bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-200"
      >
        <div
          class="h-48 flex items-center justify-center relative overflow-hidden"
          :style="
            !labCoverImage(lab) ? { backgroundColor: lab.branding.primary_color ?? '#1a1a2e' } : {}
          "
        >
          <img
            v-if="labCoverImage(lab)"
            :src="labCoverImage(lab)!"
            :alt="lab.name"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
          <template v-else>
            <span class="text-7xl font-black text-white/10 absolute">{{ lab.name.charAt(0) }}</span>
            <span class="text-4xl font-bold text-white relative z-10">{{
              lab.name.charAt(0)
            }}</span>
          </template>
          <Badge
            variant="outline"
            class="absolute top-3 right-3 border-0 bg-green-500/20 text-green-100 backdrop-blur-sm"
          >
            Aktif
          </Badge>
          <div
            v-if="labCoverImage(lab)"
            class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent"
          />
        </div>

        <div class="p-6">
          <h3
            class="font-semibold text-gray-900 text-lg group-hover:text-blue-600 transition-colors"
          >
            {{ lab.name }}
          </h3>
          <p class="text-sm text-gray-500 mt-1.5 line-clamp-2">
            {{ lab.description ?? 'Tidak ada deskripsi.' }}
          </p>
          <div class="mt-5 flex items-center justify-between">
            <div class="flex gap-1.5">
              <div
                class="w-3.5 h-3.5 rounded-full border border-gray-200"
                :style="{ backgroundColor: lab.branding.primary_color ?? '#ccc' }"
              />
              <div
                class="w-3.5 h-3.5 rounded-full border border-gray-200"
                :style="{ backgroundColor: lab.branding.secondary_color ?? '#ccc' }"
              />
            </div>
            <span
              class="text-sm text-blue-600 font-medium group-hover:underline flex items-center gap-1"
            >
              Lihat Layanan
              <ArrowRight class="size-3.5" />
            </span>
          </div>
        </div>
      </button>
    </div>
  </div>
</template>
