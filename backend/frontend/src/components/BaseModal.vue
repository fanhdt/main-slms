<script setup lang="ts">
defineProps<{
  show: boolean
  title: string
  size?: 'sm' | 'md' | 'lg'
}>()

defineEmits<{
  close: []
}>()
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50" @click="$emit('close')" />

        <!-- Modal -->
        <div
          class="relative bg-white shadow-xl w-full flex flex-col rounded-t-2xl sm:rounded-2xl max-h-[92vh] sm:max-h-[85vh]"
          :class="{
            'sm:max-w-sm': size === 'sm',
            'sm:max-w-lg': size === 'md' || !size,
            'sm:max-w-2xl': size === 'lg',
          }"
        >
          <!-- Header -->
          <div
            class="flex items-center justify-between gap-3 px-4 py-3 border-b border-gray-200 sm:px-6 sm:py-4"
          >
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 truncate">
              {{ title }}
            </h3>
            <button
              @click="$emit('close')"
              class="shrink-0 text-gray-400 hover:text-gray-600 transition-colors p-1 -mr-1"
            >
              ✕
            </button>
          </div>

          <!-- Content -->
          <div class="px-4 py-4 sm:px-6 sm:py-5 overflow-y-auto flex-1">
            <slot />
          </div>

          <!-- Footer -->
          <div
            v-if="$slots.footer"
            class="px-4 py-3 sm:px-6 sm:py-4 border-t border-gray-200 bg-gray-50 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end sm:gap-3"
          >
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
