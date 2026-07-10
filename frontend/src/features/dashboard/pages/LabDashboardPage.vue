<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import {
  CalendarDays,
  Wallet,
  Package,
  Image,
  Plus,
  ExternalLink,
  TrendingUp,
  TrendingDown,
} from 'lucide-vue-next'
import { Line, Doughnut, Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { useRouter } from 'vue-router'
import StatCard from '@/components/common/StatCard.vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { Skeleton } from '@/components/ui/skeleton'
import api from '@/lib/axios'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Tooltip,
  Legend,
  Filler,
)

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const labStore = useLabStore()

const labSlug = computed(() => route.params.labSlug as string)

// ============================================================
// Data — endpoint SAMA seperti sebelumnya, hanya per_page dinaikkan
// supaya cukup data untuk chart (bukan endpoint baru).
// ============================================================
const { data: bookings, isLoading: bookingsLoading } = useQuery({
  queryKey: ['lab-bookings-stats', labSlug],
  queryFn: async () => {
    const lab = labStore.activeLab
    if (!lab) return null
    const res = await api.get('/bookings', { params: { lab_id: lab.id, per_page: 100 } })
    return res.data.data
  },
  enabled: computed(() => !!labStore.activeLab),
})

const { data: assets, isLoading: assetsLoading } = useQuery({
  queryKey: ['lab-assets-stats', labSlug],
  queryFn: async () => {
    const lab = labStore.activeLab
    if (!lab) return null
    const res = await api.get('/assets', { params: { lab_id: lab.id, per_page: 100 } })
    return res.data.data
  },
  enabled: computed(() => !!labStore.activeLab),
})

const { data: photoProjects, isLoading: photoLoading } = useQuery({
  queryKey: ['lab-photo-stats', labSlug],
  queryFn: async () => {
    const lab = labStore.activeLab
    if (!lab) return null
    const res = await api.get('/photo-projects', { params: { lab_id: lab.id, per_page: 50 } })
    return res.data.data
  },
  enabled: computed(() => !!labStore.activeLab),
})

const isLoadingStats = computed(
  () => bookingsLoading.value || assetsLoading.value || photoLoading.value,
)

// ============================================================
// Stat computations
// ============================================================
const totalBookings = computed(() => bookings.value?.meta?.total ?? 0)

const paidRevenue = computed(() => {
  const items = bookings.value?.data ?? []
  return items
    .filter((b: any) => b.payment_status?.value === 'paid')
    .reduce((sum: number, b: any) => sum + Number(b.total_price), 0)
})

const paidRate = computed(() => {
  const items = bookings.value?.data ?? []
  if (!items.length) return 0
  const paid = items.filter((b: any) => b.payment_status?.value === 'paid').length
  return Math.round((paid / items.length) * 100)
})

const totalAssets = computed(() => assets.value?.meta?.total ?? 0)
const availableAssets = computed(
  () => assets.value?.data?.filter((a: any) => a.status?.value === 'available').length ?? 0,
)

const activePhotoProjects = computed(() => photoProjects.value?.meta?.total ?? 0)
const pendingApproval = computed(
  () => photoProjects.value?.data?.filter((p: any) => p.status?.value === 'approval').length ?? 0,
)

const recentBookings = computed(() => (bookings.value?.data ?? []).slice(0, 6))

function formatCurrency(value: number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    notation: value >= 1_000_000 ? 'compact' : 'standard',
  }).format(value)
}

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const todayLabel = computed(() =>
  new Date().toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }),
)
const greetingName = computed(() => authStore.user?.name?.split(' ')[0] ?? 'Admin')

// Catatan: badge perubahan (%) belum tersedia dari backend (butuh endpoint
// perbandingan periode), jadi untuk sekarang stat card TIDAK menampilkan
// badge tren — hanya angka aktual dari data yang sudah di-fetch.
const statCards = computed(() => [
  {
    label: 'Total booking',
    value: totalBookings.value,
    hint: '100 data terakhir',
    icon: CalendarDays,
  },
  {
    label: 'Pendapatan (lunas)',
    value: formatCurrency(paidRevenue.value),
    hint: `${paidRate.value}% booking lunas`,
    icon: Wallet,
  },
  {
    label: 'Aset tersedia',
    value: `${availableAssets.value} / ${totalAssets.value}`,
    hint: 'Siap dipinjam',
    icon: Package,
  },
  {
    label: 'Photo project aktif',
    value: activePhotoProjects.value,
    hint:
      pendingApproval.value > 0 ? `${pendingApproval.value} menunggu approval` : 'Semua terkendali',
    icon: Image,
    hidden: !labStore.activeLab?.is_photography_lab,
  },
])

// ============================================================
// CHART: Tren Booking — REAL DATA dari bookings.value.data
// ============================================================
const chartTextColor = '#6b7280'
const chartGridColor = 'rgba(0,0,0,0.05)'
const MONTH_LABELS_ID = [
  'Jan',
  'Feb',
  'Mar',
  'Apr',
  'Mei',
  'Jun',
  'Jul',
  'Agu',
  'Sep',
  'Okt',
  'Nov',
  'Des',
]

const bookingTrendData = computed(() => {
  const items = bookings.value?.data ?? []
  const now = new Date()

  const months: { year: number; month: number; label: string }[] = []
  for (let i = 5; i >= 0; i--) {
    const d = new Date(now.getFullYear(), now.getMonth() - i, 1)
    months.push({
      year: d.getFullYear(),
      month: d.getMonth(),
      label: MONTH_LABELS_ID[d.getMonth()]!,
    })
  }

  const counts = months.map(
    ({ year, month }) =>
      items.filter((b: any) => {
        const d = new Date(b.created_at)
        return d.getFullYear() === year && d.getMonth() === month
      }).length,
  )

  return {
    labels: months.map((m) => m.label),
    datasets: [
      {
        label: 'Booking',
        data: counts,
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59, 130, 246, 0.08)',
        fill: true,
        tension: 0.35,
        pointRadius: 3,
        pointBackgroundColor: '#3b82f6',
      },
    ],
  }
})

const bookingTrendOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { display: false }, ticks: { color: chartTextColor } },
    y: {
      grid: { color: chartGridColor },
      ticks: { color: chartTextColor, precision: 0 },
      beginAtZero: true,
    },
  },
}

// ============================================================
// CHART: Status Booking — REAL DATA dari bookings.value.data
// ============================================================
const bookingStatusData = computed(() => {
  const items = bookings.value?.data ?? []
  const count = (status: string) => items.filter((b: any) => b.status?.value === status).length

  return {
    labels: ['Pending', 'Approved/Ongoing', 'Completed', 'Rejected/Canceled'],
    datasets: [
      {
        data: [
          count('pending'),
          count('approved') + count('ongoing'),
          count('completed'),
          count('rejected') + count('canceled'),
        ],
        backgroundColor: ['#f59e0b', '#3b82f6', '#22c55e', '#ef4444'],
        borderWidth: 0,
      },
    ],
  }
})

const bookingStatusOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom' as const,
      labels: { color: chartTextColor, boxWidth: 10, padding: 14 },
    },
  },
  cutout: '65%',
}

// ============================================================
// CHART: Aset per Kategori — REAL DATA dari assets.value.data
// (menggantikan chart "pemakaian per lab" yang tidak relevan untuk
// dashboard satu-lab; ini pakai data aset yang memang sudah di-fetch)
// ============================================================
const assetCategoryData = computed(() => {
  const items = assets.value?.data ?? []
  const grouped: Record<string, number> = {}
  items.forEach((a: any) => {
    const label = a.category?.label ?? 'Lainnya'
    grouped[label] = (grouped[label] ?? 0) + 1
  })
  const sorted = Object.entries(grouped)
    .sort((a, b) => b[1] - a[1])
    .slice(0, 6)

  return {
    labels: sorted.map(([label]) => label),
    datasets: [
      {
        label: 'Jumlah Aset',
        data: sorted.map(([, count]) => count),
        backgroundColor: '#8b5cf6',
        borderRadius: 6,
        maxBarThickness: 36,
      },
    ],
  }
})

const assetCategoryOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { display: false }, ticks: { color: chartTextColor } },
    y: {
      grid: { color: chartGridColor },
      ticks: { color: chartTextColor, precision: 0 },
      beginAtZero: true,
    },
  },
}

// ============================================================
// Recent Activity — REAL DATA dari bookings.value.data terbaru
// (menggantikan dummy events sebelumnya)
// ============================================================
const recentActivities = computed(() => {
  const items = [...(bookings.value?.data ?? [])]
    .sort((a: any, b: any) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
    .slice(0, 5)

  return items.map((b: any) => ({
    icon: CalendarDays,
    text: `Booking ${b.booking_code} oleh ${b.user?.name ?? 'customer'}`,
    time: b.created_at,
  }))
})

function timeAgo(dateStr: string) {
  const diff = Date.now() - new Date(dateStr).getTime()
  const minutes = Math.floor(diff / 60000)
  if (minutes < 1) return 'baru saja'
  if (minutes < 60) return `${minutes} menit lalu`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours} jam lalu`
  return `${Math.floor(hours / 24)} hari lalu`
}

// ============================================================
// Quick Actions
// ============================================================
function createManualBooking() {
  router.push({
    name: 'booking-lab',
    params: { slug: labSlug.value },
    query: { mode: 'staff' },
  })
}

function openLandingPage() {
  window.open(`/lab/${labSlug.value}`, '_blank')
}
</script>

<template>
  <div class="space-y-6">
    <!-- HEADER -->
    <div class="flex flex-col gap-1">
      <p class="text-xs font-medium" style="color: var(--text-muted)">
        {{ labStore.activeLab?.name ?? 'Dashboard' }} · {{ todayLabel }}
      </p>
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 class="text-xl font-semibold tracking-tight" style="color: var(--text-primary)">
            Selamat datang, {{ greetingName }} 👋
          </h1>
          <p class="text-sm mt-0.5" style="color: var(--text-secondary)">
            {{ labStore.activeLab?.description ?? 'Ringkasan aktivitas lab hari ini.' }}
          </p>
        </div>

        <div class="flex flex-wrap gap-2">
          <Button size="sm" @click="createManualBooking">
            <Plus class="size-4" />
            Buat Booking Manual
          </Button>
          <Button size="sm" variant="outline" @click="openLandingPage">
            <ExternalLink class="size-4" />
            Lihat Landing Page
          </Button>
        </div>
      </div>
    </div>

    <Separator />

    <!-- STAT CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
      <template v-if="isLoadingStats">
        <Skeleton v-for="i in 4" :key="i" class="h-[92px] w-full rounded-xl" />
      </template>
      <template v-else>
        <Card v-for="stat in statCards" v-show="!stat.hidden" :key="stat.label" class="p-0">
          <CardContent class="px-4 py-3.5">
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <p class="text-xs font-medium truncate" style="color: var(--text-muted)">
                  {{ stat.label }}
                </p>
                <p class="text-xl font-semibold mt-1 truncate" style="color: var(--text-primary)">
                  {{ stat.value }}
                </p>
                <p class="text-[11px] mt-1.5 truncate" style="color: var(--text-muted)">
                  {{ stat.hint }}
                </p>
              </div>
              <div
                class="shrink-0 w-9 h-9 rounded-lg flex items-center justify-center"
                style="background: var(--surface-1); color: var(--text-accent)"
              >
                <component :is="stat.icon" class="size-4.5" />
              </div>
            </div>
          </CardContent>
        </Card>
      </template>
    </div>

    <!-- CHARTS -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <Card class="lg:col-span-2 p-0">
        <CardHeader class="px-5 pt-5 pb-0">
          <CardTitle class="text-sm font-semibold">Tren Booking</CardTitle>
          <p class="text-xs" style="color: var(--text-muted)">6 bulan terakhir</p>
        </CardHeader>
        <CardContent class="px-5 pb-5 pt-4">
          <div v-if="bookingsLoading" class="h-[240px] w-full">
            <Skeleton class="h-full w-full" />
          </div>
          <div v-else class="h-[240px] w-full">
            <Line :data="bookingTrendData" :options="bookingTrendOptions" />
          </div>
        </CardContent>
      </Card>

      <Card class="p-0">
        <CardHeader class="px-5 pt-5 pb-0">
          <CardTitle class="text-sm font-semibold">Status Booking</CardTitle>
          <p class="text-xs" style="color: var(--text-muted)">Distribusi status saat ini</p>
        </CardHeader>
        <CardContent class="px-5 pb-5 pt-4">
          <div v-if="bookingsLoading" class="h-[240px] w-full">
            <Skeleton class="h-full w-full rounded-full" />
          </div>
          <div v-else class="h-[240px] w-full">
            <Doughnut :data="bookingStatusData" :options="bookingStatusOptions" />
          </div>
        </CardContent>
      </Card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <Card class="lg:col-span-2 p-0">
        <CardHeader class="px-5 pt-5 pb-0">
          <CardTitle class="text-sm font-semibold">Aset per Kategori</CardTitle>
          <p class="text-xs" style="color: var(--text-muted)">Distribusi jumlah aset di lab ini</p>
        </CardHeader>
        <CardContent class="px-5 pb-5 pt-4">
          <div v-if="assetsLoading" class="h-[220px] w-full">
            <Skeleton class="h-full w-full" />
          </div>
          <div v-else class="h-[220px] w-full">
            <Bar :data="assetCategoryData" :options="assetCategoryOptions" />
          </div>
        </CardContent>
      </Card>

      <Card class="p-0">
        <CardHeader class="px-5 pt-5 pb-0">
          <CardTitle class="text-sm font-semibold">Aktivitas Terbaru</CardTitle>
        </CardHeader>
        <CardContent class="px-5 pb-5 pt-3">
          <div v-if="bookingsLoading" class="space-y-3">
            <Skeleton v-for="i in 4" :key="i" class="h-9 w-full" />
          </div>
          <ul v-else-if="recentActivities.length" class="space-y-3">
            <li
              v-for="(activity, idx) in recentActivities"
              :key="idx"
              class="flex items-start gap-3"
            >
              <div
                class="shrink-0 w-7 h-7 rounded-md flex items-center justify-center mt-0.5"
                style="background: var(--surface-1); color: var(--text-accent)"
              >
                <component :is="activity.icon" class="size-3.5" />
              </div>
              <div class="min-w-0">
                <p class="text-sm truncate" style="color: var(--text-primary)">
                  {{ activity.text }}
                </p>
                <p class="text-xs" style="color: var(--text-muted)">{{ timeAgo(activity.time) }}</p>
              </div>
            </li>
          </ul>
          <p v-else class="text-sm text-center py-6" style="color: var(--text-muted)">
            Belum ada aktivitas.
          </p>
        </CardContent>
      </Card>
    </div>

    <!-- RECENT BOOKING + QUICK ACTIONS -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <Card class="lg:col-span-2 p-0 overflow-hidden">
        <CardHeader class="px-5 pt-5 pb-3 flex flex-row items-center justify-between space-y-0">
          <CardTitle class="text-sm font-semibold">Booking Terbaru</CardTitle>
          <RouterLink
            :to="`/dashboard/lab/${labSlug}/bookings`"
            class="text-xs font-medium"
            style="color: var(--text-accent)"
          >
            Lihat semua
          </RouterLink>
        </CardHeader>

        <div v-if="bookingsLoading" class="px-5 pb-5 space-y-2">
          <Skeleton v-for="i in 4" :key="i" class="h-10 w-full" />
        </div>

        <div
          v-else-if="!recentBookings.length"
          class="px-5 pb-8 text-center text-sm"
          style="color: var(--text-muted)"
        >
          Belum ada booking.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr style="border-bottom: 0.5px solid var(--border)">
                <th
                  class="text-left px-5 py-2 font-medium text-xs"
                  style="color: var(--text-muted)"
                >
                  Kode
                </th>
                <th
                  class="text-left px-4 py-2 font-medium text-xs"
                  style="color: var(--text-muted)"
                >
                  Customer
                </th>
                <th
                  class="text-left px-4 py-2 font-medium text-xs"
                  style="color: var(--text-muted)"
                >
                  Status
                </th>
                <th
                  class="text-left px-4 py-2 font-medium text-xs"
                  style="color: var(--text-muted)"
                >
                  Tanggal
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="booking in recentBookings"
                :key="booking.uuid"
                style="border-bottom: 0.5px solid var(--border)"
              >
                <td class="px-5 py-2.5 font-mono text-xs" style="color: var(--text-primary)">
                  {{ booking.booking_code }}
                </td>
                <td class="px-4 py-2.5 truncate" style="color: var(--text-primary)">
                  {{ booking.user?.name }}
                </td>
                <td class="px-4 py-2.5">
                  <StatusBadge :status="booking.status" type="booking" />
                </td>
                <td class="px-4 py-2.5 text-xs" style="color: var(--text-muted)">
                  {{ formatDate(booking.start_time) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>

      <Card class="p-0">
        <CardHeader class="px-5 pt-5 pb-0">
          <CardTitle class="text-sm font-semibold">Aksi Cepat</CardTitle>
        </CardHeader>
        <CardContent class="px-5 pb-5 pt-4 grid grid-cols-2 gap-2">
          <Button
            variant="outline"
            size="sm"
            class="h-auto flex-col items-start gap-1.5 py-3 px-3"
            @click="createManualBooking"
          >
            <Plus class="size-4" style="color: var(--text-accent)" />
            <span class="text-xs font-medium text-left">Booking Baru</span>
          </Button>
          <Button
            variant="outline"
            size="sm"
            class="h-auto flex-col items-start gap-1.5 py-3 px-3"
            @click="router.push(`/dashboard/lab/${labSlug}/assets`)"
          >
            <Package class="size-4" style="color: var(--text-accent)" />
            <span class="text-xs font-medium text-left">Tambah Aset</span>
          </Button>
          <Button
            variant="outline"
            size="sm"
            class="h-auto flex-col items-start gap-1.5 py-3 px-3"
            @click="router.push(`/dashboard/lab/${labSlug}/users`)"
          >
            <CalendarDays class="size-4" style="color: var(--text-accent)" />
            <span class="text-xs font-medium text-left">Tambah Pengguna</span>
          </Button>
          <Button
            variant="outline"
            size="sm"
            class="h-auto flex-col items-start gap-1.5 py-3 px-3"
            @click="openLandingPage"
          >
            <ExternalLink class="size-4" style="color: var(--text-accent)" />
            <span class="text-xs font-medium text-left">Landing Page</span>
          </Button>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
