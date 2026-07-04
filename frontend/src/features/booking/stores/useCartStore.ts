import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'

export interface CartItem {
  uuid: string
  name: string
  brand: string | null
  rental_price: string
}

const STORAGE_KEY = 'slms_rental_cart'

function loadFromStorage(): Record<string, CartItem[]> {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '{}')
  } catch {
    return {}
  }
}

export const useCartStore = defineStore('cart', () => {
  // Cart terpisah per lab (key = slug lab), karena aset tiap lab beda.
  const cartByLab = ref<Record<string, CartItem[]>>(loadFromStorage())

  watch(
    cartByLab,
    (value) => {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(value))
    },
    { deep: true },
  )

  function getItems(slug: string): CartItem[] {
    return cartByLab.value[slug] ?? []
  }

  function itemCount(slug: string): number {
    return getItems(slug).length
  }

  function isInCart(slug: string, uuid: string): boolean {
    return getItems(slug).some((item) => item.uuid === uuid)
  }

  function addItem(slug: string, item: CartItem) {
    if (!cartByLab.value[slug]) cartByLab.value[slug] = []
    if (isInCart(slug, item.uuid)) return
    cartByLab.value[slug].push(item)
  }

  function removeItem(slug: string, uuid: string) {
    if (!cartByLab.value[slug]) return
    cartByLab.value[slug] = cartByLab.value[slug].filter((item) => item.uuid !== uuid)
  }

  function clearCart(slug: string) {
    cartByLab.value[slug] = []
  }

  return { cartByLab, getItems, itemCount, isInCart, addItem, removeItem, clearCart }
})
