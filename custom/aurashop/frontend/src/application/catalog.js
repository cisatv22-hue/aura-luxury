export const DEFAULT_PER_PAGE = 24
const MAX_PER_PAGE = 48

/**
 * @param {import('../domain/catalog.js').ProductQuery} query
 * @returns {import('../domain/catalog.js').ProductQuery}
 */
export function normalizeQuery(query) {
  const q = query.q?.trim()
  return {
    category: query.category && query.category > 0 ? query.category : null,
    q: q ? q.slice(0, 100) : undefined,
    sort: query.sort ?? 'newest',
    page: Math.max(1, Math.floor(query.page ?? 1)),
    perPage: Math.min(MAX_PER_PAGE, Math.max(1, Math.floor(query.perPage ?? DEFAULT_PER_PAGE))),
  }
}

/**
 * @param {import('./ports.js').CatalogPort} port
 */
export function createCatalogUseCases(port) {
  /** @type {Promise<import('../domain/catalog.js').Category[]> | null} */
  let categories = null

  return {
    /** @param {import('../domain/catalog.js').ProductQuery} query @param {AbortSignal} [signal] */
    browse: (query, signal) => port.listProducts(normalizeQuery(query), signal),
    /** @param {string} ref @param {AbortSignal} [signal] */
    viewProduct: (ref, signal) => port.getProduct(ref.trim(), signal),
    categories: () => {
      categories ??= port.listCategories().catch((error) => {
        categories = null
        throw error
      })
      return categories
    },
  }
}
