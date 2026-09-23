import type { Money } from './money'

export type Availability = 'in_stock' | 'low_stock' | 'out_of_stock'
export type ProductSort = 'newest' | 'price_asc' | 'price_desc' | 'name'

export interface ProductImage {
  mini: string
  card: string
  full: string
}

export interface CategoryRef {
  id: number
  label: string
}

export interface Category extends CategoryRef {
  parentId: number | null
  description: string
}

export interface ProductSummary {
  ref: string
  label: string
  summary: string
  price: Money
  image: ProductImage | null
  categories: CategoryRef[]
  availability: Availability
  hasVariants: boolean
}

export interface VariantAttribute {
  code: string
  label: string
  values: string[]
}

export interface Variant {
  ref: string
  options: Record<string, string>
  price: Money
  availability: Availability
}

export interface ProductDetail {
  ref: string
  label: string
  summary: string
  descriptionHtml: string
  price: Money
  priceFrom: Money
  images: ProductImage[]
  categories: CategoryRef[]
  availability: Availability
  attributes: VariantAttribute[]
  variants: Variant[]
}

export interface Pagination {
  page: number
  perPage: number
  total: number
  totalPages: number
}

export interface ProductPage {
  items: ProductSummary[]
  pagination: Pagination
}

export interface ProductQuery {
  category?: number | null
  q?: string
  sort?: ProductSort
  page?: number
  perPage?: number
}

export type VariantSelection = Record<string, string>

export const SORT_OPTIONS: ReadonlyArray<{ value: ProductSort; label: string }> = [
  { value: 'newest', label: 'Novedades' },
  { value: 'price_asc', label: 'Precio: menor a mayor' },
  { value: 'price_desc', label: 'Precio: mayor a menor' },
  { value: 'name', label: 'Nombre' },
]

export function isSort(value: unknown): value is ProductSort {
  return SORT_OPTIONS.some((o) => o.value === value)
}

export function isPurchasable(availability: Availability): boolean {
  return availability !== 'out_of_stock'
}

export function availabilityLabel(availability: Availability): string {
  switch (availability) {
    case 'in_stock':
      return 'Disponible'
    case 'low_stock':
      return 'Últimas piezas'
    case 'out_of_stock':
      return 'Agotado'
  }
}

/** The variant matching every attribute of the product, or undefined while the selection is incomplete. */
export function findVariant(product: ProductDetail, selection: VariantSelection): Variant | undefined {
  if (product.attributes.some((a) => !selection[a.code])) return undefined
  return product.variants.find((v) => product.attributes.every((a) => v.options[a.code] === selection[a.code]))
}

/**
 * Whether choosing `value` for `code` can still lead to a purchasable variant,
 * given the other attributes already selected.
 */
export function isOptionAvailable(
  product: ProductDetail,
  selection: VariantSelection,
  code: string,
  value: string,
): boolean {
  return product.variants.some(
    (v) =>
      v.options[code] === value &&
      isPurchasable(v.availability) &&
      Object.entries(selection).every(([c, selected]) => c === code || !selected || v.options[c] === selected),
  )
}

export function hasPriceRange(product: ProductDetail): boolean {
  const amounts = new Set(product.variants.map((v) => v.price.amount))
  return amounts.size > 1
}

export function rootCategories(categories: Category[]): Category[] {
  return categories.filter((c) => c.parentId === null || !categories.some((p) => p.id === c.parentId))
}

export function childCategories(categories: Category[], parentId: number): Category[] {
  return categories.filter((c) => c.parentId === parentId)
}

/** The root ancestor of a category, used to keep the parent chip active while a child is selected. */
export function rootOf(categories: Category[], id: number): Category | undefined {
  const byId = new Map(categories.map((c) => [c.id, c]))
  let current = byId.get(id)
  const seen = new Set<number>()
  while (current && current.parentId !== null && byId.has(current.parentId) && !seen.has(current.id)) {
    seen.add(current.id)
    current = byId.get(current.parentId)
  }
  return current
}
