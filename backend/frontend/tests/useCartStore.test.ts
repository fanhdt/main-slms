import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'

// Sesuaikan path import ini dengan lokasi asli store di project kamu:
// frontend/src/features/booking/stores/useCartStore.ts
import { useCartStore } from '@/features/booking/stores/useCartStore'

const SLUG = 'lab-fotografi-a'

const sampleAsset = {
  uuid: 'asset-001',
  name: 'Canon EOS R5',
  brand: 'Canon',
  rental_price: '150000',
  max_quantity: 3,
}

describe('useCartStore', () => {
  beforeEach(() => {
    // PENTING: store ini persist ke localStorage (key 'slms_rental_cart').
    // Reset Pinia SAJA tidak cukup — localStorage tetap kebawa antar test
    // dan bikin addItem() no-op karena isInCart() sudah true dari data lama.
    // Makanya localStorage wajib di-clear juga di sini.
    localStorage.clear()
    setActivePinia(createPinia())
  })

  it('keranjang kosong di awal untuk slug manapun', () => {
    const cart = useCartStore()
    expect(cart.getItems(SLUG)).toEqual([])
    expect(cart.itemCount(SLUG)).toBe(0)
    expect(cart.totalQuantity(SLUG)).toBe(0)
  })

  it('menambahkan item baru ke keranjang dengan quantity default 1', () => {
    const cart = useCartStore()
    cart.addItem(SLUG, sampleAsset)

    expect(cart.itemCount(SLUG)).toBe(1)
    expect(cart.isInCart(SLUG, sampleAsset.uuid)).toBe(true)
    expect(cart.getQuantity(SLUG, sampleAsset.uuid)).toBe(1)
  })

  it('tidak menduplikasi item yang sama saat ditambahkan dua kali', () => {
    const cart = useCartStore()
    cart.addItem(SLUG, sampleAsset)
    cart.addItem(SLUG, sampleAsset)

    expect(cart.itemCount(SLUG)).toBe(1)
  })

  it('menambah quantity dengan incrementQuantity, dibatasi max_quantity', () => {
    const cart = useCartStore()
    cart.addItem(SLUG, sampleAsset) // qty = 1

    cart.incrementQuantity(SLUG, sampleAsset.uuid) // qty = 2
    cart.incrementQuantity(SLUG, sampleAsset.uuid) // qty = 3 (= max_quantity)
    cart.incrementQuantity(SLUG, sampleAsset.uuid) // seharusnya tetap 3, tidak melebihi stok

    expect(cart.getQuantity(SLUG, sampleAsset.uuid)).toBe(3)
  })

  it('mengurangi quantity dengan decrementQuantity, minimal 1', () => {
    const cart = useCartStore()
    cart.addItem(SLUG, sampleAsset)
    cart.incrementQuantity(SLUG, sampleAsset.uuid) // qty = 2

    cart.decrementQuantity(SLUG, sampleAsset.uuid) // qty = 1
    expect(cart.getQuantity(SLUG, sampleAsset.uuid)).toBe(1)
  })

  it('menghapus item dari keranjang dengan removeItem', () => {
    const cart = useCartStore()
    cart.addItem(SLUG, sampleAsset)
    cart.removeItem(SLUG, sampleAsset.uuid)

    expect(cart.isInCart(SLUG, sampleAsset.uuid)).toBe(false)
    expect(cart.itemCount(SLUG)).toBe(0)
  })

  it('menghitung totalQuantity dari beberapa jenis alat sekaligus', () => {
    const cart = useCartStore()
    cart.addItem(SLUG, sampleAsset) // qty 1
    cart.incrementQuantity(SLUG, sampleAsset.uuid) // qty 2

    cart.addItem(SLUG, {
      uuid: 'asset-002',
      name: 'Tripod Manfrotto',
      brand: 'Manfrotto',
      rental_price: '30000',
      max_quantity: 5,
    }) // qty 1

    expect(cart.itemCount(SLUG)).toBe(2) // 2 jenis alat
    expect(cart.totalQuantity(SLUG)).toBe(3) // 2 + 1 unit
  })

  it('mengosongkan keranjang dengan clearCart tanpa mempengaruhi lab/slug lain', () => {
    const cart = useCartStore()
    const otherSlug = 'lab-audio-b'

    cart.addItem(SLUG, sampleAsset)
    cart.addItem(otherSlug, sampleAsset)

    cart.clearCart(SLUG)

    expect(cart.getItems(SLUG)).toEqual([])
    expect(cart.getItems(otherSlug)).toHaveLength(1)
  })

  it('keranjang antar lab (slug) terpisah satu sama lain', () => {
    const cart = useCartStore()
    cart.addItem('lab-a', sampleAsset)

    expect(cart.itemCount('lab-a')).toBe(1)
    expect(cart.itemCount('lab-b')).toBe(0)
  })

  it('addItem() tidak mereset quantity kalau item sudah ada (baik di state maupun localStorage)', () => {
    const cart = useCartStore()
    cart.addItem(SLUG, sampleAsset)
    cart.incrementQuantity(SLUG, sampleAsset.uuid) // qty = 2

    // addItem dipanggil lagi dengan item yang sama (skenario umum: user
    // klik "Tambah ke Keranjang" dua kali dari halaman katalog)
    cart.addItem(SLUG, sampleAsset)

    // Harus tetap 2, bukan reset ke 1 — karena isInCart() bikin addItem no-op
    expect(cart.getQuantity(SLUG, sampleAsset.uuid)).toBe(2)
  })

  it('setQuantity mengunci nilai antara 1 dan max_quantity (tidak boleh 0 atau melebihi stok)', () => {
    const cart = useCartStore()
    cart.addItem(SLUG, sampleAsset) // max_quantity = 3

    cart.setQuantity(SLUG, sampleAsset.uuid, 0)
    expect(cart.getQuantity(SLUG, sampleAsset.uuid)).toBe(1) // dibatasi minimal 1

    cart.setQuantity(SLUG, sampleAsset.uuid, 99)
    expect(cart.getQuantity(SLUG, sampleAsset.uuid)).toBe(3) // dibatasi max_quantity
  })

  it('data cart benar-benar tersimpan ke localStorage (bukan cuma di memory)', async () => {
    const cart = useCartStore()
    cart.addItem(SLUG, sampleAsset)

    // watch({ deep: true }) di store berjalan async (microtask), jadi perlu
    // ditunggu sebelum mengecek localStorage
    await new Promise((resolve) => setTimeout(resolve, 0))

    const saved = JSON.parse(localStorage.getItem('slms_rental_cart') ?? '{}')
    expect(saved[SLUG]).toHaveLength(1)
    expect(saved[SLUG][0].uuid).toBe(sampleAsset.uuid)
  })
})
