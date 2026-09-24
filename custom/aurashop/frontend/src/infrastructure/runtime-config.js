/**
 * @typedef {object} RuntimeConfig
 * @property {string} apiBase
 * @property {string} routerBase
 * @property {import('../domain/store.js').StoreConfig | null} store
 */

/**
 * Reads the config public/AuraShop/index.php renders into window.__AURASHOP__.
 * @returns {RuntimeConfig}
 */
export function readRuntimeConfig() {
  const injected = window.__AURASHOP__ ?? {}
  return {
    apiBase: injected.apiBase ?? '/custom/aurashop/api/index.php',
    routerBase: injected.routerBase ?? '/public/AuraShop/index.php',
    store: injected.store ?? null,
  }
}
