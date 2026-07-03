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
      <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50" @click="$emit('close')" />

        <!-- Modal -->
        <div
          class="relative bg-white rounded-2xl shadow-xl w-full overflow-hidden"
          :class="{
            'max-w-sm': size === 'sm',
            'max-w-lg': size === 'md' || !size,
            'max-w-2xl': size === 'lg',
          }"
        >
          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
            <button
              @click="$emit('close')"
              class="text-gray-400 hover:text-gray-600 transition-colors"
            >
              ✕
            </button>
          </div>

          <!-- Content -->
          <div class="px-6 py-5 max-h-[70vh] overflow-y-auto">
            <slot />
          </div>

          <!-- Footer -->
          <div
            v-if="$slots.footer"
            class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3"
          >
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
