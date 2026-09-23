import type { StoreConfig } from '@/domain/store'

export interface RuntimeConfig {
  apiBase: string
  routerBase: string
  store: StoreConfig | null
}

declare global {
  interface Window {
    /** Injected by public/AuraShop/index.php. Absent when running the Vite dev server. */
    __AURASHOP__?: Partial<RuntimeConfig>
  }
}

export function readRuntimeConfig(): RuntimeConfig {
  const injected = window.__AURASHOP__ ?? {}
  return {
    apiBase: injected.apiBase ?? '/custom/aurashop/api/index.php',
    routerBase: injected.routerBase ?? '/',
    store: injected.store ?? null,
  }
}
