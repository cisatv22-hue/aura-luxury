import type { CatalogPort } from '@/application/ports'
import type { Category, ProductDetail, ProductPage, ProductQuery } from '@/domain/catalog'
import type { HttpClient } from './http-client'

/** Adapter for the AuraShop REST API v1 (custom/aurashop/api). */
export class CatalogHttpAdapter implements CatalogPort {
  constructor(private readonly http: HttpClient) {}

  listProducts(query: ProductQuery, signal?: AbortSignal): Promise<ProductPage> {
    return this.http.get<ProductPage>(
      '/v1/catalog/products',
      {
        category: query.category,
        q: query.q,
        sort: query.sort,
        page: query.page,
        perPage: query.perPage,
      },
      signal,
    )
  }

  getProduct(ref: string, signal?: AbortSignal): Promise<ProductDetail> {
    return this.http.get<ProductDetail>(`/v1/catalog/products/${encodeURIComponent(ref)}`, {}, signal)
  }

  async listCategories(): Promise<Category[]> {
    const body = await this.http.get<{ items: Category[] }>('/v1/catalog/categories')
    return body.items
  }
}
