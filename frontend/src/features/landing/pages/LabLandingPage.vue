<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'

import api from '@/lib/axios'
import AvailabilityCalendar from '@/components/AvailabilityCalendar.vue'
import PhotographerPortfolioSection from '@/features/portfolio/components/PhotographerPortfolioSection.vue'
import { Skeleton } from '@/components/ui/skeleton'
import { Mail, Phone, MapPin, Images, Camera, CalendarClock, Info } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

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

function handleBooking() {
  if (authStore.isAuthenticated) {
    router.push(`/booking/${slug.value}`)
  } else {
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
  <div class="min-h-screen bg-white">
    <!-- Loading -->
    <div v-if="isLoading" class="min-h-screen flex flex-col items-center justify-center gap-4 px-6">
      <Skeleton class="h-10 w-64" />
      <Skeleton class="h-6 w-96 max-w-full" />
    </div>

    <template v-else-if="lab">
      <!-- ================================================================
           NAVBAR
      ================================================================ -->
      <nav
        class="fixed top-0 left-0 right-0 z-50 border-b border-white/10"
        :style="{ backgroundColor: lab.branding.primary_color ?? '#1a1a2e' }"
      >
        <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
          <span class="text-white font-bold text-lg">{{ lab.name }}</span>
          <div class="flex items-center gap-6">
            <a href="#services" class="text-white/70 hover:text-white text-sm transition-colors">
              Layanan
            </a>
            <a href="#packages" class="text-white/70 hover:text-white text-sm transition-colors">
              Paket
            </a>
            <a
              v-if="lab.is_photography_lab"
              href="#portfolio"
              class="text-white/70 hover:text-white text-sm transition-colors"
            >
              Portofolio
            </a>
            <a href="#contact" class="text-white/70 hover:text-white text-sm transition-colors">
              Kontak
            </a>
            <button
              @click="handleBooking"
              class="text-sm font-medium px-4 py-2 rounded-lg transition-colors"
              :style="{
                backgroundColor: lab.branding.secondary_color ?? '#e94560',
                color: 'white',
              }"
            >
              Book Sekarang
            </button>
          </div>
        </div>
      </nav>

      <!-- ================================================================
           HERO SECTION
      ================================================================ -->
      <section
        class="min-h-screen flex items-center justify-center pt-16 relative overflow-hidden"
        :style="{ backgroundColor: lab.branding.primary_color ?? '#1a1a2e' }"
      >
        <div class="absolute inset-0 opacity-5">
          <div
            class="absolute inset-0"
            style="
              background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0);
              background-size: 40px 40px;
            "
          />
        </div>

        <div class="max-w-4xl mx-auto px-6 text-center relative z-10">
          <div
            class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full mb-6"
            :style="{
              backgroundColor: lab.branding.secondary_color + '30',
              color: lab.branding.secondary_color ?? '#e94560',
            }"
          >
            <Camera class="size-3.5" />
            Professional Photography Studio
          </div>

          <h1 class="text-5xl md:text-7xl font-black text-white leading-tight mb-6">
            {{ lab.name }}
          </h1>

          <p class="text-xl text-white/60 max-w-2xl mx-auto mb-10">
            {{ lab.description }}
          </p>

          <div class="flex items-center justify-center gap-4">
            <button
              @click="handleBooking"
              class="px-8 py-4 rounded-xl font-semibold text-white text-lg transition-all hover:scale-105"
              :style="{ backgroundColor: lab.branding.secondary_color ?? '#e94560' }"
            >
              Book Sekarang
            </button>

            <a
              href="#services"
              class="px-8 py-4 rounded-xl font-semibold text-white/80 border border-white/20 hover:bg-white/10 transition-colors text-lg"
            >
              Lihat Layanan
            </a>
          </div>
        </div>

        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 text-white/40 animate-bounce">
          ↓
        </div>
      </section>

      <section class="max-w-5xl mx-auto px-6 py-10">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Jadwal Ketersediaan Lab</h2>

        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_320px] gap-4 items-start">
          <AvailabilityCalendar :slug="slug" :interactive="false" @day-changed="handleDayChanged" />

          <aside class="bg-white rounded-xl border border-gray-200 p-4 lg:top-4">
            <div class="flex items-center gap-2 mb-1">
              <CalendarClock class="size-4 text-gray-400 shrink-0" />
              <p class="text-sm font-semibold text-gray-900">Jam Terpakai</p>
            </div>
            <p v-if="selectedDayInfo.date" class="text-xs text-gray-400 mb-4">
              {{ formatSelectedDate(selectedDayInfo.date) }}
            </p>
            <p v-else class="text-xs text-gray-400 mb-4">Pilih tanggal untuk lihat jadwal</p>

            <div v-if="selectedDayInfo.loading" class="space-y-2">
              <div v-for="i in 3" :key="i" class="h-14 rounded-lg bg-gray-100 animate-pulse" />
            </div>

            <div v-else-if="selectedDayInfo.activities.length" class="space-y-2">
              <div
                v-for="(activity, idx) in selectedDayInfo.activities"
                :key="idx"
                class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5"
              >
                <div class="flex items-start justify-between gap-2">
                  <p class="text-sm font-medium text-gray-900 leading-snug">{{ activity.label }}</p>
                  <span
                    class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-red-50 text-red-600 shrink-0 whitespace-nowrap"
                  >
                    Terpakai
                  </span>
                </div>
                <p class="text-xs text-gray-400 mt-1">{{ activity.start }} – {{ activity.end }}</p>
              </div>
            </div>

            <div
              v-else-if="selectedDayInfo.date"
              class="flex flex-col items-center text-center gap-2 py-8 px-2"
            >
              <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center">
                <Info class="size-4.5 text-green-600" />
              </div>
              <p class="text-sm font-medium text-gray-700">Lab kosong hari ini</p>
              <p class="text-xs text-gray-400">Belum ada kegiatan terjadwal pada tanggal ini.</p>
            </div>
          </aside>
        </div>
      </section>

      <!-- ================================================================
           SERVICES SECTION
      ================================================================ -->
      <section id="services" class="py-20 bg-gray-50">
        <div class="max-w-6xl mx-auto px-6">
          <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900">Layanan Kami</h2>
            <p class="text-gray-500 mt-2">Pilih layanan yang sesuai kebutuhanmu</p>
          </div>

          <div v-if="services?.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
              v-for="service in services"
              :key="service.uuid"
              class="relative bg-white rounded-2xl border border-gray-200 p-6 hover:shadow-md transition-shadow"
            >
              <span
                v-if="service.type?.value === 'photography'"
                class="absolute top-4 right-4 text-[11px] font-medium px-2 py-1 rounded-full flex items-center gap-1"
                :style="{
                  backgroundColor: lab.branding.secondary_color + '15',
                  color: lab.branding.secondary_color ?? '#e94560',
                }"
              >
                <Images class="size-3" />
                Dapat Galeri Foto
              </span>
              <div
                class="w-full h-40 rounded-xl overflow-hidden mb-4 flex items-center justify-center"
                :style="
                  !service.image ? { backgroundColor: lab.branding.primary_color + '15' } : {}
                "
              >
                <img
                  v-if="service.image"
                  :src="service.image"
                  :alt="service.name"
                  class="w-full h-full object-cover"
                />
                <Camera
                  v-else
                  class="size-8"
                  :style="{ color: lab.branding.primary_color ?? '#1a1a2e' }"
                />
              </div>
              <h3 class="font-semibold text-gray-900 text-lg">{{ service.name }}</h3>
              <p class="text-gray-500 text-sm mt-1 line-clamp-2">{{ service.description }}</p>
              <div class="mt-4 flex items-center justify-between">
                <div>
                  <p class="text-xs text-gray-400">Mulai dari</p>
                  <p
                    class="font-bold text-lg"
                    :style="{ color: lab.branding.primary_color ?? '#1a1a2e' }"
                  >
                    {{ formatPrice(service.price) }}
                  </p>
                  <p class="text-xs text-gray-400">{{ service.pricing_type?.label }}</p>
                </div>
                <button
                  @click="handleBooking"
                  class="text-sm font-medium px-4 py-2 rounded-lg text-white transition-colors"
                  :style="{ backgroundColor: lab.branding.secondary_color ?? '#e94560' }"
                >
                  Pesan
                </button>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-12 text-gray-400">Belum ada layanan tersedia.</div>
        </div>
      </section>

      <!-- ================================================================
           PACKAGES SECTION
      ================================================================ -->
      <section id="packages" class="py-20 bg-white">
        <div class="max-w-6xl mx-auto px-6">
          <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900">Paket Foto</h2>
            <p class="text-gray-500 mt-2">Hemat lebih banyak dengan paket bundling</p>
          </div>

          <div v-if="packages?.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
              v-for="pkg in packages"
              :key="pkg.uuid"
              class="rounded-2xl border-2 overflow-hidden hover:shadow-lg transition-shadow"
              :style="{ borderColor: lab.branding.primary_color ?? '#1a1a2e' }"
            >
              <div
                class="p-6 text-white"
                :style="{ backgroundColor: lab.branding.primary_color ?? '#1a1a2e' }"
              >
                <h3 class="text-xl font-bold">{{ pkg.name }}</h3>
                <p class="text-white/60 text-sm mt-1">{{ pkg.description }}</p>
              </div>

              <div class="p-6">
                <div class="mb-4">
                  <p class="text-3xl font-black text-gray-900">
                    {{ formatPrice(pkg.final_price ?? pkg.price) }}
                  </p>
                  <p v-if="pkg.discount > 0" class="text-sm text-gray-400 line-through">
                    {{ formatPrice(pkg.price) }}
                  </p>
                  <span
                    v-if="pkg.discount > 0"
                    class="text-xs font-medium px-2 py-0.5 rounded-full text-white inline-block mt-1"
                    :style="{ backgroundColor: lab.branding.secondary_color ?? '#e94560' }"
                  >
                    Hemat {{ pkg.discount }}%
                  </span>
                </div>

                <ul v-if="pkg.includes?.length" class="space-y-2 mb-6">
                  <li
                    v-for="item in pkg.includes"
                    :key="item"
                    class="flex items-center gap-2 text-sm text-gray-600"
                  >
                    <span :style="{ color: lab.branding.secondary_color ?? '#e94560' }">✓</span>
                    {{ item }}
                  </li>
                </ul>

                <button
                  @click="handleBooking"
                  class="w-full py-3 rounded-xl font-semibold text-white transition-colors"
                  :style="{ backgroundColor: lab.branding.secondary_color ?? '#e94560' }"
                >
                  Pilih Paket
                </button>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-12 text-gray-400">Belum ada paket tersedia.</div>
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
           CONTACT SECTION
      ================================================================ -->
      <section
        id="contact"
        class="py-20"
        :style="{ backgroundColor: lab.branding.primary_color ?? '#1a1a2e' }"
      >
        <div class="max-w-4xl mx-auto px-6 text-center">
          <h2 class="text-3xl font-bold text-white mb-2">Hubungi Kami</h2>
          <p class="text-white/60 mb-10">Ada pertanyaan? Jangan ragu untuk menghubungi kami</p>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-10">
            <div v-if="lab.contact?.email" class="bg-white/10 rounded-2xl p-5 text-white">
              <Mail class="size-6 mb-2 mx-auto text-white/80" />
              <p class="text-sm text-white/60">Email</p>
              <p class="font-medium">{{ lab.contact.email }}</p>
            </div>
            <div v-if="lab.contact?.phone" class="bg-white/10 rounded-2xl p-5 text-white">
              <Phone class="size-6 mb-2 mx-auto text-white/80" />
              <p class="text-sm text-white/60">Telepon</p>
              <p class="font-medium">{{ lab.contact.phone }}</p>
            </div>
            <div v-if="lab.contact?.address" class="bg-white/10 rounded-2xl p-5 text-white">
              <MapPin class="size-6 mb-2 mx-auto text-white/80" />
              <p class="text-sm text-white/60">Alamat</p>
              <p class="font-medium">{{ lab.contact.address }}</p>
            </div>
          </div>

          <button
            @click="handleBooking"
            class="px-10 py-4 rounded-xl font-bold text-white text-lg transition-all hover:scale-105"
            :style="{ backgroundColor: lab.branding.secondary_color ?? '#e94560' }"
          >
            Book Sekarang →
          </button>
        </div>
      </section>

      <!-- Footer -->
      <footer class="bg-gray-900 py-6 text-center text-gray-500 text-sm space-y-2">
        <p>© {{ new Date().getFullYear() }} {{ lab.name }} — Powered by SLMS</p>
        <RouterLink
          :to="{ name: 'lab-rfid-kiosk', params: { slug } }"
          class="inline-block text-xs text-gray-600 hover:text-gray-400 transition-colors"
        >
          Mode Kios RFID
        </RouterLink>
      </footer>
    </template>
  </div>
</template>
