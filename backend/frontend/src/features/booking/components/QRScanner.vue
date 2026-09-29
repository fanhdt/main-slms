<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Html5QrcodeScanner } from 'html5-qrcode'

const emit = defineEmits<{
  scanned: [code: string]
  error: [message: string]
}>()

const scannerRef = ref<Html5QrcodeScanner | null>(null)
const isScanning = ref(false)

onMounted(() => {
  scannerRef.value = new Html5QrcodeScanner(
    'qr-reader',
    {
      fps: 10,
      qrbox: { width: 250, height: 250 },
      aspectRatio: 1.0,
    },
    false,
  )

  scannerRef.value.render(
    (decodedText) => {
      emit('scanned', decodedText)
      isScanning.value = true
    },
    (error) => {
      // Abaikan error scanning biasa
    },
  )
})

onUnmounted(() => {
  if (scannerRef.value) {
    scannerRef.value.clear().catch(() => {})
  }
})
</script>

<template>
  <div class="space-y-4">
    <div id="qr-reader" class="w-full rounded-xl overflow-hidden border border-gray-200" />
    <p v-if="isScanning" class="text-center text-sm text-green-600 font-medium">
      ✓ QR Code terdeteksi!
    </p>
  </div>
</template>
