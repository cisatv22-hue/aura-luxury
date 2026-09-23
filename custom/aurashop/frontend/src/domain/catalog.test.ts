import { describe, expect, it } from 'vitest'
import {
  availabilityLabel,
  findVariant,
  hasPriceRange,
  isOptionAvailable,
  rootCategories,
  rootOf,
  type Category,
  type ProductDetail,
} from './catalog'

const mxn = (pesos: number) => ({ amount: pesos * 100, currency: 'MXN' })

const hoodie: ProductDetail = {
  ref: 'HOOD',
  label: 'Sudadera',
  summary: '',
  descriptionHtml: '',
  price: mxn(1450),
  priceFrom: mxn(1450),
  images: [],
  categories: [],
  availability: 'in_stock',
  attributes: [
    { code: 'TALLA', label: 'Talla', values: ['S', 'M', 'L'] },
    { code: 'COLOR', label: 'Color', values: ['Negro', 'Crema'] },
  ],
  variants: [
    { ref: 'HOOD-S-N', options: { TALLA: 'S', COLOR: 'Negro' }, price: mxn(1450), availability: 'in_stock' },
    { ref: 'HOOD-M-N', options: { TALLA: 'M', COLOR: 'Negro' }, price: mxn(1450), availability: 'out_of_stock' },
    { ref: 'HOOD-M-C', options: { TALLA: 'M', COLOR: 'Crema' }, price: mxn(1550), availability: 'low_stock' },
  ],
}

describe('findVariant', () => {
  it('needs every attribute selected', () => {
    expect(findVariant(hoodie, { TALLA: 'M' })).toBeUndefined()
  })

  it('returns the matching variant', () => {
    expect(findVariant(hoodie, { TALLA: 'M', COLOR: 'Crema' })?.ref).toBe('HOOD-M-C')
  })

  it('returns undefined for combinations that do not exist', () => {
    expect(findVariant(hoodie, { TALLA: 'L', COLOR: 'Negro' })).toBeUndefined()
  })
})

describe('isOptionAvailable', () => {
  it('is true when some purchasable variant has the value', () => {
    expect(isOptionAvailable(hoodie, {}, 'TALLA', 'M')).toBe(true)
  })

  it('respects the other selected attributes', () => {
    expect(isOptionAvailable(hoodie, { COLOR: 'Negro' }, 'TALLA', 'M')).toBe(false)
    expect(isOptionAvailable(hoodie, { COLOR: 'Crema' }, 'TALLA', 'M')).toBe(true)
  })

  it('is false for values without variants', () => {
    expect(isOptionAvailable(hoodie, {}, 'TALLA', 'L')).toBe(false)
  })

  it('ignores the attribute being changed', () => {
    expect(isOptionAvailable(hoodie, { TALLA: 'S', COLOR: 'Crema' }, 'COLOR', 'Negro')).toBe(true)
  })
})

describe('catalog helpers', () => {
  it('detects price ranges across variants', () => {
    expect(hasPriceRange(hoodie)).toBe(true)
  })

  it('labels availability for customers', () => {
    expect(availabilityLabel('low_stock')).toBe('Últimas piezas')
  })

  it('finds roots and the root of a nested category', () => {
    const categories: Category[] = [
      { id: 1, label: 'Plata', parentId: null, description: '' },
      { id: 2, label: 'Cadenas', parentId: 1, description: '' },
      { id: 3, label: 'Cubanas', parentId: 2, description: '' },
      { id: 4, label: 'Huérfana', parentId: 99, description: '' },
    ]
    expect(rootCategories(categories).map((c) => c.id)).toEqual([1, 4])
    expect(rootOf(categories, 3)?.id).toBe(1)
  })
})
