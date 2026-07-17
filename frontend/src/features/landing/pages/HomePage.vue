<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import api from '@/lib/axios'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Images,
  CalendarCheck,
  ShieldCheck,
  ArrowRight,
  FlaskConical,
  Menu,
  X,
  Star,
  Linkedin,
  Youtube,
  Instagram,
  MapPin,
  Phone,
  Mail,
} from 'lucide-vue-next'
import { ref, onMounted, onBeforeUnmount } from 'vue'

/* ============================================================
   BUSINESS LOGIC — UNCHANGED
   (Vue Query, Axios, Router, Auth, Pinia, functions, events)
============================================================ */
const router = useRouter()
const authStore = useAuthStore()

const { data: labs, isLoading } = useQuery({
  queryKey: ['public-labs'],
  queryFn: async () => {
    const res = await api.get('/labs', { params: { is_active: true, per_page: 100 } })
    return res.data.data.data
  },
})

function goToLab(slug: string) {
  router.push(`/lab/${slug}`)
}

function goToAuth() {
  if (authStore.isAuthenticated) {
    if (authStore.hasRole('super_admin')) router.push('/admin')
    else if (authStore.hasRole('customer')) router.push('/booking')
    else router.push('/dashboard')
  } else {
    router.push({ name: 'login' })
  }
}

function getLabImage(lab: any) {
  return lab?.branding?.hero_image || lab?.branding?.logo || null
}

/* ============================================================
   UI-ONLY STATE — presentation, animation & interaction only.
   No business logic, no data fetching, no store/router changes.
============================================================ */
const isMobileMenuOpen = ref(false)
const hasScrolled = ref(false)

/* Fine noise texture used behind the hero (built as a JS string to avoid
   any HTML/CSS attribute quoting issues). */
const noiseTextureUrl =
  "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E"

function onWindowScroll() {
  hasScrolled.value = window.scrollY > 12
}

/* Decorative gallery images (unchanged source list, same as before —
   this is the hero showcase carousel, purely visual). */
const galleryImages = [
  {
    src: 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=1200&q=80',
    name: 'Studio fotografi',
  },
  {
    src: 'https://images.unsplash.com/photo-1516321497487-e288fb19713f?w=1200&q=80',
    name: 'Studio audio',
  },
  {
    src: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1200&q=80',
    name: 'Lab komputer',
  },
  {
    src: 'https://images.unsplash.com/photo-1497366811353-6870744d04b2?w=1200&q=80',
    name: 'Ruang kerja',
  },
  {
    src: 'https://images.unsplash.com/photo-1581092160562-40aa08e78837?w=1200&q=80',
    name: 'Peralatan lab',
  },
]

/* Duplicated for a seamless, unbroken infinite-loop illusion. */
const galleryLoopImages = [...galleryImages, ...galleryImages]

/* ---- Carousel engine: transform-based auto-loop, pause-on-hover,
   drag/swipe/trackpad support. Pure UI/animation — no app state. ---- */
const trackRef = ref<HTMLElement | null>(null)
const trackOffset = ref(0) // px, negative moves content left
const isPaused = ref(false)
const isDragging = ref(false)

let rafId: number | null = null
let dragStartX = 0
let dragStartOffset = 0
let loopWidth = 0 // width of one full (non-duplicated) image set
const SPEED_PX_PER_FRAME = 0.45

function measureLoopWidth() {
  const el = trackRef.value
  if (el) loopWidth = el.scrollWidth / 2
}

function tick() {
  if (!isPaused.value && !isDragging.value && loopWidth > 0) {
    trackOffset.value -= SPEED_PX_PER_FRAME
    if (Math.abs(trackOffset.value) >= loopWidth) {
      trackOffset.value += loopWidth
    }
  }
  rafId = requestAnimationFrame(tick)
}

function pauseCarousel() {
  isPaused.value = true
}
function resumeCarousel() {
  isPaused.value = false
}

function onDragStart(clientX: number) {
  isDragging.value = true
  isPaused.value = true
  dragStartX = clientX
  dragStartOffset = trackOffset.value
}
function onDragMove(clientX: number) {
  if (!isDragging.value) return
  const delta = clientX - dragStartX
  let next = dragStartOffset + delta
  if (loopWidth > 0) {
    // keep offset normalized within one loop width for seamless wrap
    while (next <= -loopWidth) next += loopWidth
    while (next > 0) next -= loopWidth
  }
  trackOffset.value = next
}
function onDragEnd() {
  isDragging.value = false
}

function onMouseDown(e: MouseEvent) {
  onDragStart(e.clientX)
}
function onMouseMove(e: MouseEvent) {
  onDragMove(e.clientX)
}
function onTouchStart(e: TouchEvent) {
  const touch = e.touches[0]
  if (!touch) return
  onDragStart(touch.clientX)
}
function onTouchMove(e: TouchEvent) {
  const touch = e.touches[0]
  if (!touch) return
  onDragMove(touch.clientX)
}
function onWheel(e: WheelEvent) {
  // trackpad horizontal scroll nudges the carousel too
  if (Math.abs(e.deltaX) > Math.abs(e.deltaY)) {
    e.preventDefault()
    let next = trackOffset.value - e.deltaX
    if (loopWidth > 0) {
      while (next <= -loopWidth) next += loopWidth
      while (next > 0) next -= loopWidth
    }
    trackOffset.value = next
  }
}

/* ---- Subtle scroll parallax for the hero carousel ---- */
const parallaxY = ref(0)
function onParallaxScroll() {
  parallaxY.value = window.scrollY * 0.08
}

function handleScroll() {
  onWindowScroll()
  onParallaxScroll()
}

onMounted(() => {
  measureLoopWidth()
  rafId = requestAnimationFrame(tick)
  window.addEventListener('scroll', handleScroll, { passive: true })
  window.addEventListener('mousemove', onMouseMove)
  window.addEventListener('mouseup', onDragEnd)
  window.addEventListener('touchmove', onTouchMove, { passive: true })
  window.addEventListener('touchend', onDragEnd)
  window.addEventListener('resize', measureLoopWidth)
})

onBeforeUnmount(() => {
  if (rafId) cancelAnimationFrame(rafId)
  window.removeEventListener('scroll', handleScroll)
  window.removeEventListener('mousemove', onMouseMove)
  window.removeEventListener('mouseup', onDragEnd)
  window.removeEventListener('touchmove', onTouchMove)
  window.removeEventListener('touchend', onDragEnd)
  window.removeEventListener('resize', measureLoopWidth)
})

const testimonials = [
  {
    name: 'Ayu Lestari',
    role: 'Mahasiswa DKV',
    rating: 5,
    comment:
      'Ruangan serbaguna digunakan untuk berbagai macam kegiatan yang dapat disesuaikan dengan fasilitas ruangan dan kapasitas ruangan.',
  },
  {
    name: 'Rizky Pratama',
    role: 'Mahasiswa Ilmu Komputer',
    rating: 5,
    comment:
      'Proses booking sangat mudah dan cepat. Fasilitas lab lengkap serta staf yang membantu.',
  },
  {
    name: 'Dinda Amelia',
    role: 'Mahasiswa Broadcasting',
    rating: 4,
    comment: 'Kualitas peralatan sangat baik dan terawat. Sangat membantu kebutuhan produksi kami.',
  },
]
</script>

<template>
  <div class="min-h-screen bg-white antialiased">
    <!-- ================================================================
         NAVBAR — floating, glass, Material-ish
    ================================================================ -->
    <nav
      class="fixed top-3 left-3 right-3 sm:top-4 sm:left-6 sm:right-6 z-50 transition-all duration-300"
      :class="hasScrolled ? 'shadow-[0_8px_30px_-12px_rgba(0,0,0,0.35)]' : 'shadow-none'"
    >
      <div
        class="max-w-7xl mx-auto rounded-2xl border border-white/10 bg-[#101825]/70 backdrop-blur-xl px-4 sm:px-6 h-14 sm:h-16 flex items-center justify-between"
      >
        <div class="flex items-center gap-3">
          <img
            src="/images/logo-lab.svg"
            alt="Logo SLMS"
            class="h-7 w-7 sm:h-8 sm:w-8 object-contain shrink-0"
          />
          <span class="h-5 w-px bg-white/15 shrink-0" aria-hidden="true" />
          <img
            src="/images/logo-upi.svg"
            alt="Logo UPI"
            class="h-7 sm:h-8 w-auto max-w-20 sm:max-w-24 object-contain shrink-0"
          />
        </div>

        <!-- Desktop menu -->
        <div class="hidden md:flex items-center gap-8">
          <a
            href="#home"
            class="relative text-white/60 hover:text-white text-sm font-medium transition-colors group"
          >
            Home
            <span
              class="absolute -bottom-1 left-0 w-0 h-px bg-blue-400 transition-all duration-300 group-hover:w-full"
            />
          </a>
          <a
            href="#layanan"
            class="relative text-white/60 hover:text-white text-sm font-medium transition-colors group"
          >
            Layanan
            <span
              class="absolute -bottom-1 left-0 w-0 h-px bg-blue-400 transition-all duration-300 group-hover:w-full"
            />
          </a>
          <a
            href="#tentang"
            class="relative text-white/60 hover:text-white text-sm font-medium transition-colors group"
          >
            Tentang
            <span
              class="absolute -bottom-1 left-0 w-0 h-px bg-blue-400 transition-all duration-300 group-hover:w-full"
            />
          </a>
          <Button
            size="sm"
            class="bg-blue-600 hover:bg-blue-500 rounded-full px-5 shadow-[0_2px_12px_-2px_rgba(37,99,235,0.6)] transition-all hover:shadow-[0_4px_18px_-2px_rgba(37,99,235,0.75)]"
            @click="goToAuth"
          >
            {{ authStore.isAuthenticated ? 'Dashboard' : 'Masuk' }}
          </Button>
        </div>

        <!-- Mobile hamburger -->
        <button
          class="md:hidden w-9 h-9 rounded-full flex items-center justify-center text-white hover:bg-white/10 transition-colors"
          @click="isMobileMenuOpen = !isMobileMenuOpen"
        >
          <Menu v-if="!isMobileMenuOpen" class="size-5" />
          <X v-else class="size-5" />
        </button>
      </div>

      <!-- Mobile drawer -->
      <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
      >
        <div
          v-if="isMobileMenuOpen"
          class="md:hidden mt-2 rounded-2xl border border-white/10 bg-[#101825]/90 backdrop-blur-xl px-6 py-5 flex flex-col gap-4"
        >
          <a href="#home" class="text-white/80 text-sm" @click="isMobileMenuOpen = false">Home</a>
          <a href="#layanan" class="text-white/80 text-sm" @click="isMobileMenuOpen = false"
            >Layanan</a
          >
          <a href="#tentang" class="text-white/80 text-sm" @click="isMobileMenuOpen = false"
            >Tentang</a
          >
          <Button
            size="sm"
            class="bg-blue-600 hover:bg-blue-500 rounded-full w-full"
            @click="goToAuth"
          >
            {{ authStore.isAuthenticated ? 'Dashboard' : 'Masuk' }}
          </Button>
        </div>
      </transition>
    </nav>

    <!-- ================================================================
         HERO — immersive, 100vh, carousel peeks from below the fold
    ================================================================ -->
    <section
      id="home"
      class="relative h-screen min-h-180 flex flex-col bg-[#0b1220] overflow-hidden"
    >
      <!-- Ambient gradient + blur circles -->
      <div class="absolute inset-0 pointer-events-none">
        <div
          class="absolute -top-40 -left-32 w-130 h-130 rounded-full bg-blue-600/25 blur-[120px]"
        />
        <div
          class="absolute top-1/3 -right-32 w-120 h-[480px] rounded-full bg-indigo-500/20 blur-[130px]"
        />
        <div class="absolute inset-0 bg-gradient-to-b from-[#0b1220] via-[#0e1526] to-[#101a2c]" />
      </div>

      <!-- Fine noise texture -->
      <div
        class="absolute inset-0 opacity-[0.05] pointer-events-none mix-blend-overlay"
        :style="{ backgroundImage: `url(${noiseTextureUrl})` }"
      />

      <!-- Copy block -->
      <div class="relative z-10 flex-1 flex flex-col items-center justify-center px-6 pt-20 pb-6">
        <div class="max-w-3xl mx-auto text-center">
          <div
            class="inline-flex items-center gap-2 text-xs font-medium px-4 py-1.5 rounded-full mb-6 bg-white/5 text-blue-200/90 border border-white/10 backdrop-blur-sm"
          >
            <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse" />
            Smart Lab Management System
          </div>

          <h1
            class="text-4xl sm:text-6xl md:text-7xl font-bold text-white leading-[1.05] mb-6 tracking-tight"
          >
            Kelola semua lab<br />
            <span
              class="text-transparent bg-clip-text bg-gradient-to-r from-blue-300 via-blue-200 to-indigo-300"
            >
              dalam satu platform
            </span>
          </h1>

          <p class="text-base sm:text-lg text-white/50 max-w-xl mx-auto mb-10 leading-relaxed">
            Booking studio, sewa peralatan, hingga pengelolaan layanan laboratorium — semuanya jadi
            mudah.
          </p>

          <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a
              href="#layanan"
              class="w-full sm:w-auto text-center px-7 py-3.5 rounded-full font-semibold text-white text-base bg-blue-600 hover:bg-blue-500 transition-all duration-300 ease-out shadow-[0_8px_24px_-8px_rgba(37,99,235,0.7)] hover:shadow-[0_10px_30px_-6px_rgba(37,99,235,0.85)] hover:-translate-y-0.5"
            >
              Lihat Laboratorium
            </a>
            <button
              @click="goToAuth"
              class="w-full sm:w-auto px-7 py-3.5 rounded-full font-semibold text-white/90 border border-white/15 hover:bg-white/5 hover:border-white/25 transition-all duration-300 ease-out hover:-translate-y-0.5 text-base"
            >
              {{ authStore.isAuthenticated ? 'Ke Dashboard' : 'Dashboard' }}
            </button>
          </div>
        </div>
      </div>

      <!-- ============== HERO SHOWCASE CAROUSEL (peek effect) ============== -->
      <!-- Container is positioned so only its top portion is visible on load;
           scrolling reveals the rest naturally (Apple/Stripe/Linear style). -->
      <div
        class="relative z-10 w-full translate-y-[42%] sm:translate-y-[38%] md:translate-y-[34%]"
        :style="{ transform: `translateY(${42 - Math.min(parallaxY, 40)}%)` }"
      >
        <div
          class="relative w-full h-[260px] sm:h-[420px] md:h-[560px] lg:h-[640px] overflow-hidden select-none cursor-grab active:cursor-grabbing shadow-[0_30px_80px_-20px_rgba(0,0,0,0.55)]"
          @mouseenter="pauseCarousel"
          @mouseleave="resumeCarousel"
          @mousedown="onMouseDown"
          @touchstart="onTouchStart"
          @wheel="onWheel"
        >
          <!-- Track -->
          <div
            ref="trackRef"
            class="flex h-full will-change-transform"
            :style="{
              transform: `translateX(${trackOffset}px)`,
              transition: isDragging ? 'none' : 'transform 0s linear',
            }"
          >
            <div
              v-for="(g, idx) in galleryLoopImages"
              :key="idx"
              class="relative flex-none h-full w-[85vw] sm:w-[55vw] md:w-[42vw] lg:w-[34vw] group"
            >
              <img
                :src="g.src"
                :alt="g.name"
                draggable="false"
                class="w-full h-full object-cover transition-transform duration-[600ms] ease-out group-hover:scale-105"
              />
            </div>
          </div>

          <!-- Edge overlay gradients -->
          <div
            class="pointer-events-none absolute inset-y-0 left-0 w-16 sm:w-28 bg-gradient-to-r from-[#0b1220] to-transparent"
          />
          <div
            class="pointer-events-none absolute inset-y-0 right-0 w-16 sm:w-28 bg-gradient-to-l from-[#0b1220] to-transparent"
          />
        </div>
      </div>
    </section>

    <!-- ================================================================
         LAB GRID — LAYANAN
    ================================================================ -->
    <section id="layanan" class="pt-28 sm:pt-36 pb-20 sm:pb-28 bg-white">
      <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-14">
          <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 tracking-tight">
            Laboratorium Kami
          </h2>
          <p class="text-gray-500 mt-3 text-base sm:text-lg">
            Pilih laboratorium sesuai kebutuhanmu
          </p>
        </div>

        <!-- Loading skeleton -->
        <div v-if="isLoading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
          <div v-for="i in 6" :key="i" class="rounded-2xl overflow-hidden border border-gray-100">
            <Skeleton class="h-48 w-full rounded-none" />
            <div class="p-6 space-y-3">
              <Skeleton class="h-5 w-2/3 rounded-md" />
              <Skeleton class="h-4 w-full rounded-md" />
              <Skeleton class="h-4 w-1/3 rounded-md" />
            </div>
          </div>
        </div>

        <!-- Grid -->
        <div
          v-else-if="labs?.length"
          class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8"
        >
          <button
            v-for="lab in labs"
            :key="lab.uuid"
            @click="goToLab(lab.slug)"
            class="group text-left bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 ease-out hover:-translate-y-1"
          >
            <div class="h-48 relative overflow-hidden bg-gray-50 flex items-center justify-center">
              <img
                v-if="getLabImage(lab)"
                :src="getLabImage(lab)"
                :alt="lab.name"
                class="w-full h-full object-cover transition-transform duration-300 ease-out group-hover:scale-105"
              />
              <span v-else class="text-5xl font-black text-gray-200">{{ lab.name.charAt(0) }}</span>
              <span
                class="absolute top-3 left-3 text-xs font-semibold px-3 py-1 rounded-full bg-white/90 backdrop-blur text-gray-800 shadow-sm"
              >
                {{ lab.name.split(' ')[0] }}
              </span>
            </div>

            <div class="p-6">
              <h3
                class="font-bold text-gray-900 text-lg group-hover:text-blue-600 transition-colors"
              >
                {{ lab.name }}
              </h3>
              <p class="text-sm text-gray-500 mt-2 line-clamp-2 leading-relaxed">
                {{
                  lab.description ?? 'Lighting, Backdrop, Aksesoris lainnya dan ruangan editorial'
                }}
              </p>

              <div class="mt-5 flex items-center justify-between">
                <div class="flex gap-1.5">
                  <div
                    class="w-3 h-3 rounded-full border border-gray-200"
                    :style="{ backgroundColor: lab.branding?.primary_color ?? '#ccc' }"
                  />
                  <div
                    class="w-3 h-3 rounded-full border border-gray-200"
                    :style="{ backgroundColor: lab.branding?.secondary_color ?? '#ccc' }"
                  />
                </div>
                <span
                  class="text-sm text-blue-600 font-semibold group-hover:underline flex items-center gap-1"
                >
                  Kunjungi
                  <ArrowRight
                    class="size-3.5 transition-transform duration-300 group-hover:translate-x-1"
                  />
                </span>
              </div>
            </div>
          </button>
        </div>

        <!-- Empty -->
        <div v-else class="text-center py-20">
          <div
            class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mx-auto mb-4"
          >
            <FlaskConical class="size-8 text-gray-300" />
          </div>
          <p class="text-gray-400 text-base">Belum ada laboratorium tersedia.</p>
        </div>
      </div>
    </section>

    <!-- ================================================================
         TENTANG
    ================================================================ -->
    <section id="tentang" class="py-20 sm:py-28 bg-[#0e1526] relative overflow-hidden">
      <div
        class="absolute -top-24 left-1/2 -translate-x-1/2 w-[600px] h-[300px] rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"
      />
      <div class="max-w-4xl mx-auto px-6 text-center relative z-10">
        <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4 tracking-tight">Tentang SLMS</h2>
        <p class="text-white/50 text-base sm:text-lg leading-relaxed max-w-2xl mx-auto">
          Smart Lab Management System adalah platform terpadu untuk mengelola berbagai laboratorium.
          Mulai dari booking studio, sewa peralatan, hingga pengelolaan layanan, semuanya dalam satu
          sistem yang mudah digunakan.
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mt-14">
          <div
            v-for="feat in [
              {
                icon: Images,
                title: 'Multi Laboratorium',
                desc: 'Kelola berbagai jenis lab dalam satu platform',
              },
              {
                icon: CalendarCheck,
                title: 'Booking Mudah',
                desc: 'Sistem booking yang cepat dan transparan',
              },
              {
                icon: ShieldCheck,
                title: 'Aman & Terpercaya',
                desc: 'Data dan transaksi terjamin keamanannya',
              },
            ]"
            :key="feat.title"
            class="rounded-2xl border border-white/10 bg-white/[0.03] p-8 flex flex-col items-center transition-all duration-300 ease-out hover:bg-white/[0.06] hover:-translate-y-1"
          >
            <div class="w-12 h-12 rounded-xl bg-blue-500/15 flex items-center justify-center mb-4">
              <component :is="feat.icon" class="size-6 text-blue-300" />
            </div>
            <h3 class="font-semibold text-white">{{ feat.title }}</h3>
            <p class="text-sm text-white/50 mt-2 leading-relaxed">{{ feat.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ================================================================
         TESTIMONI
    ================================================================ -->
    <section class="py-20 sm:py-28 bg-white">
      <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-14">
          <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 tracking-tight">Kata Mereka</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 sm:gap-8">
          <div
            v-for="(t, idx) in testimonials"
            :key="idx"
            class="rounded-2xl bg-gray-50 border border-gray-100 p-8 flex flex-col items-center text-center transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg"
          >
            <p class="text-gray-600 leading-relaxed mb-6">{{ t.comment }}</p>
            <div class="flex gap-1 mb-3">
              <Star v-for="s in t.rating" :key="s" class="size-4 fill-orange-400 text-orange-400" />
            </div>
            <p class="font-semibold text-gray-900">{{ t.name }}</p>
            <p class="text-xs text-gray-400">{{ t.role }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ================================================================
         FOOTER
    ================================================================ -->
    <footer class="bg-[#0e1526] pt-16 pb-8">
      <div class="max-w-6xl mx-auto px-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-10 pb-10 border-b border-white/10">
          <div>
            <span class="text-lg font-bold text-white">SLMS</span>
            <p class="text-white font-medium mt-3">Smart Lab Management System</p>
            <p class="text-white/50 text-sm mt-1">Kampus di Cibiru</p>

            <div class="flex items-start gap-2 text-white/50 text-sm mt-4">
              <MapPin class="size-4 mt-0.5 flex-none" />
              <span
                >Jl. Pendidikan No.15, Cibiru Wetan, Kec. Cileunyi, Kabupaten Bandung, Jawa Barat
                40625</span
              >
            </div>
            <div class="flex items-center gap-2 text-white/50 text-sm mt-2">
              <Phone class="size-4 flex-none" />
              <span>+62 812-2277-7849</span>
            </div>
            <div class="flex items-center gap-2 text-white/50 text-sm mt-2">
              <Mail class="size-4 flex-none" />
              <span>upicibiru@gmail.com</span>
            </div>
          </div>

          <div class="sm:text-right">
            <p class="text-white/60 text-sm mb-4">Ikuti kami</p>
            <div class="flex sm:justify-end gap-3">
              <a
                href="#"
                class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition-colors"
              >
                <Linkedin class="size-4 text-white/70" />
              </a>
              <a
                href="#"
                class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition-colors"
              >
                <Youtube class="size-4 text-white/70" />
              </a>
              <a
                href="#"
                class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition-colors"
              >
                <Instagram class="size-4 text-white/70" />
              </a>
            </div>
          </div>
        </div>

        <p class="text-center text-white/40 text-xs pt-6">
          © {{ new Date().getFullYear() }} Smart Lab Management System. All Rights Reserved.
        </p>
      </div>
    </footer>
  </div>
</template>

<style scoped>
@media (prefers-reduced-motion: reduce) {
  * {
    transition-duration: 0.01ms !important;
    animation-duration: 0.01ms !important;
  }
}
</style>
