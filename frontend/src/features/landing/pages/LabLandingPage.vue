<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import api from '@/lib/axios'

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
</script>

<template>
  <div class="min-h-screen bg-white">
    <!-- Loading -->
    <div v-if="isLoading" class="min-h-screen flex items-center justify-center">
      <div class="text-gray-500">Memuat...</div>
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
        <!-- Background pattern -->
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
            class="inline-block text-xs font-semibold px-3 py-1 rounded-full mb-6"
            :style="{
              backgroundColor: lab.branding.secondary_color + '30',
              color: lab.branding.secondary_color ?? '#e94560',
            }"
          >
            ✦ Professional Photography Studio
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

        <!-- Scroll indicator -->
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 text-white/40 animate-bounce">
          ↓
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
              class="bg-white rounded-2xl border border-gray-200 p-6 hover:shadow-md transition-shadow"
            >
              <div
                class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 text-2xl"
                :style="{ backgroundColor: lab.branding.primary_color + '15' }"
              >
                📸
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
              <!-- Package Header -->
              <div
                class="p-6 text-white"
                :style="{ backgroundColor: lab.branding.primary_color ?? '#1a1a2e' }"
              >
                <h3 class="text-xl font-bold">{{ pkg.name }}</h3>
                <p class="text-white/60 text-sm mt-1">{{ pkg.description }}</p>
              </div>

              <!-- Package Body -->
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
                    class="text-xs font-medium px-2 py-0.5 rounded-full text-white"
                    :style="{ backgroundColor: lab.branding.secondary_color ?? '#e94560' }"
                  >
                    Hemat {{ pkg.discount }}%
                  </span>
                </div>

                <!-- Includes -->
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
              <div class="text-2xl mb-2">📧</div>
              <p class="text-sm text-white/60">Email</p>
              <p class="font-medium">{{ lab.contact.email }}</p>
            </div>
            <div v-if="lab.contact?.phone" class="bg-white/10 rounded-2xl p-5 text-white">
              <div class="text-2xl mb-2">📞</div>
              <p class="text-sm text-white/60">Telepon</p>
              <p class="font-medium">{{ lab.contact.phone }}</p>
            </div>
            <div v-if="lab.contact?.address" class="bg-white/10 rounded-2xl p-5 text-white">
              <div class="text-2xl mb-2">📍</div>
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
      <footer class="bg-gray-900 py-6 text-center text-gray-500 text-sm">
        © {{ new Date().getFullYear() }} {{ lab.name }} — Powered by SLMS
      </footer>
    </template>
  </div>
</template>
