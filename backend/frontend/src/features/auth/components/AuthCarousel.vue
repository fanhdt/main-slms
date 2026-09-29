<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'

interface Slide {
  title: string
  description: string
}

const props = withDefaults(
  defineProps<{
    slides?: Slide[]
    /** durasi tiap slide (ms) */
    interval?: number
  }>(),
  {
    slides: () => [
      {
        title: 'Easy Laboratory Booking',
        description: 'Schedule lab, studio, and equipment usage in just a few clicks.',
      },
      {
        title: 'Asset Management',
        description: 'Track equipment availability — in use, available, or under maintenance.',
      },
      {
        title: 'Laboratory Services',
        description: 'Offer bundled services and packages tailored to each laboratory.',
      },
      {
        title: 'Multi Role Access',
        description: 'Manage multiple laboratories at once, each with its own branding.',
      },
    ],
    interval: 4500,
  },
)

const active = ref(0)
let timer: ReturnType<typeof setInterval> | null = null

function start() {
  stop()
  timer = setInterval(() => {
    active.value = (active.value + 1) % props.slides.length
  }, props.interval)
}

function stop() {
  if (timer) clearInterval(timer)
  timer = null
}

function goTo(index: number) {
  active.value = index
  start()
}

onMounted(start)
onBeforeUnmount(stop)
</script>

<template>
  <div class="w-full max-w-md">
    <!-- Slide text -->
    <div class="relative h-18 sm:h-16 mb-4">
      <Transition name="fade" mode="out-in">
        <div :key="active">
          <h3 class="text-white font-semibold text-base sm:text-lg">
            {{ slides[active]?.title }}
          </h3>
          <p class="text-white/70 text-sm mt-1 leading-relaxed">
            {{ slides[active]?.description }}
          </p>
        </div>
      </Transition>
    </div>

    <!-- Segmented progress indicator -->
    <div class="flex gap-1.5">
      <button
        v-for="(slide, index) in slides"
        :key="index"
        type="button"
        class="h-1 flex-1 rounded-full bg-white/20 overflow-hidden cursor-pointer"
        :aria-label="`Ke slide ${index + 1}`"
        @click="goTo(index)"
      >
        <span v-if="index < active" class="block h-full w-full bg-white rounded-full" />
        <span
          v-else-if="index === active"
          :key="`fill-${active}`"
          class="block h-full bg-white rounded-full progress-fill"
          :style="{ animationDuration: `${interval}ms` }"
        />
        <span v-else class="block h-full w-0 bg-white rounded-full" />
      </button>
    </div>
  </div>
</template>

<style scoped>
.progress-fill {
  width: 0%;
  animation-name: fill-bar;
  animation-timing-function: linear;
  animation-fill-mode: forwards;
}

@keyframes fill-bar {
  from {
    width: 0%;
  }
  to {
    width: 100%;
  }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 250ms ease-out;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
  .progress-fill {
    animation: none;
    width: 100%;
  }
  .fade-enter-active,
  .fade-leave-active {
    transition: none;
  }
}
</style>
