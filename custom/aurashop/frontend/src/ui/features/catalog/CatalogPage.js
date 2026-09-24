import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { isSort, SORT_OPTIONS } from '../../../domain/catalog.js'
import { useShopStore } from '../../../stores/shop.js'
import { usePageTitle } from '../../composables/usePageTitle.js'
import { useProductList } from '../../composables/useProductList.js'
import SkeletonGrid from '../../design-system/SkeletonGrid.js'
import StateMessage from '../../design-system/StateMessage.js'
import CategoryFilter from './CategoryFilter.js'
import PaginationNav from './PaginationNav.js'
import ProductGrid from './ProductGrid.js'

/** @param {unknown} value */
function positiveInt(value) {
  const n = typeof value === 'string' ? Number.parseInt(value, 10) : NaN
  return Number.isFinite(n) && n > 0 ? n : null
}

export default {
  name: 'CatalogPage',
  components: { SkeletonGrid, StateMessage, CategoryFilter, PaginationNav, ProductGrid },
  setup() {
    const route = useRoute()
    const router = useRouter()
    const shop = useShopStore()

    // The URL is the single source of truth for filters: shareable links and a working back button.
    const query = computed(() => ({
      category: positiveInt(route.query.categoria),
      q: typeof route.query.q === 'string' ? route.query.q : undefined,
      sort: isSort(route.query.orden) ? route.query.orden : 'newest',
      page: positiveInt(route.query.pagina) ?? 1,
    }))

    const { page, loading, error, reload } = useProductList(query)

    const selectedCategory = computed(() => shop.categoryById(query.value.category))
    const heading = computed(() => {
      if (query.value.q) return `Resultados para “${query.value.q}”`
      return selectedCategory.value?.label ?? 'Catálogo completo'
    })
    usePageTitle(heading)

    /** @param {{ categoria?: number | null, q?: string | null, orden?: string, pagina?: number }} changes */
    function update(changes) {
      const next = { ...route.query }
      for (const [key, value] of Object.entries(changes)) {
        const isDefault = (key === 'orden' && value === 'newest') || (key === 'pagina' && value === 1)
        if (value === null || value === undefined || value === '' || isDefault) delete next[key]
        else next[key] = String(value)
      }
      if (!('pagina' in changes)) delete next.pagina
      void router.push({ name: 'catalog', query: next })
    }

    /** @param {Event} event */
    function onSort(event) {
      const value = event.target.value
      if (isSort(value)) update({ orden: value })
    }

    return { shop, query, page, loading, error, reload, selectedCategory, heading, update, onSort, SORT_OPTIONS }
  },
  template: `
    <section class="mx-auto w-full max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
      <div class="mb-10 flex flex-col gap-6 border-b border-zinc-800 pb-8">
        <div>
          <span class="eyebrow">Colección disponible</span>
          <h1 class="mt-1 font-display text-3xl font-black uppercase tracking-tight text-white sm:text-4xl">{{ heading }}</h1>
          <p v-if="selectedCategory?.description && !query.q" class="mt-2 max-w-2xl text-sm text-zinc-400">{{ selectedCategory.description }}</p>
        </div>
        <CategoryFilter :categories="shop.categories" :roots="shop.roots" :selected="query.category ?? null" @select="(id) => update({ categoria: id })" />
      </div>

      <div class="mb-8 flex flex-wrap items-center justify-between gap-4 text-xs text-zinc-400">
        <p aria-live="polite">
          <template v-if="page && !loading">
            <span class="font-black text-white">{{ page.pagination.total }}</span>
            {{ page.pagination.total === 1 ? 'artículo' : 'artículos' }}
          </template>
        </p>
        <div class="flex items-center gap-3">
          <button v-if="query.q" type="button" class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 hover:text-white" @click="update({ q: null })">
            <i class="fa-solid fa-xmark mr-1" aria-hidden="true"></i>Quitar búsqueda
          </button>
          <label for="catalog-sort" class="text-[11px] font-bold uppercase tracking-wider text-zinc-500">Ordenar</label>
          <select
            id="catalog-sort"
            :value="query.sort"
            class="rounded border border-zinc-800 bg-zinc-900 px-3 py-2 text-xs font-semibold text-zinc-200 focus:border-zinc-500 focus:outline-none"
            @change="onSort"
          >
            <option v-for="option in SORT_OPTIONS" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
        </div>
      </div>

      <SkeletonGrid v-if="loading && !page" />
      <StateMessage v-else-if="error" icon="fa-plug-circle-exclamation" title="No pudimos cargar el catálogo" :text="error">
        <button type="button" class="btn-outline" @click="reload">Intentar de nuevo</button>
      </StateMessage>
      <StateMessage
        v-else-if="page && page.items.length === 0"
        icon="fa-box-open"
        title="No encontramos productos"
        :text="query.q ? 'Prueba con otra palabra o revisa todo el catálogo.' : 'Pronto agregaremos nuevas piezas a esta colección.'"
      >
        <button type="button" class="btn-outline" @click="update({ q: null, categoria: null })">Ver todo el catálogo</button>
      </StateMessage>
      <div v-else-if="page" :class="{ 'opacity-60 transition-opacity': loading }">
        <ProductGrid :products="page.items" />
        <PaginationNav :page="page.pagination.page" :total-pages="page.pagination.totalPages" @change="(p) => update({ pagina: p })" />
      </div>
    </section>
  `,
}
