<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'

import api from '@/lib/axios'
import AvailabilityCalendar from '@/components/AvailabilityCalendar.vue'
import PhotographerPortfolioSection from '@/features/portfolio/components/PhotographerPortfolioSection.vue'
import { useBookingDraftStore } from '@/features/booking/stores/useBookingDraftStore'
import type { BookingDraft } from '@/features/booking/stores/useBookingDraftStore'
import { setPendingBookingRedirect } from '@/composables/usePendingBookingRedirect'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Mail,
  Phone,
  MapPin,
  Images,
  Camera,
  CalendarClock,
  Info,
  Menu,
  X,
  ArrowRight,
  Sparkles,
  CheckCircle2,
  CalendarDays,
  ClipboardList,
  CreditCard,
  CheckCircle,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const draftStore = useBookingDraftStore()

const slug = computed(() => route.params.slug as string)

const { data: lab, isLoading } = useQuery({
  queryKey: ['lab-landing', slug],
  queryFn: async () => {
    const res = await api.get(`/labs/${slug.value}`)
    return res.data.data
  },
})

const { data: services } = useQuery({
  queryKey: ['lab-services', slug],
  queryFn: async () => {
    const res = await api.get('/services', {
      params: { lab_id: lab.value?.id, is_active: true },
    })
    return res.data.data.data
  },
  enabled: computed(() => !!lab.value),
})

// --- Pagination untuk grid Layanan (6 per halaman, 3 kolom x 2 baris) ---
const servicesPage = ref(1)
const servicesPerPage = 6

const servicesTotalPages = computed(() => {
  if (!services.value?.length) return 1
  return Math.max(1, Math.ceil(services.value.length / servicesPerPage))
})

const paginatedServices = computed(() => {
  if (!services.value?.length) return []
  const start = (servicesPage.value - 1) * servicesPerPage
  return services.value.slice(start, start + servicesPerPage)
})

function goToServicesPage(page: number) {
  if (page < 1 || page > servicesTotalPages.value) return
  servicesPage.value = page
  document.getElementById('services')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

const { data: packages } = useQuery({
  queryKey: ['lab-packages', slug],
  queryFn: async () => {
    const res = await api.get('/packages', {
      params: { lab_id: lab.value?.id, is_active: true },
    })
    return res.data.data.data
  },
  enabled: computed(() => !!lab.value),
})

const selectedDayInfo = ref<{
  date: string | null
  loading: boolean
  operationalHours: { open: string; close: string } | null
  activities: { start: string; end: string; label: string }[]
}>({
  date: null,
  loading: false,
  operationalHours: null,
  activities: [],
})

// UI-only state: mobile drawer toggle (does not touch any business logic)
const isMobileMenuOpen = ref(false)

function handleBooking() {
  if (authStore.isAuthenticated) {
    router.push(`/booking/${slug.value}`)
  } else {
    router.push({ name: 'login' })
  }
}

// --- Kalender interaktif di landing page: pilih jadwal lalu langsung booking ---
// Tipe schedule di-reuse langsung dari BookingDraft (useBookingDraftStore),
// supaya tidak duplikat definisi shape { date, start, end, durationHours }.
const confirmedSchedule = ref<BookingDraft['schedule']>(null)

function handleScheduleConfirm(payload: NonNullable<BookingDraft['schedule']>) {
  confirmedSchedule.value = payload
}

function handleBookWithSelectedSchedule() {
  if (!confirmedSchedule.value) return

  // Simpan jadwal terpilih sebagai draft booking "Pinjam Lab" untuk lab ini,
  // supaya BookingFormPage otomatis memuatnya begitu user sampai di sana.
  draftStore.saveDraft(slug.value, 'lab_rental', undefined, {
    schedule: confirmedSchedule.value,
  })

  const target = {
    name: 'booking-form',
    params: { slug: slug.value },
    query: { bookingType: 'lab_rental' },
  } as const

  if (authStore.isAuthenticated) {
    router.push(target)
  } else {
    // Simpan tujuan booking, arahkan ke login dulu — setelah login berhasil
    // user otomatis dilempar kembali ke sini dengan jadwal yang sama.
    setPendingBookingRedirect(target)
    router.push({ name: 'login' })
  }
}

function formatPrice(price: string) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(Number(price))
}

function handleDayChanged(payload: typeof selectedDayInfo.value) {
  selectedDayInfo.value = payload
}

function formatSelectedDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  })
}
</script>

<template>
  <div class="min-h-screen bg-white antialiased">
    <!-- ================================================================
         LOADING (Skeleton — no spinners)
    ================================================================ -->
    <div v-if="isLoading" class="min-h-screen flex flex-col items-center justify-center gap-4 px-6">
      <Skeleton class="h-12 w-72 rounded-2xl" />
      <Skeleton class="h-6 w-96 max-w-full rounded-xl" />
      <Skeleton class="h-6 w-80 max-w-full rounded-xl" />
    </div>

    <template v-else-if="lab">
      <!-- ================================================================
           NAVBAR — floating, glass, sticky
      ================================================================ -->
      <nav class="fixed top-3 sm:top-4 left-0 right-0 z-50 px-3 sm:px-6">
        <div
          class="max-w-6xl mx-auto flex items-center justify-between gap-3 rounded-2xl border border-white/10 px-4 sm:px-5 py-2.5 sm:py-3 shadow-lg shadow-black/5 backdrop-blur-xl transition-all"
          :style="{ backgroundColor: (lab.branding.primary_color ?? '#1a1a2e') + 'cc' }"
        >
          <span class="text-white font-bold text-base sm:text-lg tracking-tight truncate">
            {{ lab.name }}
          </span>

          <!-- Desktop nav -->
          <div class="hidden md:flex items-center gap-1">
            <a
              href="#services"
              class="text-white/70 hover:text-white text-sm font-medium px-3 py-2 rounded-xl hover:bg-white/10 transition-colors duration-200"
            >
              Layanan
            </a>
            <a
              href="#packages"
              class="text-white/70 hover:text-white text-sm font-medium px-3 py-2 rounded-xl hover:bg-white/10 transition-colors duration-200"
            >
              Paket
            </a>
            <a
              v-if="lab.is_photography_lab"
              href="#portfolio"
              class="text-white/70 hover:text-white text-sm font-medium px-3 py-2 rounded-xl hover:bg-white/10 transition-colors duration-200"
            >
              Portofolio
            </a>
            <a
              href="#contact"
              class="text-white/70 hover:text-white text-sm font-medium px-3 py-2 rounded-xl hover:bg-white/10 transition-colors duration-200"
            >
              Kontak
            </a>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="handleBooking"
              class="hidden sm:inline-flex items-center gap-1.5 text-sm font-semibold px-4 sm:px-5 py-2 rounded-xl text-white shadow-sm shadow-black/10 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white/70"
              :style="{ backgroundColor: lab.branding.secondary_color ?? '#e94560' }"
              aria-label="Book sekarang"
            >
              Book Sekarang
            </button>

            <!-- Mobile hamburger -->
            <button
              class="md:hidden inline-flex items-center justify-center size-9 rounded-xl text-white hover:bg-white/10 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white/70"
              @click="isMobileMenuOpen = !isMobileMenuOpen"
              :aria-expanded="isMobileMenuOpen"
              aria-label="Buka menu navigasi"
            >
              <Menu v-if="!isMobileMenuOpen" class="size-5" />
              <X v-else class="size-5" />
            </button>
          </div>
        </div>

        <!-- Mobile drawer -->
        <Transition
          enter-active-class="transition ease-out duration-200"
          enter-from-class="opacity-0 -translate-y-2"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition ease-in duration-150"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-2"
        >
          <div
            v-if="isMobileMenuOpen"
            class="md:hidden max-w-6xl mx-auto mt-2 rounded-2xl border border-white/10 shadow-lg backdrop-blur-xl overflow-hidden"
            :style="{ backgroundColor: (lab.branding.primary_color ?? '#1a1a2e') + 'e6' }"
          >
            <div class="flex flex-col p-2">
              <a
                href="#services"
                class="text-white/80 hover:text-white text-sm font-medium px-4 py-3 rounded-xl hover:bg-white/10 transition-colors"
                @click="isMobileMenuOpen = false"
              >
                Layanan
              </a>
              <a
                href="#packages"
                class="text-white/80 hover:text-white text-sm font-medium px-4 py-3 rounded-xl hover:bg-white/10 transition-colors"
                @click="isMobileMenuOpen = false"
              >
                Paket
              </a>
              <a
                v-if="lab.is_photography_lab"
                href="#portfolio"
                class="text-white/80 hover:text-white text-sm font-medium px-4 py-3 rounded-xl hover:bg-white/10 transition-colors"
                @click="isMobileMenuOpen = false"
              >
                Portofolio
              </a>
              <a
                href="#contact"
                class="text-white/80 hover:text-white text-sm font-medium px-4 py-3 rounded-xl hover:bg-white/10 transition-colors"
                @click="isMobileMenuOpen = false"
              >
                Kontak
              </a>
              <button
                @click="(handleBooking(), (isMobileMenuOpen = false))"
                class="mt-1 text-sm font-semibold px-4 py-3 rounded-xl text-white text-center shadow-sm transition-colors"
                :style="{ backgroundColor: lab.branding.secondary_color ?? '#e94560' }"
              >
                Book Sekarang
              </button>
            </div>
          </div>
        </Transition>
      </nav>

      <!-- ================================================================
           HERO SECTION — two column, glow, gradient, glass badge
      ================================================================ -->
      <section
        class="min-h-screen flex items-center pt-28 pb-16 sm:pt-32 relative overflow-hidden"
        :style="{ backgroundColor: lab.branding.primary_color ?? '#1a1a2e' }"
      >
        <!-- Ambient background: dot pattern + glow blurs -->
        <div class="absolute inset-0 opacity-[0.06] pointer-events-none">
          <div
            class="absolute inset-0"
            style="
              background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0);
              background-size: 40px 40px;
            "
          />
        </div>
        <div
          class="absolute -top-24 -left-24 size-72 sm:size-96 rounded-full blur-3xl opacity-30 pointer-events-none"
          :style="{ backgroundColor: lab.branding.secondary_color ?? '#e94560' }"
        />
        <div
          class="absolute -bottom-32 -right-16 size-72 sm:size-[28rem] rounded-full blur-3xl opacity-20 pointer-events-none"
          :style="{ backgroundColor: lab.branding.secondary_color ?? '#e94560' }"
        />

        <div class="max-w-6xl mx-auto px-6 relative z-10 w-full">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-8 items-center">
            <!-- Left column -->
            <div class="text-center lg:text-left">
              <div
                class="inline-flex items-center gap-1.5 text-xs font-semibold px-3.5 py-1.5 rounded-full mb-6 border border-white/10 backdrop-blur-sm"
                :style="{
                  backgroundColor: lab.branding.secondary_color + '25',
                  color: '#fff',
                }"
              >
                <Sparkles
                  class="size-3.5"
                  :style="{ color: lab.branding.secondary_color ?? '#e94560' }"
                />
                Professional Photography Studio
              </div>

              <h1
                class="text-4xl sm:text-5xl lg:text-6xl font-black text-white leading-[1.05] tracking-tight mb-6"
              >
                {{ lab.name }}
              </h1>

              <p
                class="text-lg sm:text-xl text-white/60 leading-relaxed max-w-xl mx-auto lg:mx-0 mb-10"
              >
                {{ lab.description }}
              </p>

              <div
                class="flex flex-col sm:flex-row items-center lg:items-start justify-center lg:justify-start gap-3 sm:gap-4"
              >
                <button
                  @click="handleBooking"
                  class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl font-semibold text-white text-base sm:text-lg shadow-lg shadow-black/20 hover:shadow-xl hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white/70"
                  :style="{ backgroundColor: lab.branding.secondary_color ?? '#e94560' }"
                >
                  Book Sekarang
                  <ArrowRight class="size-5" />
                </button>

                <a
                  href="#services"
                  class="w-full sm:w-auto text-center px-8 py-4 rounded-xl font-semibold text-white/90 border border-white/20 hover:bg-white/10 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 text-base sm:text-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white/70"
                >
                  Lihat Layanan
                </a>
              </div>
            </div>

            <!-- Right column: hero illustration -->
            <div class="relative hidden lg:flex items-center justify-center">
              <!-- Foto lab (kalau ada branding hero_image/logo) -->
              <div
                v-if="lab.branding?.hero_image || lab.branding?.logo"
                class="relative size-80 xl:size-96 rounded-3xl overflow-hidden border border-white/10 shadow-2xl shadow-black/30"
              >
                <img
                  :src="lab.branding.hero_image || lab.branding.logo"
                  :alt="lab.name"
                  class="w-full h-full object-cover"
                />
                <!-- subtle overlay biar konsisten sama gaya glass di section lain -->
                <div
                  class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent"
                />

                <div
                  class="absolute -top-4 -right-4 size-20 rounded-2xl border border-white/10 backdrop-blur-md flex items-center justify-center shadow-lg"
                  :style="{ backgroundColor: lab.branding.secondary_color + '30' }"
                >
                  <Images class="size-8 text-white/70" stroke-width="1.5" />
                </div>
              </div>

              <!-- Fallback ilustrasi ikon (kalau lab belum punya foto) -->
              <div
                v-else
                class="relative size-80 xl:size-96 rounded-3xl border border-white/10 backdrop-blur-md flex items-center justify-center shadow-2xl shadow-black/30"
                :style="{ backgroundColor: 'rgba(255,255,255,0.06)' }"
              >
                <Camera class="size-24 text-white/20" stroke-width="1" />
                <div
                  class="absolute -top-4 -right-4 size-20 rounded-2xl border border-white/10 backdrop-blur-md flex items-center justify-center shadow-lg"
                  :style="{ backgroundColor: lab.branding.secondary_color + '30' }"
                >
                  <Images class="size-8 text-white/70" stroke-width="1.5" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ================================================================
           AVAILABILITY SECTION — dashboard-style calendar + timeline
      ================================================================ -->
      <section class="max-w-6xl mx-auto px-6 py-16 sm:py-20">
        <div class="text-center mb-10">
          <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">
            Jadwal Ketersediaan Lab
          </h2>
          <p class="text-gray-500 mt-2">
            Pilih jam yang kosong di kalender untuk langsung membuat booking
          </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_340px] gap-6 items-start">
          <div class="rounded-3xl border border-gray-100 shadow-sm p-4 sm:p-6 bg-white">
            <AvailabilityCalendar
              :slug="slug"
              :interactive="true"
              @day-changed="handleDayChanged"
              @confirm-slot="handleScheduleConfirm"
            />

            <!-- CTA muncul setelah jadwal dikonfirmasi di kalender -->
            <Transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="opacity-0 translate-y-2"
              enter-to-class="opacity-100 translate-y-0"
            >
              <div
                v-if="confirmedSchedule"
                class="mt-5 rounded-2xl border p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                :style="{
                  borderColor: (lab.branding.secondary_color ?? '#e94560') + '40',
                  backgroundColor: (lab.branding.secondary_color ?? '#e94560') + '0d',
                }"
              >
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">
                    Jadwal Dipilih
                  </p>
                  <p class="text-base font-semibold text-gray-900">
                    {{ formatSelectedDate(confirmedSchedule.date) }} ·
                    {{ confirmedSchedule.start }}–{{ confirmedSchedule.end }}
                    <span class="text-gray-400 font-normal">
                      ({{ confirmedSchedule.durationHours }} jam)
                    </span>
                  </p>
                </div>
                <button
                  @click="handleBookWithSelectedSchedule"
                  class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-semibold text-white shadow-sm hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 shrink-0 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                  :style="{ backgroundColor: lab.branding.secondary_color ?? '#e94560' }"
                >
                  Lanjutkan Booking
                  <ArrowRight class="size-4" />
                </button>
              </div>
            </Transition>
          </div>

          <aside class="bg-white rounded-3xl border border-gray-100 shadow-sm p-5 sm:p-6 lg:top-24">
            <div class="flex items-center gap-2 mb-1">
              <CalendarClock class="size-4 text-gray-400 shrink-0" />
              <p class="text-sm font-semibold text-gray-900">Jam Terpakai</p>
            </div>
            <p v-if="selectedDayInfo.date" class="text-xs text-gray-400 mb-4">
              {{ formatSelectedDate(selectedDayInfo.date) }}
            </p>
            <p v-else class="text-xs text-gray-400 mb-4">Pilih tanggal untuk lihat jadwal</p>

            <!-- Loading skeleton -->
            <div v-if="selectedDayInfo.loading" class="space-y-2.5">
              <Skeleton v-for="i in 3" :key="i" class="h-14 rounded-xl" />
            </div>

            <!-- Timeline of activities -->
            <div v-else-if="selectedDayInfo.activities.length" class="space-y-2.5">
              <div
                v-for="(activity, idx) in selectedDayInfo.activities"
                :key="idx"
                class="rounded-2xl border border-gray-100 bg-gray-50/70 px-4 py-3 hover:shadow-sm transition-shadow duration-200"
              >
                <div class="flex items-start justify-between gap-2">
                  <p class="text-sm font-medium text-gray-900 leading-snug">{{ activity.label }}</p>
                  <span
                    class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-red-50 text-red-600 shrink-0 whitespace-nowrap"
                  >
                    Terpakai
                  </span>
                </div>
                <p class="text-xs text-gray-400 mt-1">{{ activity.start }} – {{ activity.end }}</p>
              </div>
            </div>

            <!-- Empty state -->
            <div
              v-else-if="selectedDayInfo.date"
              class="flex flex-col items-center text-center gap-2.5 py-10 px-2"
            >
              <div class="size-11 rounded-2xl bg-green-50 flex items-center justify-center">
                <Info class="size-5 text-green-600" />
              </div>
              <p class="text-sm font-semibold text-gray-700">Lab kosong hari ini</p>
              <p class="text-xs text-gray-400 leading-relaxed">
                Belum ada kegiatan terjadwal pada tanggal ini.
              </p>
            </div>
          </aside>
        </div>
      </section>

      <!-- ================================================================
           SERVICES SECTION — grid 3x2 (6 item), sisanya pagination
      ================================================================ -->
      <section id="services" class="py-16 sm:py-20 bg-gray-50">
        <div class="max-w-6xl mx-auto px-6">
          <div class="text-center mb-12">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">
              Layanan Kami
            </h2>
            <p class="text-gray-500 mt-2">Pilih layanan yang sesuai kebutuhanmu</p>
          </div>

          <div v-if="services?.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
              v-for="service in paginatedServices"
              :key="service.uuid"
              class="group relative bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5 hover:shadow-lg hover:-translate-y-1 transition-all duration-300"
            >
              <span
                v-if="service.type?.value === 'photography'"
                class="absolute top-3 right-3 z-10 text-[10px] font-medium px-2 py-1 rounded-full flex items-center gap-1 backdrop-blur-sm"
                :style="{
                  backgroundColor: lab.branding.secondary_color + '15',
                  color: lab.branding.secondary_color ?? '#e94560',
                }"
              >
                <Images class="size-3" />
                Galeri
              </span>

              <div
                class="w-full h-36 sm:h-44 rounded-xl overflow-hidden mb-3 flex items-center justify-center"
                :style="
                  !service.image ? { backgroundColor: lab.branding.primary_color + '10' } : {}
                "
              >
                <img
                  v-if="service.image"
                  :src="service.image"
                  :alt="service.name"
                  class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
                />
                <Camera
                  v-else
                  class="size-8"
                  :style="{ color: lab.branding.primary_color ?? '#1a1a2e' }"
                />
              </div>

              <h3 class="font-semibold text-gray-900 text-base sm:text-lg tracking-tight truncate">
                {{ service.name }}
              </h3>
              <p class="text-gray-500 text-sm mt-1 leading-snug line-clamp-2">
                {{ service.description }}
              </p>

              <div class="mt-4 flex items-end justify-between gap-2">
                <div class="min-w-0">
                  <template v-if="service.price !== null">
                    <p
                      class="font-bold text-base sm:text-lg truncate"
                      :style="{ color: lab.branding.primary_color ?? '#1a1a2e' }"
                    >
                      {{ formatPrice(service.price) }}
                    </p>
                    <p class="text-xs text-gray-400 truncate">
                      {{ service.pricing_type?.label }}
                    </p>
                  </template>
                  <p v-else class="text-xs font-medium text-blue-600 leading-snug">
                    Sesuai pilihan editing
                  </p>
                </div>
                <button
                  @click="handleBooking"
                  class="text-sm font-medium px-4 py-2 rounded-xl text-white shadow-sm hover:shadow-md active:scale-[0.97] transition-all duration-200 shrink-0 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                  :style="{ backgroundColor: lab.branding.secondary_color ?? '#e94560' }"
                >
                  Pesan
                </button>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div
            v-if="services?.length && servicesTotalPages > 1"
            class="flex items-center justify-center gap-2 mt-10"
          >
            <button
              :disabled="servicesPage <= 1"
              @click="goToServicesPage(servicesPage - 1)"
              class="w-9 h-9 rounded-full border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-100 disabled:opacity-30 disabled:pointer-events-none transition-colors"
            >
              <ChevronLeft class="size-4" />
            </button>

            <button
              v-for="page in servicesTotalPages"
              :key="page"
              @click="goToServicesPage(page)"
              class="w-9 h-9 rounded-full text-sm font-medium transition-colors"
              :class="
                page === servicesPage
                  ? 'text-white'
                  : 'text-gray-500 hover:bg-gray-100 border border-gray-200'
              "
              :style="
                page === servicesPage
                  ? { backgroundColor: lab.branding.secondary_color ?? '#e94560' }
                  : {}
              "
            >
              {{ page }}
            </button>

            <button
              :disabled="servicesPage >= servicesTotalPages"
              @click="goToServicesPage(servicesPage + 1)"
              class="w-9 h-9 rounded-full border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-100 disabled:opacity-30 disabled:pointer-events-none transition-colors"
            >
              <ChevronRight class="size-4" />
            </button>
          </div>

          <!-- Empty state -->
          <div
            v-else-if="!services?.length"
            class="flex flex-col items-center text-center gap-3 py-16"
          >
            <div class="size-12 rounded-2xl bg-gray-100 flex items-center justify-center">
              <Camera class="size-6 text-gray-400" />
            </div>
            <p class="text-sm font-semibold text-gray-600">Belum ada layanan tersedia</p>
            <p class="text-xs text-gray-400">Layanan akan tampil di sini setelah tersedia.</p>
          </div>
        </div>
      </section>

      <!-- ================================================================
           PACKAGES SECTION — SaaS pricing card style
      ================================================================ -->
      <section id="packages" class="py-16 sm:py-20 bg-white">
        <div class="max-w-6xl mx-auto px-6">
          <div class="text-center mb-12">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">Paket Foto</h2>
            <p class="text-gray-500 mt-2">Hemat lebih banyak dengan paket bundling</p>
          </div>

          <div v-if="packages?.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
              v-for="(pkg, idx) in packages"
              :key="pkg.uuid"
              class="rounded-2xl border shadow-sm overflow-hidden hover:shadow-lg hover:-translate-y-1 transition-all duration-300 relative"
              :class="idx === 1 ? 'ring-2 ring-offset-2' : 'border-gray-100'"
              :style="
                idx === 1 ? { '--tw-ring-color': lab.branding.secondary_color ?? '#e94560' } : {}
              "
            >
              <span
                v-if="idx === 1"
                class="absolute top-3 right-3 z-10 text-[10px] font-semibold px-2.5 py-1 rounded-full text-white"
                :style="{ backgroundColor: lab.branding.secondary_color ?? '#e94560' }"
              >
                Paling Populer
              </span>

              <!-- Gambar paket -->
              <div
                class="w-full h-40 sm:h-48 overflow-hidden bg-gray-100 flex items-center justify-center"
              >
                <img
                  v-if="pkg.image"
                  :src="pkg.image"
                  :alt="pkg.name"
                  class="w-full h-full object-cover"
                />
                <Images
                  v-else
                  class="size-9"
                  :style="{ color: lab.branding.primary_color ?? '#1a1a2e' }"
                />
              </div>

              <div
                class="p-6 text-white"
                :style="{ backgroundColor: lab.branding.primary_color ?? '#1a1a2e' }"
              >
                <h3 class="text-xl font-bold tracking-tight">{{ pkg.name }}</h3>
                <p class="text-white/60 text-sm mt-1 leading-relaxed">{{ pkg.description }}</p>
              </div>

              <div class="p-6">
                <div class="mb-5">
                  <p class="text-3xl font-black text-gray-900 tracking-tight">
                    {{ formatPrice(pkg.final_price ?? pkg.price) }}
                  </p>
                  <p v-if="pkg.discount > 0" class="text-sm text-gray-400 line-through">
                    {{ formatPrice(pkg.price) }}
                  </p>
                  <span
                    v-if="pkg.discount > 0"
                    class="text-xs font-semibold px-2.5 py-0.5 rounded-full text-white inline-block mt-2"
                    :style="{ backgroundColor: lab.branding.secondary_color ?? '#e94560' }"
                  >
                    Hemat {{ pkg.discount }}%
                  </span>
                </div>

                <ul v-if="pkg.includes?.length" class="space-y-2.5 mb-6">
                  <li
                    v-for="item in pkg.includes"
                    :key="item"
                    class="flex items-start gap-2 text-sm text-gray-600 leading-relaxed"
                  >
                    <CheckCircle2
                      class="size-4 mt-0.5 shrink-0"
                      :style="{ color: lab.branding.secondary_color ?? '#e94560' }"
                    />
                    {{ item }}
                  </li>
                </ul>

                <button
                  @click="handleBooking"
                  class="w-full py-3 rounded-xl font-semibold text-white shadow-sm hover:shadow-md active:scale-[0.98] transition-all duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                  :style="{ backgroundColor: lab.branding.secondary_color ?? '#e94560' }"
                >
                  Pilih Paket
                </button>
              </div>
            </div>
          </div>

          <!-- Empty state -->
          <div v-else class="flex flex-col items-center text-center gap-3 py-16">
            <div class="size-12 rounded-2xl bg-gray-100 flex items-center justify-center">
              <Images class="size-6 text-gray-400" />
            </div>
            <p class="text-sm font-semibold text-gray-600">Belum ada paket tersedia</p>
            <p class="text-xs text-gray-400">
              Paket bundling akan tampil di sini setelah tersedia.
            </p>
          </div>
        </div>
      </section>

      <!-- ================================================================
           PORTFOLIO SECTION
      ================================================================ -->
      <PhotographerPortfolioSection
        v-if="lab.is_photography_lab"
        :lab-id="lab.id"
        :primary-color="lab.branding.primary_color"
        :secondary-color="lab.branding.secondary_color"
      />

      <!-- ================================================================
     CARA PEMESANAN
================================================================ -->
      <section id="cara-pesan" class="py-16 sm:py-20 bg-gray-50">
        <div class="max-w-6xl mx-auto px-6">
          <div class="text-center mb-12">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">
              Cara Pemesanan
            </h2>
            <p class="text-gray-500 mt-2">Booking lab hanya dalam 4 langkah mudah</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div
              v-for="(step, idx) in [
                {
                  icon: ClipboardList,
                  title: 'Pilih Layanan',
                  desc: 'Pilih layanan atau paket yang sesuai kebutuhanmu.',
                },
                {
                  icon: CalendarDays,
                  title: 'Pilih Jadwal',
                  desc: 'Cek ketersediaan lab dan pilih tanggal serta jam yang kosong.',
                },
                {
                  icon: CreditCard,
                  title: 'Booking & Bayar',
                  desc: 'Isi detail booking dan selesaikan pembayaran secara online.',
                },
                {
                  icon: CheckCircle,
                  title: 'Datang & Gunakan',
                  desc: 'Tunjukkan kode booking/QR di lokasi dan mulai gunakan lab.',
                },
              ]"
              :key="idx"
              class="relative bg-white rounded-2xl border border-gray-100 shadow-sm p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-300"
            >
              <span
                class="absolute top-4 right-4 text-4xl font-black opacity-[0.08] select-none"
                :style="{ color: lab.branding.primary_color ?? '#1a1a2e' }"
              >
                {{ idx + 1 }}
              </span>

              <div
                class="size-11 rounded-2xl flex items-center justify-center mb-4"
                :style="{ backgroundColor: (lab.branding.secondary_color ?? '#e94560') + '15' }"
              >
                <component
                  :is="step.icon"
                  class="size-5"
                  :style="{ color: lab.branding.secondary_color ?? '#e94560' }"
                />
              </div>

              <h3 class="font-semibold text-gray-900 tracking-tight">{{ step.title }}</h3>
              <p class="text-sm text-gray-500 mt-1.5 leading-relaxed">{{ step.desc }}</p>
            </div>
          </div>

          <div class="text-center mt-10">
            <button
              @click="handleBooking"
              class="inline-flex items-center gap-2 px-8 py-3.5 rounded-xl font-semibold text-white shadow-sm hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200"
              :style="{ backgroundColor: lab.branding.secondary_color ?? '#e94560' }"
            >
              Mulai Booking
              <ArrowRight class="size-4" />
            </button>
          </div>
        </div>
      </section>

      <!-- ================================================================
           CONTACT SECTION
      ================================================================ -->
      <section
        id="contact"
        class="py-16 sm:py-20"
        :style="{ backgroundColor: lab.branding.primary_color ?? '#1a1a2e' }"
      >
        <div class="max-w-4xl mx-auto px-6 text-center">
          <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight mb-2">
            Hubungi Kami
          </h2>
          <p class="text-white/60 mb-10">Ada pertanyaan? Jangan ragu untuk menghubungi kami</p>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-10">
            <div
              v-if="lab.contact?.email"
              class="rounded-2xl border border-white/10 backdrop-blur-md p-6 text-white hover:-translate-y-1 transition-transform duration-300"
              style="background-color: rgba(255, 255, 255, 0.08)"
            >
              <div
                class="size-11 rounded-2xl bg-white/10 flex items-center justify-center mx-auto mb-3"
              >
                <Mail class="size-5 text-white" />
              </div>
              <p class="text-xs text-white/60">Email</p>
              <p class="font-medium mt-0.5 break-words">{{ lab.contact.email }}</p>
            </div>
            <div
              v-if="lab.contact?.phone"
              class="rounded-2xl border border-white/10 backdrop-blur-md p-6 text-white hover:-translate-y-1 transition-transform duration-300"
              style="background-color: rgba(255, 255, 255, 0.08)"
            >
              <div
                class="size-11 rounded-2xl bg-white/10 flex items-center justify-center mx-auto mb-3"
              >
                <Phone class="size-5 text-white" />
              </div>
              <p class="text-xs text-white/60">Telepon</p>
              <p class="font-medium mt-0.5">{{ lab.contact.phone }}</p>
            </div>
            <div
              v-if="lab.contact?.address"
              class="rounded-2xl border border-white/10 backdrop-blur-md p-6 text-white hover:-translate-y-1 transition-transform duration-300"
              style="background-color: rgba(255, 255, 255, 0.08)"
            >
              <div
                class="size-11 rounded-2xl bg-white/10 flex items-center justify-center mx-auto mb-3"
              >
                <MapPin class="size-5 text-white" />
              </div>
              <p class="text-xs text-white/60">Alamat</p>
              <p class="font-medium mt-0.5 leading-relaxed">{{ lab.contact.address }}</p>
            </div>
          </div>

          <button
            @click="handleBooking"
            class="inline-flex items-center gap-2 px-10 py-4 rounded-xl font-bold text-white text-lg shadow-lg shadow-black/20 hover:shadow-xl hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white/70"
            :style="{ backgroundColor: lab.branding.secondary_color ?? '#e94560' }"
          >
            Book Sekarang
            <ArrowRight class="size-5" />
          </button>
        </div>
      </section>

      <!-- ================================================================
           FOOTER
      ================================================================ -->
      <footer class="bg-gray-950 py-8">
        <div class="max-w-6xl mx-auto px-6 flex flex-col items-center gap-3 text-center">
          <div class="w-full max-w-xs h-px bg-white/10" />
          <p class="text-gray-500 text-sm">
            © {{ new Date().getFullYear() }} {{ lab.name }} — Powered by SLMS
          </p>
          <RouterLink
            :to="{ name: 'lab-rfid-kiosk', params: { slug } }"
            class="text-xs text-gray-600 hover:text-gray-400 transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500 rounded"
          >
            Mode Kios RFID
          </RouterLink>
        </div>
      </footer>
    </template>
  </div>
</template>
