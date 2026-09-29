<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { availabilityApi, type DaySlot } from '@/features/booking/api/availabilityApi'
import { useAvailabilityColor } from '@/composables/useAvailabilityColor'

const props = withDefaults(
  defineProps<{
    slug: string
    interactive?: boolean
    minDate?: string
    /**
     * Jadwal yang sudah terkonfirmasi sebelumnya (misalnya dari draft booking
     * yang dipilih user di landing page). Kalau diisi, kalender langsung
     * membuka tanggal tersebut dan menandai slotnya sebagai terkonfirmasi,
     * tanpa user perlu memilih ulang.
     */
    initialConfirmedSlot?: {
      date: string
      start: string
      end: string
      durationHours: number
    } | null
  }>(),
  { interactive: false, initialConfirmedSlot: null },
)

const emit = defineEmits<{
  'confirm-slot': [payload: { date: string; start: string; end: string; durationHours: number }]
  'day-changed': [
    payload: {
      date: string | null
      loading: boolean
      operationalHours: { open: string; close: string } | null
      activities: { start: string; end: string; label: string }[]
    },
  ]
}>()

const today = new Date()
const currentYear = ref(today.getFullYear())
const currentMonth = ref(today.getMonth() + 1)
const selectedDate = ref<string | null>(null)

const pendingSlot = ref<DaySlot | null>(null)
const pendingDuration = ref(1)
const confirmedSlot = ref<{
  date: string
  start: string
  end: string
  durationHours: number
} | null>(null)

// Guard supaya initialConfirmedSlot hanya diterapkan sekali (begitu tersedia),
// dan tidak menimpa ulang pilihan interaktif user setelahnya.
const hasAppliedInitialSlot = ref(false)

const monthKey = computed(
  () => `${currentYear.value}-${String(currentMonth.value).padStart(2, '0')}`,
)

const minDateValue = computed(() => {
  if (props.minDate) return props.minDate
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  return tomorrow.toISOString().split('T')[0]
})

const { data: monthData, isLoading: monthLoading } = useQuery({
  queryKey: ['availability-month', props.slug, monthKey],
  queryFn: async () => {
    const res = await availabilityApi.getMonth(props.slug, monthKey.value)
    return res.data.data
  },
})

const { data: dayData, isLoading: dayLoading } = useQuery({
  queryKey: ['availability-day', props.slug, selectedDate],
  queryFn: async () => {
    const res = await availabilityApi.getDay(props.slug, selectedDate.value as string)
    return res.data.data
  },
  enabled: computed(() => !!selectedDate.value),
})

watch(
  [selectedDate, dayLoading, dayData],
  () => {
    emit('day-changed', {
      date: selectedDate.value,
      loading: dayLoading.value,
      operationalHours: dayData.value?.operational_hours ?? null,
      activities: dayData.value?.activities ?? [],
    })
  },
  { immediate: true },
)

// Terapkan jadwal awal (kalau ada) begitu tersedia — juga menangani kasus
// prop-nya baru terisi SETELAH komponen ini mount (mis. parent masih
// memuat draft secara async), bukan cuma saat mount pertama.
watch(
  () => props.initialConfirmedSlot,
  (slot) => {
    if (!slot || hasAppliedInitialSlot.value) return
    hasAppliedInitialSlot.value = true
    selectedDate.value = slot.date
    confirmedSlot.value = { ...slot }
    currentYear.value = new Date(slot.date).getFullYear()
    currentMonth.value = new Date(slot.date).getMonth() + 1
  },
  { immediate: true },
)

const monthLabel = computed(() =>
  new Date(currentYear.value, currentMonth.value - 1, 1).toLocaleDateString('id-ID', {
    month: 'long',
    year: 'numeric',
  }),
)

const calendarDays = computed(() => {
  const firstDay = new Date(currentYear.value, currentMonth.value - 1, 1)
  const daysInMonth = new Date(currentYear.value, currentMonth.value, 0).getDate()
  const startWeekday = firstDay.getDay()

  const cells: { date: string | null; day: number | null; status: string | null }[] = []
  for (let i = 0; i < startWeekday; i++) cells.push({ date: null, day: null, status: null })

  for (let d = 1; d <= daysInMonth; d++) {
    const dateStr = `${currentYear.value}-${String(currentMonth.value).padStart(2, '0')}-${String(d).padStart(2, '0')}`
    cells.push({ date: dateStr, day: d, status: monthData.value?.days[dateStr] ?? 'available' })
  }
  return cells
})

const pendingSlotIndexRange = computed(() => {
  if (!pendingSlot.value || !dayData.value) return null
  const idx = dayData.value.slots.findIndex((s) => s.start === pendingSlot.value!.start)
  if (idx === -1) return null
  return { start: idx, end: idx + pendingDuration.value - 1 }
})

function isPast(dateStr: string) {
  if (!minDateValue.value) return false
  return dateStr < minDateValue.value
}

function prevMonth() {
  if (currentMonth.value === 1) {
    currentMonth.value = 12
    currentYear.value -= 1
  } else {
    currentMonth.value -= 1
  }
}

function nextMonth() {
  if (currentMonth.value === 12) {
    currentMonth.value = 1
    currentYear.value += 1
  } else {
    currentMonth.value += 1
  }
}

function selectDate(dateStr: string | null) {
  if (!dateStr || isPast(dateStr)) return
  selectedDate.value = dateStr
  pendingSlot.value = null
  pendingDuration.value = 1
  confirmedSlot.value = null
}

function pickSlot(slot: DaySlot) {
  if (!props.interactive || !slot.available || !dayData.value) return
  pendingSlot.value = slot
  pendingDuration.value = 1
  confirmedSlot.value = null
}

function canExtendTo(duration: number): boolean {
  if (!pendingSlot.value || !dayData.value) return false
  const startIdx = dayData.value.slots.findIndex((s) => s.start === pendingSlot.value!.start)
  if (startIdx === -1) return false
  for (let i = startIdx; i < startIdx + duration; i++) {
    const slot = dayData.value.slots[i]
    if (!slot || !slot.available) return false
  }
  return true
}

function setDuration(duration: number) {
  if (!canExtendTo(duration)) return
  pendingDuration.value = duration
}

function cancelSelection() {
  pendingSlot.value = null
  pendingDuration.value = 1
}

function changeSchedule() {
  confirmedSlot.value = null
  pendingSlot.value = null
}

function confirmSelection() {
  if (!pendingSlot.value || !selectedDate.value || !dayData.value) return

  const startIdx = dayData.value.slots.findIndex((s) => s.start === pendingSlot.value!.start)
  const endSlot = dayData.value.slots[startIdx + pendingDuration.value - 1]
  if (!endSlot) return

  const payload = {
    date: selectedDate.value,
    start: pendingSlot.value.start,
    end: endSlot.end,
    durationHours: pendingDuration.value,
  }

  confirmedSlot.value = payload
  pendingSlot.value = null
  emit('confirm-slot', payload)
}
const { cellColor } = useAvailabilityColor()

function slotClass(slot: DaySlot, index: number) {
  if (confirmedSlot.value && slot.start === confirmedSlot.value.start) {
    return 'border-blue-600 bg-blue-600 text-white'
  }
  const range = pendingSlotIndexRange.value
  if (range && index >= range.start && index <= range.end) {
    return 'border-blue-400 bg-blue-100 text-blue-700 ring-2 ring-blue-400'
  }
  if (!slot.available) {
    return 'border-gray-200 bg-gray-50 text-gray-400 cursor-not-allowed'
  }
  return props.interactive
    ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100 cursor-pointer'
    : 'border-green-200 bg-green-50 text-green-700'
}

onMounted(() => {
  // Kalau initialConfirmedSlot sudah diterapkan lewat watcher di atas,
  // jangan timpa lagi dengan tanggal fallback (besok).
  if (!selectedDate.value) {
    selectedDate.value = minDateValue.value ?? null
  }
})
</script>

<template>
  <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="flex items-center justify-between px-3 sm:px-4 py-3 border-b border-gray-100">
      <button
        @click="prevMonth"
        class="w-9 h-9 sm:w-8 sm:h-8 rounded-lg hover:bg-gray-100 active:bg-gray-200 flex items-center justify-center text-lg shrink-0"
      >
        ‹
      </button>
      <p class="text-sm font-semibold text-gray-900 capitalize">{{ monthLabel }}</p>
      <button
        @click="nextMonth"
        class="w-9 h-9 sm:w-8 sm:h-8 rounded-lg hover:bg-gray-100 active:bg-gray-200 flex items-center justify-center text-lg shrink-0"
      >
        ›
      </button>
    </div>

    <div class="p-2.5 sm:p-4">
      <div
        class="grid grid-cols-7 gap-1 sm:gap-1 text-center text-[11px] sm:text-xs text-gray-400 mb-2"
      >
        <span v-for="d in ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']" :key="d">{{ d }}</span>
      </div>

      <div v-if="monthLoading" class="py-8 text-center text-sm text-gray-400">
        Memuat kalender...
      </div>

      <div v-else class="grid grid-cols-7 gap-1 sm:gap-1.5">
        <button
          v-for="(cell, idx) in calendarDays"
          :key="idx"
          :disabled="!cell.date || isPast(cell.date)"
          @click="selectDate(cell.date)"
          class="aspect-square min-h-9 sm:min-h-0 rounded-lg text-[13px] sm:text-sm font-medium transition-colors flex items-center justify-center"
          :class="[
            !cell.date && 'invisible',
            cell.date && isPast(cell.date) && 'text-gray-300 cursor-not-allowed',
            cell.date && !isPast(cell.date) && cellColor(cell.status),
            cell.date === selectedDate && 'ring-2 ring-blue-500',
          ]"
        >
          {{ cell.day }}
        </button>
      </div>

      <div
        class="flex flex-wrap items-center gap-x-4 gap-y-1.5 mt-3 text-[11px] sm:text-xs text-gray-500"
      >
        <span class="flex items-center gap-1"
          ><span class="w-2.5 h-2.5 rounded-sm bg-green-50 border border-green-200 shrink-0" />
          Kosong</span
        >
        <span class="flex items-center gap-1"
          ><span class="w-2.5 h-2.5 rounded-sm bg-yellow-50 border border-yellow-200 shrink-0" />
          Sebagian</span
        >
        <span class="flex items-center gap-1"
          ><span class="w-2.5 h-2.5 rounded-sm bg-red-50 border border-red-200 shrink-0" />
          Penuh</span
        >
      </div>
    </div>

    <div v-if="selectedDate" class="border-t border-gray-100 p-3 sm:p-4">
      <p class="text-sm font-semibold text-gray-900 mb-3 leading-snug">
        Jadwal
        {{
          new Date(selectedDate).toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
          })
        }}
        <span
          v-if="dayData"
          class="block sm:inline font-normal text-gray-400 text-xs sm:text-sm mt-0.5 sm:mt-0"
        >
          ({{ dayData.operational_hours.open }}–{{ dayData.operational_hours.close }})
        </span>
      </p>

      <div v-if="dayLoading" class="py-4 text-center text-sm text-gray-400">Memuat jadwal...</div>

      <template v-else-if="dayData">
        <!-- Grid slot jam: 2 kolom di layar sangat kecil, 3 di mobile besar, 4 di sm+
             — sebelumnya langsung 3-4 kolom sehingga tombol jadi terlalu kecil
             untuk disentuh di layar sempit. -->
        <div class="grid grid-cols-2 xs:grid-cols-3 sm:grid-cols-4 gap-2 sm:gap-2">
          <button
            v-for="(slot, index) in dayData.slots"
            :key="slot.start"
            :disabled="!slot.available || !interactive"
            @click="pickSlot(slot)"
            class="py-2.5 sm:py-2 px-2 rounded-lg text-sm sm:text-xs font-medium border transition-colors min-h-11 sm:min-h-0"
            :class="slotClass(slot, index)"
          >
            {{ slot.start }}
          </button>
        </div>

        <div
          v-if="interactive && pendingSlot"
          class="mt-4 p-3.5 sm:p-4 rounded-xl bg-blue-50 border border-blue-200"
        >
          <p class="text-sm text-blue-900 font-medium mb-3">
            Kamu memilih jam <strong>{{ pendingSlot.start }}</strong>
          </p>

          <div class="mb-3">
            <label class="text-xs font-medium text-blue-800 mb-1.5 block">Durasi (jam)</label>
            <div class="grid grid-cols-4 gap-2">
              <button
                v-for="d in [1, 2, 3, 4]"
                :key="d"
                :disabled="!canExtendTo(d)"
                @click="setDuration(d)"
                class="py-2.5 sm:py-1.5 px-2 rounded-lg text-sm sm:text-xs font-medium border transition-colors min-h-11 sm:min-h-0"
                :class="
                  pendingDuration === d
                    ? 'bg-blue-600 border-blue-600 text-white'
                    : canExtendTo(d)
                      ? 'bg-white border-blue-200 text-blue-700 hover:bg-blue-100'
                      : 'bg-gray-100 border-gray-200 text-gray-300 cursor-not-allowed'
                "
              >
                {{ d }} jam
              </button>
            </div>
          </div>

          <p class="text-sm text-blue-900 mb-3 leading-relaxed">
            Total waktu: <strong>{{ pendingSlot.start }}</strong> –
            <strong>{{
              dayData.slots[
                dayData.slots.findIndex((s) => s.start === pendingSlot!.start) + pendingDuration - 1
              ]?.end
            }}</strong>
            ({{ pendingDuration }} jam)
          </p>

          <div class="flex flex-col sm:flex-row gap-2">
            <button
              @click="cancelSelection"
              class="flex-1 py-2.5 sm:py-2 rounded-lg text-sm font-medium border border-gray-300 text-gray-700 hover:bg-gray-50 active:bg-gray-100 transition-colors min-h-11 sm:min-h-0"
            >
              Ubah Pilihan
            </button>
            <button
              @click="confirmSelection"
              class="flex-1 py-2.5 sm:py-2 rounded-lg text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700 active:bg-blue-800 transition-colors min-h-11 sm:min-h-0"
            >
              Konfirmasi Jadwal
            </button>
          </div>
        </div>

        <div
          v-else-if="interactive && confirmedSlot"
          class="mt-4 p-3.5 sm:p-4 rounded-xl bg-green-50 border border-green-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2"
        >
          <div>
            <p class="text-xs text-green-700 font-medium">Jadwal terkonfirmasi</p>
            <p class="text-sm text-green-900 font-semibold">
              {{ confirmedSlot.start }} – {{ confirmedSlot.end }} ({{ confirmedSlot.durationHours }}
              jam)
            </p>
          </div>
          <button
            @click="changeSchedule"
            class="text-xs text-green-700 underline hover:text-green-900 self-start sm:self-auto"
          >
            Ganti jadwal
          </button>
        </div>
      </template>
    </div>
  </div>
</template>
