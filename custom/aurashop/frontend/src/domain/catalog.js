/**
 * Catalog domain: plain data (same shape as the API v1 contract) and pure rules. No Vue, no fetch.
 *
 * @typedef {'in_stock' | 'low_stock' | 'out_of_stock'} Availability
 * @typedef {'newest' | 'price_asc' | 'price_desc' | 'name'} ProductSort
 * @typedef {{ mini: string, card: string, full: string }} ProductImage
 * @typedef {{ id: number, label: string }} CategoryRef
 * @typedef {{ id: number, label: string, parentId: number | null, description: string }} Category
 * @typedef {{ code: string, label: string, values: string[] }} VariantAttribute
 * @typedef {{ ref: string, options: Record<string, string>, price: import('./money.js').Money, availability: Availability }} Variant
 * @typedef {Record<string, string>} VariantSelection
 *
 * @typedef {object} ProductSummary
 * @property {string} ref
 * @property {string} label
 * @property {string} summary
 * @property {import('./money.js').Money} price
 * @property {ProductImage | null} image
 * @property {CategoryRef[]} categories
 * @property {Availability} availability
 * @property {boolean} hasVariants
 *
 * @typedef {object} ProductDetail
 * @property {string} ref
 * @property {string} label
 * @property {string} summary
 * @property {string} descriptionHtml sanitized server side
 * @property {import('./money.js').Money} price
 * @property {import('./money.js').Money} priceFrom
 * @property {ProductImage[]} images
 * @property {CategoryRef[]} categories
 * @property {Availability} availability
 * @property {VariantAttribute[]} attributes
 * @property {Variant[]} variants
 *
 * @typedef {{ page: number, perPage: number, total: number, totalPages: number }} Pagination
 * @typedef {{ items: ProductSummary[], pagination: Pagination }} ProductPage
 * @typedef {{ category?: number | null, q?: string, sort?: ProductSort, page?: number, perPage?: number }} ProductQuery
 */

/** @type {ReadonlyArray<{ value: ProductSort, label: string }>} */
export const SORT_OPTIONS = Object.freeze([
  { value: 'newest', label: 'Novedades' },
  { value: 'price_asc', label: 'Precio: menor a mayor' },
  { value: 'price_desc', label: 'Precio: mayor a menor' },
  { value: 'name', label: 'Nombre' },
])

/** @param {unknown} value @returns {value is ProductSort} */
export function isSort(value) {
  return SORT_OPTIONS.some((o) => o.value === value)
}

/** @param {Availability} availability */
export function isPurchasable(availability) {
  return availability !== 'out_of_stock'
}

/** @param {Availability} availability */
export function availabilityLabel(availability) {
  return { in_stock: 'Disponible', low_stock: 'Últimas piezas', out_of_stock: 'Agotado' }[availability]
}

/**
 * The variant matching every attribute of the product, or undefined while the selection is incomplete.
 * @param {ProductDetail} product
 * @param {VariantSelection} selection
 */
export function findVariant(product, selection) {
  if (product.attributes.some((a) => !selection[a.code])) return undefined
  return product.variants.find((v) => product.attributes.every((a) => v.options[a.code] === selection[a.code]))
}

/**
 * Whether choosing `value` for `code` can still lead to a purchasable variant, given the other selected attributes.
 * @param {ProductDetail} product
 * @param {VariantSelection} selection
 * @param {string} code
 * @param {string} value
 */
export function isOptionAvailable(product, selection, code, value) {
  return product.variants.some(
    (v) =>
      v.options[code] === value &&
      isPurchasable(v.availability) &&
      Object.entries(selection).every(([c, selected]) => c === code || !selected || v.options[c] === selected),
  )
}

/** @param {ProductDetail} product */
export function hasPriceRange(product) {
  return new Set(product.variants.map((v) => v.price.amount)).size > 1
}

/** @param {Category[]} categories */
export function rootCategories(categories) {
  return categories.filter((c) => c.parentId === null || !categories.some((p) => p.id === c.parentId))
}

/** @param {Category[]} categories @param {number} parentId */
export function childCategories(categories, parentId) {
  return categories.filter((c) => c.parentId === parentId)
}

/**
 * The root ancestor of a category, used to keep the parent chip active while a child is selected.
 * @param {Category[]} categories
 * @param {number} id
 */
export function rootOf(categories, id) {
  const byId = new Map(categories.map((c) => [c.id, c]))
  let current = byId.get(id)
  const seen = new Set()
  while (current && current.parentId !== null && byId.has(current.parentId) && !seen.has(current.id)) {
    seen.add(current.id)
    current = byId.get(current.parentId)
  }
  return current
}
