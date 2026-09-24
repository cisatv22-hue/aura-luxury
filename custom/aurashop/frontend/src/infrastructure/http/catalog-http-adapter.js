/**
 * Adapter for the AuraShop REST API v1 (custom/aurashop/api).
 * @implements {import('../../application/ports.js').CatalogPort}
 */
export class CatalogHttpAdapter {
  /** @param {import('./http-client.js').HttpClient} http */
  constructor(http) {
    this.http = http
  }

  /** @param {import('../../domain/catalog.js').ProductQuery} query @param {AbortSignal} [signal] */
  listProducts(query, signal) {
    const { category, q, sort, page, perPage } = query
    return this.http.get('/v1/catalog/products', { category, q, sort, page, perPage }, signal)
  }

  /** @param {string} ref @param {AbortSignal} [signal] */
  getProduct(ref, signal) {
    return this.http.get(`/v1/catalog/products/${encodeURIComponent(ref)}`, {}, signal)
  }

  async listCategories() {
    const body = await this.http.get('/v1/catalog/categories')
    return body.items
  }
}
