import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export interface CartItem {
  uuid: string
  name: string
  brand: string | null
  rental_price: string
  quantity: number
  max_quantity: number
}

export type CartItemInput = Omit<CartItem, 'quantity'> & { quantity?: number }

const STORAGE_KEY = 'slms_rental_cart'

function loadFromStorage(): Record<string, CartItem[]> {
  try {
    const parsed = JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '{}')
    // Migrasi data lama + pastikan quantity/max_quantity selalu number valid
    for (const slug in parsed) {
      parsed[slug] = parsed[slug].map((item: any) => {
        const maxQty = Number(item.max_quantity)
        const qty = Number(item.quantity)
        return {
          ...item,
          max_quantity: Number.isFinite(maxQty) && maxQty > 0 ? maxQty : 1,
          quantity: Number.isFinite(qty) && qty > 0 ? qty : 1,
        }
      })
    }
    return parsed
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

  function totalQuantity(slug: string): number {
    return getItems(slug).reduce((sum, item) => sum + item.quantity, 0)
  }

  function isInCart(slug: string, uuid: string): boolean {
    return getItems(slug).some((item) => item.uuid === uuid)
  }

  function getQuantity(slug: string, uuid: string): number {
    return getItems(slug).find((item) => item.uuid === uuid)?.quantity ?? 0
  }

  function addItem(slug: string, item: CartItemInput) {
    if (!cartByLab.value[slug]) cartByLab.value[slug] = []
    if (isInCart(slug, item.uuid)) return

    const maxQty = Number(item.max_quantity)
    const safeMaxQty = Number.isFinite(maxQty) && maxQty > 0 ? maxQty : 1
    const qty = Number(item.quantity)
    const safeQty = Number.isFinite(qty) && qty > 0 ? qty : 1

    cartByLab.value[slug].push({
      ...item,
      max_quantity: safeMaxQty,
      quantity: Math.min(safeQty, safeMaxQty),
    })
  }

  function setQuantity(slug: string, uuid: string, quantity: number) {
    const item = cartByLab.value[slug]?.find((i) => i.uuid === uuid)
    if (!item) return

    const safeQuantity = Number.isFinite(quantity) ? quantity : 1
    const clamped = Math.max(1, Math.min(safeQuantity, item.max_quantity))
    item.quantity = clamped
  }

  function incrementQuantity(slug: string, uuid: string) {
    const item = cartByLab.value[slug]?.find((i) => i.uuid === uuid)
    if (!item) return
    // Jangan increment kalau sudah mentok stok (misal stok cuma 1)
    if (item.quantity >= item.max_quantity) return
    setQuantity(slug, uuid, item.quantity + 1)
  }

  function decrementQuantity(slug: string, uuid: string) {
    const item = cartByLab.value[slug]?.find((i) => i.uuid === uuid)
    if (!item) return
    setQuantity(slug, uuid, item.quantity - 1)
  }

  function removeItem(slug: string, uuid: string) {
    if (!cartByLab.value[slug]) return
    cartByLab.value[slug] = cartByLab.value[slug].filter((item) => item.uuid !== uuid)
  }

  function clearCart(slug: string) {
    cartByLab.value[slug] = []
  }

  return {
    cartByLab,
    getItems,
    itemCount,
    totalQuantity,
    isInCart,
    getQuantity,
    addItem,
    setQuantity,
    incrementQuantity,
    decrementQuantity,
    removeItem,
    clearCart,
  }
})
