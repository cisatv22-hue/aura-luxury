<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter, type LocationQueryRaw } from 'vue-router'
import { isSort, SORT_OPTIONS, type ProductQuery, type ProductSort } from '@/domain/catalog'
import { useShopStore } from '@/stores/shop'
import { usePageTitle } from '@/ui/composables/usePageTitle'
import { useProductList } from '@/ui/composables/useProductList'
import SkeletonGrid from '@/ui/design-system/SkeletonGrid.vue'
import StateMessage from '@/ui/design-system/StateMessage.vue'
import CategoryFilter from './CategoryFilter.vue'
import PaginationNav from './PaginationNav.vue'
import ProductGrid from './ProductGrid.vue'

const route = useRoute()
const router = useRouter()
const shop = useShopStore()

function positiveInt(value: unknown): number | null {
  const n = typeof value === 'string' ? Number.parseInt(value, 10) : NaN
  return Number.isFinite(n) && n > 0 ? n : null
}

// The URL is the single source of truth for filters: shareable links and working back button.
const query = computed<ProductQuery>(() => ({
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

function update(changes: { categoria?: number | null; q?: string | null; orden?: ProductSort; pagina?: number }): void {
  const next: LocationQueryRaw = { ...route.query }
  for (const [key, value] of Object.entries(changes)) {
    if (value === null || value === undefined || value === '' || (key === 'orden' && value === 'newest') || (key === 'pagina' && value === 1)) {
      delete next[key]
    } else {
      next[key] = String(value)
    }
  }
  if (!('pagina' in changes)) delete next.pagina
  void router.push({ name: 'catalog', query: next })
}

function onSort(event: Event): void {
  const value = (event.target as HTMLSelectElement).value
  if (isSort(value)) update({ orden: value })
}
</script>

<template>
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
</template>
