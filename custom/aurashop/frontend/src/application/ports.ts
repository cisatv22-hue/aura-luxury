import type { Category, ProductDetail, ProductPage, ProductQuery } from '@/domain/catalog'
import type { StoreConfig } from '@/domain/store'

/** Implemented by infrastructure adapters. UI code never talks to fetch directly. */
export interface CatalogPort {
  listProducts(query: ProductQuery, signal?: AbortSignal): Promise<ProductPage>
  getProduct(ref: string, signal?: AbortSignal): Promise<ProductDetail>
  listCategories(): Promise<Category[]>
}

export interface StorePort {
  getConfig(): Promise<StoreConfig>
}
