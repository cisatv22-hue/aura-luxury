import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { useContainer } from '@/di/container'
import { rootCategories, type Category } from '@/domain/catalog'
import type { StoreConfig } from '@/domain/store'

/** Data every page needs: store settings and the category tree. Loaded once per visit. */
export const useShopStore = defineStore('shop', () => {
  const { catalog, store } = useContainer()

  const config = ref<StoreConfig | null>(null)
  const categories = ref<Category[]>([])
  const loaded = ref(false)

  const roots = computed(() => rootCategories(categories.value))
  const storeName = computed(() => config.value?.storeName ?? 'AURA LUXURY')

  async function load(): Promise<void> {
    if (loaded.value) return
    const [cfg, cats] = await Promise.allSettled([store.getConfig(), catalog.categories()])
    if (cfg.status === 'fulfilled') config.value = cfg.value
    if (cats.status === 'fulfilled') categories.value = cats.value
    loaded.value = true
  }

  function categoryById(id: number | null | undefined): Category | undefined {
    return id ? categories.value.find((c) => c.id === id) : undefined
  }

  return { config, categories, roots, storeName, loaded, load, categoryById }
})
