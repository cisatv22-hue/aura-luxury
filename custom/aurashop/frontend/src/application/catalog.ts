import type { Category, ProductDetail, ProductPage, ProductQuery } from '@/domain/catalog'
import type { CatalogPort } from './ports'

export const DEFAULT_PER_PAGE = 24
const MAX_PER_PAGE = 48

export interface CatalogUseCases {
  browse(query: ProductQuery, signal?: AbortSignal): Promise<ProductPage>
  viewProduct(ref: string, signal?: AbortSignal): Promise<ProductDetail>
  categories(): Promise<Category[]>
}

export function normalizeQuery(query: ProductQuery): ProductQuery {
  const q = query.q?.trim()
  return {
    category: query.category && query.category > 0 ? query.category : null,
    q: q ? q.slice(0, 100) : undefined,
    sort: query.sort ?? 'newest',
    page: Math.max(1, Math.floor(query.page ?? 1)),
    perPage: Math.min(MAX_PER_PAGE, Math.max(1, Math.floor(query.perPage ?? DEFAULT_PER_PAGE))),
  }
}

export function createCatalogUseCases(port: CatalogPort): CatalogUseCases {
  let categories: Promise<Category[]> | null = null

  return {
    browse: (query, signal) => port.listProducts(normalizeQuery(query), signal),
    viewProduct: (ref, signal) => port.getProduct(ref.trim(), signal),
    categories: () => {
      categories ??= port.listCategories().catch((error: unknown) => {
        categories = null
        throw error
      })
      return categories
    },
  }
}
