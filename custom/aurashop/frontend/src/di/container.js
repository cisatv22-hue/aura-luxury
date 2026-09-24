import { inject } from 'vue'
import { createCatalogUseCases } from '../application/catalog.js'
import { CatalogHttpAdapter } from '../infrastructure/http/catalog-http-adapter.js'
import { HttpClient } from '../infrastructure/http/http-client.js'
import { HttpStoreAdapter, InjectedStoreAdapter } from '../infrastructure/store-adapters.js'

export const containerKey = Symbol('aurashop.container')

/**
 * Composition root of the SPA: the only place that picks adapters for each port.
 * @param {import('../infrastructure/runtime-config.js').RuntimeConfig} config
 */
export function createContainer(config) {
  const http = new HttpClient(config.apiBase)
  return {
    config,
    catalog: createCatalogUseCases(new CatalogHttpAdapter(http)),
    store: config.store ? new InjectedStoreAdapter(config.store) : new HttpStoreAdapter(http),
  }
}

/** @returns {ReturnType<typeof createContainer>} */
export function useContainer() {
  const container = inject(containerKey)
  if (!container) throw new Error('AuraShop container not provided')
  return container
}
