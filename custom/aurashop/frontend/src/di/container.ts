import { inject, type InjectionKey } from 'vue'
import { createCatalogUseCases, type CatalogUseCases } from '@/application/catalog'
import type { StorePort } from '@/application/ports'
import { CatalogHttpAdapter } from '@/infrastructure/http/catalog-http-adapter'
import { HttpClient } from '@/infrastructure/http/http-client'
import type { RuntimeConfig } from '@/infrastructure/runtime-config'
import { HttpStoreAdapter, InjectedStoreAdapter } from '@/infrastructure/store-adapters'

export interface Container {
  config: RuntimeConfig
  catalog: CatalogUseCases
  store: StorePort
}

export const containerKey: InjectionKey<Container> = Symbol('aurashop.container')

/** Composition root of the SPA: the only place that picks adapters for each port. */
export function createContainer(config: RuntimeConfig): Container {
  const http = new HttpClient(config.apiBase)
  return {
    config,
    catalog: createCatalogUseCases(new CatalogHttpAdapter(http)),
    store: config.store ? new InjectedStoreAdapter(config.store) : new HttpStoreAdapter(http),
  }
}

export function useContainer(): Container {
  const container = inject(containerKey)
  if (!container) throw new Error('AuraShop container not provided')
  return container
}
