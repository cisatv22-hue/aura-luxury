/**
 * Ports implemented by infrastructure adapters. UI code never talks to fetch directly.
 *
 * @typedef {object} CatalogPort
 * @property {(query: import('../domain/catalog.js').ProductQuery, signal?: AbortSignal) => Promise<import('../domain/catalog.js').ProductPage>} listProducts
 * @property {(ref: string, signal?: AbortSignal) => Promise<import('../domain/catalog.js').ProductDetail>} getProduct
 * @property {() => Promise<import('../domain/catalog.js').Category[]>} listCategories
 *
 * @typedef {object} StorePort
 * @property {() => Promise<import('../domain/store.js').StoreConfig>} getConfig
 */

export {}
