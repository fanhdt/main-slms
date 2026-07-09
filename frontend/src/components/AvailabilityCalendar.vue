<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { availabilityApi, type DaySlot } from '@/features/booking/api/availabilityApi'
import { useAvailabilityColor } from '@/composables/useAvailabilityColor'

const props = withDefaults(
  defineProps<{
    slug: string
    interactive?: boolean
    minDate?: string
  }>(),
  { interactive: false },
)

const emit = defineEmits<{
  'confirm-slot': [payload: { date: string; start: string; end: string; durationHours: number }]
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
  selectedDate.value = minDateValue.value ?? null
})
</script>

<template>
  <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
      <button
        @click="prevMonth"
        class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center"
      >
        ‹
      </button>
      <p class="text-sm font-semibold text-gray-900 capitalize">{{ monthLabel }}</p>
      <button
        @click="nextMonth"
        class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center"
      >
        ›
      </button>
    </div>

    <div class="p-4">
      <div class="grid grid-cols-7 gap-1 text-center text-xs text-gray-400 mb-2">
        <span v-for="d in ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']" :key="d">{{ d }}</span>
      </div>

      <div v-if="monthLoading" class="py-8 text-center text-sm text-gray-400">
        Memuat kalender...
      </div>

      <div v-else class="grid grid-cols-7 gap-1">
        <button
          v-for="(cell, idx) in calendarDays"
          :key="idx"
          :disabled="!cell.date || isPast(cell.date)"
          @click="selectDate(cell.date)"
          class="aspect-square rounded-lg text-sm font-medium transition-colors flex items-center justify-center"
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

      <div class="flex items-center gap-4 mt-3 text-xs text-gray-500">
        <span class="flex items-center gap-1"
          ><span class="w-2.5 h-2.5 rounded-sm bg-green-50 border border-green-200" /> Kosong</span
        >
        <span class="flex items-center gap-1"
          ><span class="w-2.5 h-2.5 rounded-sm bg-yellow-50 border border-yellow-200" />
          Sebagian</span
        >
        <span class="flex items-center gap-1"
          ><span class="w-2.5 h-2.5 rounded-sm bg-red-50 border border-red-200" /> Penuh</span
        >
      </div>
    </div>

    <div v-if="selectedDate" class="border-t border-gray-100 p-4">
      <p class="text-sm font-semibold text-gray-900 mb-3">
        Jadwal
        {{
          new Date(selectedDate).toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
          })
        }}
        <span v-if="dayData" class="font-normal text-gray-400">
          ({{ dayData.operational_hours.open }}–{{ dayData.operational_hours.close }})
        </span>
      </p>

      <div v-if="dayLoading" class="py-4 text-center text-sm text-gray-400">Memuat jadwal...</div>

      <template v-else-if="dayData">
        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
          <button
            v-for="(slot, index) in dayData.slots"
            :key="slot.start"
            :disabled="!slot.available || !interactive"
            @click="pickSlot(slot)"
            class="py-2 px-2 rounded-lg text-xs font-medium border transition-colors"
            :class="slotClass(slot, index)"
          >
            {{ slot.start }}
          </button>
        </div>

        <div
          v-if="interactive && pendingSlot"
          class="mt-4 p-4 rounded-xl bg-blue-50 border border-blue-200"
        >
          <p class="text-sm text-blue-900 font-medium mb-3">
            Kamu memilih jam <strong>{{ pendingSlot.start }}</strong>
          </p>

          <div class="mb-3">
            <label class="text-xs font-medium text-blue-800 mb-1.5 block">Durasi (jam)</label>
            <div class="flex gap-2 flex-wrap">
              <button
                v-for="d in [1, 2, 3, 4]"
                :key="d"
                :disabled="!canExtendTo(d)"
                @click="setDuration(d)"
                class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors"
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

          <p class="text-sm text-blue-900 mb-3">
            Total waktu: <strong>{{ pendingSlot.start }}</strong> –
            <strong>{{
              dayData.slots[
                dayData.slots.findIndex((s) => s.start === pendingSlot!.start) + pendingDuration - 1
              ]?.end
            }}</strong>
            ({{ pendingDuration }} jam)
          </p>

          <div class="flex gap-2">
            <button
              @click="cancelSelection"
              class="flex-1 py-2 rounded-lg text-sm font-medium border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors"
            >
              Ubah Pilihan
            </button>
            <button
              @click="confirmSelection"
              class="flex-1 py-2 rounded-lg text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700 transition-colors"
            >
              Konfirmasi Jadwal
            </button>
          </div>
        </div>

        <div
          v-else-if="interactive && confirmedSlot"
          class="mt-4 p-4 rounded-xl bg-green-50 border border-green-200 flex items-center justify-between"
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
            class="text-xs text-green-700 underline hover:text-green-900"
          >
            Ganti jadwal
          </button>
        </div>
      </template>
    </div>
  </div>
</template>
