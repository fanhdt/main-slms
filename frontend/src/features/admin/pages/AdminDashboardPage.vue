<script setup lang="ts">
import { computed } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import {
  FlaskConical,
  Users,
  Package,
  CalendarDays,
  Plus,
  UserPlus,
  ExternalLink,
  FileBarChart,
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
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
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

const router = useRouter()
const authStore = useAuthStore()

// ============================================================
// Queries — endpoint SAMA seperti sebelumnya, hanya tambah per_page
// pada bookings supaya cukup data untuk chart (bukan endpoint baru).
// ============================================================
const { data: labs, isLoading: labsLoading } = useQuery({
  queryKey: ['admin-labs'],
  queryFn: async () => {
    const res = await api.get('/labs', { params: { per_page: 100 } })
    return res.data.data
  },
})

const { data: users, isLoading: usersLoading } = useQuery({
  queryKey: ['admin-users'],
  queryFn: async () => {
    const res = await api.get('/users', { params: { per_page: 100 } })
    return res.data.data
  },
})

const { data: assets, isLoading: assetsLoading } = useQuery({
  queryKey: ['admin-assets'],
  queryFn: async () => {
    const res = await api.get('/assets', { params: { per_page: 100 } })
    return res.data.data
  },
})

const { data: bookings, isLoading: bookingsLoading } = useQuery({
  queryKey: ['admin-bookings'],
  queryFn: async () => {
    const res = await api.get('/bookings', { params: { per_page: 100 } })
    return res.data.data
  },
})

const isLoadingStats = computed(
  () => labsLoading.value || usersLoading.value || assetsLoading.value || bookingsLoading.value,
)

// ============================================================
// Header — sapaan & tanggal
// ============================================================
const todayLabel = computed(() =>
  new Date().toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }),
)
const greetingName = computed(() => authStore.user?.name?.split(' ')[0] ?? 'Admin')

// ============================================================
// Stat cards — murni dari data query, tidak ada dummy
// ============================================================
const statCards = computed(() => [
  {
    label: 'Total Lab',
    value: labs.value?.meta?.total ?? 0,
    hint: 'Laboratorium terdaftar',
    icon: FlaskConical,
    to: '/admin/labs',
  },
  {
    label: 'Total User',
    value: users.value?.meta?.total ?? 0,
    hint: 'Pengguna terdaftar',
    icon: Users,
    to: '/admin/users',
  },
  {
    label: 'Total Aset',
    value: assets.value?.meta?.total ?? 0,
    hint: 'Aset di semua lab',
    icon: Package,
    to: null,
  },
  {
    label: 'Total Booking',
    value: bookings.value?.meta?.total ?? 0,
    hint: 'Semua laboratorium',
    icon: CalendarDays,
    to: null,
  },
])

// ============================================================
// CHART: Tren Booking — dihitung dari bookings.value.data (created_at)
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

const bookingTrend = computed(() => {
  const items = bookings.value?.data ?? []
  const now = new Date()

  // 6 bulan terakhir termasuk bulan berjalan
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
// CHART: Status Booking — dihitung dari status.value booking asli
// ============================================================
const bookingStatusChart = computed(() => {
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
// CHART: Booking per Laboratorium — join bookings.lab_id ke labs.name
// ============================================================
const labUsageChart = computed(() => {
  const labList = labs.value?.data ?? []
  const items = bookings.value?.data ?? []
  function bookingLabId(b: any): string | number | undefined {
    return b.lab_id ?? b.lab?.id ?? b.lab_uuid ?? b.lab?.uuid
  }
  const labeled: { name: string; count: number }[] = labList.map((lab: any) => ({
    name: lab.name,
    count: items.filter((b: any) => {
      const bId = bookingLabId(b)
      return String(bId) === String(lab.id) || String(bId) === String(lab.uuid)
    }).length,
  }))

  // urutkan dari terbanyak, ambil maks 6 biar chart tidak penuh
  const sorted = labeled
    .sort(
      (a: { name: string; count: number }, b: { name: string; count: number }) => b.count - a.count,
    )
    .slice(0, 6)

  return {
    labels: sorted.map((l) => l.name),
    datasets: [
      {
        label: 'Booking',
        data: sorted.map((l) => l.count),
        backgroundColor: '#8b5cf6',
        borderRadius: 6,
        maxBarThickness: 36,
      },
    ],
  }
})
const hasLabUsageData = computed(() =>
  (labUsageChart.value.datasets[0]?.data ?? []).some((v: number) => v > 0),
)
const labUsageOptions = {
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
// Recent Activity — gabungan booking & user terbaru (REAL DATA),
// diurutkan berdasarkan created_at, bukan event yang direka.
// ============================================================
type ActivityItem = { icon: unknown; text: string; time: string }

const recentActivities = computed(() => {
  const bookingItems: ActivityItem[] = (bookings.value?.data ?? []).map((b: any) => ({
    icon: CalendarDays,
    text: `Booking ${b.booking_code} dibuat oleh ${b.user?.name ?? 'customer'}`,
    time: b.created_at,
  }))

  const userItems: ActivityItem[] = (users.value?.data ?? []).map((u: any) => ({
    icon: Users,
    text: `User baru terdaftar: ${u.name}`,
    time: u.created_at,
  }))

  return [...bookingItems, ...userItems]
    .sort(
      (a: ActivityItem, b: ActivityItem) => new Date(b.time).getTime() - new Date(a.time).getTime(),
    )
    .slice(0, 6)
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
// Recent Booking table — reuse data booking yang sama
// ============================================================
const recentBookings = computed(() =>
  [...(bookings.value?.data ?? [])]
    .sort((a: any, b: any) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
    .slice(0, 6),
)

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  })
}

// ============================================================
// Quick Actions — mengarah ke route yang sudah ada
// ============================================================
const quickActions = computed(() => [
  { label: 'Kelola Lab', icon: FlaskConical, action: () => router.push('/admin/labs') },
  { label: 'Kelola User', icon: UserPlus, action: () => router.push('/admin/users') },
  { label: 'Booking Pribadi', icon: Plus, action: () => router.push('/booking') },
  { label: 'Masuk ke Lab', icon: ExternalLink, action: () => router.push('/dashboard') },
])
</script>

<template>
  <div class="space-y-6">
    <!-- ============================================================
         HEADER
    ============================================================= -->
    <div class="flex flex-col gap-1">
      <p class="text-xs font-medium" style="color: var(--text-muted)">
        Global Admin Panel · {{ todayLabel }}
      </p>
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 class="text-xl font-semibold tracking-tight" style="color: var(--text-primary)">
            Selamat datang, {{ greetingName }} 🌐
          </h1>
          <p class="text-sm mt-0.5" style="color: var(--text-secondary)">
            Kamu punya akses penuh ke semua laboratorium.
          </p>
        </div>

        <div class="flex flex-wrap gap-2">
          <Button size="sm" @click="router.push('/admin/labs')">
            <Plus class="size-4" />
            Tambah Lab
          </Button>
          <Button size="sm" variant="outline" @click="router.push('/admin/users')">
            <UserPlus class="size-4" />
            Tambah User
          </Button>
        </div>
      </div>
    </div>

    <Separator />

    <!-- ============================================================
         STAT CARDS
    ============================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
      <template v-if="isLoadingStats">
        <Skeleton v-for="i in 4" :key="i" class="h-[92px] w-full rounded-xl" />
      </template>

      <template v-else>
        <RouterLink
          v-for="stat in statCards"
          :key="stat.label"
          :to="stat.to ?? ''"
          :class="!stat.to && 'pointer-events-none'"
        >
          <Card class="p-0 h-full hover:shadow-sm transition-shadow">
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
        </RouterLink>
      </template>
    </div>

    <!-- ============================================================
         CHARTS
    ============================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <Card class="lg:col-span-2 p-0">
        <CardHeader class="px-5 pt-5 pb-0">
          <CardTitle class="text-sm font-semibold">Tren Booking</CardTitle>
          <p class="text-xs" style="color: var(--text-muted)">6 bulan terakhir · semua lab</p>
        </CardHeader>
        <CardContent class="px-5 pb-5 pt-4">
          <div v-if="bookingsLoading" class="h-[240px] w-full flex items-center justify-center">
            <Skeleton class="h-full w-full" />
          </div>
          <div v-else class="h-[240px] w-full">
            <Line :data="bookingTrend" :options="bookingTrendOptions" />
          </div>
        </CardContent>
      </Card>

      <Card class="p-0">
        <CardHeader class="px-5 pt-5 pb-0">
          <CardTitle class="text-sm font-semibold">Status Booking</CardTitle>
          <p class="text-xs" style="color: var(--text-muted)">Distribusi status saat ini</p>
        </CardHeader>
        <CardContent class="px-5 pb-5 pt-4">
          <div v-if="bookingsLoading" class="h-[240px] w-full flex items-center justify-center">
            <Skeleton class="h-full w-full rounded-full" />
          </div>
          <div v-else class="h-[240px] w-full">
            <Doughnut :data="bookingStatusChart" :options="bookingStatusOptions" />
          </div>
        </CardContent>
      </Card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <Card class="lg:col-span-2 p-0">
        <CardHeader class="px-5 pt-5 pb-0">
          <CardTitle class="text-sm font-semibold">Booking per Laboratorium</CardTitle>
          <p class="text-xs" style="color: var(--text-muted)">6 lab dengan booking terbanyak</p>
        </CardHeader>
        <CardContent class="px-5 pb-5 pt-4">
          <div
            v-if="bookingsLoading || labsLoading"
            class="h-[220px] w-full flex items-center justify-center"
          >
            <Skeleton class="h-full w-full" />
          </div>

          <div
            v-else-if="!hasLabUsageData"
            class="h-[220px] flex items-center justify-center text-sm text-gray-400"
          >
            Belum ada data booking untuk ditampilkan per lab.
          </div>
          <div v-else class="h-[220px] w-full">
            <Bar :data="labUsageChart" :options="labUsageOptions" />
          </div>
        </CardContent>
      </Card>

      <!-- Recent Activity -->
      <Card class="p-0">
        <CardHeader class="px-5 pt-5 pb-0">
          <CardTitle class="text-sm font-semibold">Aktivitas Terbaru</CardTitle>
        </CardHeader>
        <CardContent class="px-5 pb-5 pt-3">
          <div v-if="isLoadingStats" class="space-y-3">
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

    <!-- ============================================================
         RECENT BOOKING + QUICK ACTIONS
    ============================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <Card class="lg:col-span-2 p-0 overflow-hidden">
        <CardHeader class="px-5 pt-5 pb-3 flex flex-row items-center justify-between space-y-0">
          <CardTitle class="text-sm font-semibold">Booking Terbaru</CardTitle>
          <span class="text-xs" style="color: var(--text-muted)">Semua laboratorium</span>
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
                  {{ formatDate(booking.created_at) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>

      <!-- Quick Actions -->
      <Card class="p-0">
        <CardHeader class="px-5 pt-5 pb-0">
          <CardTitle class="text-sm font-semibold">Aksi Cepat</CardTitle>
        </CardHeader>
        <CardContent class="px-5 pb-5 pt-4 grid grid-cols-2 gap-2">
          <Button
            v-for="action in quickActions"
            :key="action.label"
            variant="outline"
            size="sm"
            class="h-auto flex-col items-start gap-1.5 py-3 px-3"
            @click="action.action"
          >
            <component :is="action.icon" class="size-4" style="color: var(--text-accent)" />
            <span class="text-xs font-medium text-left">{{ action.label }}</span>
          </Button>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
