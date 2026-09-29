<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import api from '@/lib/axios'
import { markKioskSession, clearKioskSession } from '@/composables/useKioskSession'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import {
  CreditCard,
  CheckCircle2,
  Loader2,
  ArrowRight,
  Wifi,
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const slug = route.params.slug as string

const rfidInput = ref('')
const inputRef = ref<HTMLInputElement | null>(null)
const isLoading = ref(false)
const welcomeMessage = ref('')
const showWelcome = ref(false)

function focusInput() {
  nextTick(() => {
    inputRef.value?.focus()
  })
}

onMounted(() => {
  authStore.user = null
  authStore.token = null
  localStorage.removeItem('token')
  clearKioskSession()

  focusInput()
})

async function handleScan() {
  if (!rfidInput.value || isLoading.value) return

  isLoading.value = true

  try {
    const res = await api.post(`/labs/${slug}/rfid-login`, {
      rfid_uid: rfidInput.value,
    })

    const { token, user_name } = res.data.data

    authStore.token = token
    localStorage.setItem('token', token)

    await authStore.fetchUser()

    markKioskSession(slug)

    welcomeMessage.value = res.data.message
    showWelcome.value = true

    toast.success(`Selamat datang, ${user_name}!`)

    setTimeout(() => {
      router.push('/my-bookings')
    }, 3000)
  } catch (err: any) {
    toast.error(
      err.response?.data?.message ?? 'Kartu tidak terdaftar.',
    )
  } finally {
    rfidInput.value = ''
    isLoading.value = false

    if (!showWelcome.value) {
      focusInput()
    }
  }
}

function scanAgain() {
  showWelcome.value = false

  authStore.user = null
  authStore.token = null

  localStorage.removeItem('token')
  clearKioskSession()

  focusInput()
}
</script>

<template>
  <div
    class="relative min-h-screen overflow-hidden bg-white text-gray-900"
    @click="focusInput"
  >
    <!-- Background decoration -->
    <div
      class="pointer-events-none absolute -left-40 -top-40 h-96 w-96 rounded-full bg-blue-50 blur-3xl"
    />

    <div
      class="pointer-events-none absolute -bottom-40 -right-40 h-96 w-96 rounded-full bg-indigo-50 blur-3xl"
    />

    <!-- Top branding -->
    <header
      class="absolute left-0 right-0 top-0 flex items-center justify-between px-6 py-5 sm:px-10"
    >
      <div class="flex items-center gap-3">
        <img
          src="/images/logo-upi.svg"
          alt="Logo UPI"
          class="h-9 w-9 object-contain"
        />

        <div class="h-7 w-px bg-gray-200" />

        <img
          src="/images/logo-lab.svg"
          alt="Logo Laboratorium"
          class="h-9 w-9 object-contain"
        />

        <div class="hidden sm:block">
          <p class="text-sm font-semibold text-gray-900">
            Smart Laboratory
          </p>
          <p class="text-xs text-gray-400">
            UPI Kampus Cibiru
          </p>
        </div>
      </div>

      <div
        class="flex items-center gap-2 rounded-full border border-gray-200 bg-white/80 px-3 py-1.5 shadow-sm backdrop-blur"
      >
        <span class="relative flex h-2 w-2">
          <span
            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"
          />
          <span
            class="relative inline-flex h-2 w-2 rounded-full bg-green-500"
          />
        </span>

        <span class="text-xs font-medium text-gray-600">
          Kiosk aktif
        </span>
      </div>
    </header>

    <!-- Main -->
    <main
      class="relative z-10 flex min-h-screen items-center justify-center px-6 py-28"
    >
      <!-- Hidden RFID input
           Scanner tetap mengetik UID ke sini -->
      <input
        ref="inputRef"
        v-model="rfidInput"
        @keyup.enter="handleScan"
        type="text"
        inputmode="none"
        autocomplete="off"
        tabindex="0"
        aria-label="RFID scanner input"
        class="absolute h-px w-px opacity-0"
      />

      <!-- ===================== -->
      <!-- SCAN STATE -->
      <!-- ===================== -->
      <div
        v-if="!showWelcome"
        class="flex w-full max-w-xl flex-col items-center text-center"
      >
        <!-- RFID visual -->
        <div class="relative mb-10 flex h-64 w-64 items-center justify-center">
          <!-- Outer waves -->
          <div
            class="absolute h-56 w-56 animate-[ping_3s_ease-in-out_infinite] rounded-full border border-blue-100 opacity-60"
          />

          <div
            class="absolute h-44 w-44 animate-[ping_3s_ease-in-out_infinite_500ms] rounded-full border border-blue-200 opacity-70"
          />

          <div
            class="absolute h-36 w-36 rounded-full bg-blue-50"
          />

          <!-- Reader -->
          <div
            class="relative flex h-28 w-28 items-center justify-center rounded-[2rem] border border-blue-100 bg-white shadow-[0_20px_60px_-15px_rgba(59,130,246,0.25)]"
          >
            <div
              class="absolute inset-2 rounded-[1.5rem] border border-dashed border-blue-200"
            />

            <div
              class="flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-600/25"
            >
              <CreditCard class="h-8 w-8" stroke-width="1.8" />
            </div>
          </div>

          <!-- RFID signal -->
          <div
            class="absolute right-8 top-12 flex flex-col gap-1 text-blue-500"
          >
            <Wifi class="h-4 w-4 rotate-45" />
            <Wifi class="h-5 w-5 rotate-45" />
            <Wifi class="h-6 w-6 rotate-45" />
          </div>
        </div>

        <!-- Heading -->
        <div class="space-y-3">
          <div
            class="mx-auto inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700"
          >
            <span class="h-1.5 w-1.5 rounded-full bg-blue-500" />
            Siap membaca kartu
          </div>

          <h1
            class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl"
          >
            Tempelkan Kartu Mahasiswa
          </h1>

          <p
            class="mx-auto max-w-md text-sm leading-6 text-gray-500 sm:text-base"
          >
            Dekatkan kartu mahasiswa kamu ke reader
            <br class="hidden sm:block" />
            untuk melanjutkan ke halaman booking.
          </p>
        </div>

        <!-- Info -->
        <div
          class="mt-8 flex items-center gap-3 rounded-2xl border border-gray-100 bg-gray-50 px-5 py-3.5 text-left"
        >
          <div
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-blue-600 shadow-sm"
          >
            <CreditCard class="h-4 w-4" />
          </div>

          <div>
            <p class="text-xs font-semibold text-gray-700">
              Booking otomatis
            </p>

            <p class="mt-0.5 text-xs text-gray-400">
              Gratis jika sedang dalam jam kuliah
            </p>
          </div>
        </div>

        <!-- Loading -->
        <div
          v-if="isLoading"
          class="mt-7 flex items-center gap-2 text-sm text-gray-400"
        >
          <Loader2 class="h-4 w-4 animate-spin" />
          Membaca kartu...
        </div>

        <!-- Help -->
        <p class="mt-12 text-xs text-gray-400">
          Pastikan kartu berada dekat dengan reader
        </p>
      </div>

      <!-- ===================== -->
      <!-- SUCCESS STATE -->
      <!-- ===================== -->
      <div
        v-else
        class="flex w-full max-w-md flex-col items-center text-center"
      >
        <!-- Success icon -->
        <div
          class="mb-7 flex h-24 w-24 items-center justify-center rounded-full bg-green-50 ring-8 ring-green-50/50"
        >
          <div
            class="flex h-16 w-16 items-center justify-center rounded-full bg-green-500 text-white shadow-lg shadow-green-500/20"
          >
            <CheckCircle2 class="h-8 w-8" stroke-width="2" />
          </div>
        </div>

        <div class="space-y-3">
          <div
            class="text-sm font-medium text-green-600"
          >
            Kartu berhasil dibaca
          </div>

          <h2
            class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl"
          >
            {{ welcomeMessage }}
          </h2>

          <p class="text-sm leading-6 text-gray-500">
            Kamu berhasil masuk.
            <br />
            Mengarahkan ke halaman booking...
          </p>
        </div>

        <!-- Progress -->
        <div class="mt-8 w-full max-w-xs">
          <div class="h-1.5 overflow-hidden rounded-full bg-gray-100">
            <div
              class="h-full w-full origin-left animate-[shrink_3s_linear] rounded-full bg-green-500"
            />
          </div>
        </div>

        <Button
          variant="link"
          size="sm"
          class="mt-6 text-gray-500 hover:text-gray-900"
          @click.stop="scanAgain"
        >
          Bukan kamu? Scan ulang
          <ArrowRight class="ml-1 h-3.5 w-3.5" />
        </Button>
      </div>
    </main>

    <!-- Footer -->
    <footer
      class="absolute bottom-5 left-0 right-0 text-center text-xs text-gray-400"
    >
      Smart Laboratory Management System
    </footer>
  </div>
</template>

<style scoped>
@keyframes shrink {
  from {
    transform: scaleX(1);
  }

  to {
    transform: scaleX(0);
  }
}
</style>