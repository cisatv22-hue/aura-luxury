import { computed, inject, reactive } from 'vue'
import { rootCategories } from '../domain/catalog.js'

export const shopKey = Symbol('aurashop.shop')

/**
 * Data every page needs: store settings and the category tree, loaded once per visit.
 * @param {ReturnType<typeof import('../di/container.js').createContainer>} container
 */
export function createShopStore({ catalog, store }) {
  const shop = reactive({
    /** @type {import('../domain/store.js').StoreConfig | null} */
    config: null,
    /** @type {import('../domain/catalog.js').Category[]} */
    categories: [],
    loaded: false,
    roots: computed(() => rootCategories(shop.categories)),
    storeName: computed(() => shop.config?.storeName ?? 'AURA LUXURY'),

    async load() {
      if (shop.loaded) return
      const [cfg, cats] = await Promise.allSettled([store.getConfig(), catalog.categories()])
      if (cfg.status === 'fulfilled') shop.config = cfg.value
      if (cats.status === 'fulfilled') shop.categories = cats.value
      shop.loaded = true
    },

    /** @param {number | null | undefined} id */
    categoryById(id) {
      return id ? shop.categories.find((c) => c.id === id) : undefined
    },
  })
  return shop
}

/** @returns {ReturnType<typeof createShopStore>} */
export function useShopStore() {
  const shop = inject(shopKey)
  if (!shop) throw new Error('AuraShop store not provided')
  return shop
}
