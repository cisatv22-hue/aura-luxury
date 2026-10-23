<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ProductCard from '@/components/store/ProductCard.vue';
import type { StoreProduct } from '@/components/store/ProductCard.vue';

type Filters = {
    q?: string;
    size?: string;
    color?: string;
    min?: number;
    max?: number;
    sort?: string;
    category?: string;
};
const props = defineProps<{
    products: {
        data: StoreProduct[];
        next_page_url: string | null;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    categories: Array<{ name: string; slug: string }>;
    filters: Filters;
    title: string;
    searchPage: boolean;
    canonicalUrl: string;
}>();
const page = usePage<{ seoDefaults: { description: string } }>();
const suggestions = ref<Array<{ name: string; url: string }>>([]);
const activeSuggestionIndex = ref(-1);
const displayedProducts = ref([...props.products.data]);
const loadingMore = ref(false);
let appendNextPage = false;
let suggestionRequest = 0;
const draft = ref({ ...props.filters });
watch(
    () => props.products,
    (products) => {
        displayedProducts.value = appendNextPage
            ? [...displayedProducts.value, ...products.data]
            : [...products.data];
        appendNextPage = false;
    },
);
watch(
    () => props.filters,
    (value) => {
        draft.value = { ...value };
    },
    { deep: true },
);
const activeFilterCount = computed(
    () =>
        ['size', 'color', 'min', 'max'].filter(
            (key) => draft.value[key as keyof Filters],
        ).length,
);

function applyFilters() {
    const { category, ...values } = draft.value;
    const query = Object.fromEntries(
        Object.entries(values).filter(
            ([, value]) => value !== undefined && value !== '',
        ),
    );
    router.get(
        category
            ? `/tienda/${category}`
            : props.searchPage
              ? '/buscar'
              : '/tienda',
        query,
        {
            preserveState: true,
            replace: true,
        },
    );
}

function loadMore() {
    const nextPageUrl = props.products.next_page_url;
    if (!nextPageUrl || loadingMore.value) return;

    appendNextPage = true;
    router.get(
        nextPageUrl,
        {},
        {
            only: ['products'],
            preserveScroll: true,
            preserveState: true,
            onStart: () => {
                loadingMore.value = true;
            },
            onError: () => {
                appendNextPage = false;
            },
            onCancel: () => {
                appendNextPage = false;
            },
            onFinish: () => {
                loadingMore.value = false;
            },
        },
    );
}

watch(
    () => draft.value.q,
    (value, _old, onCleanup) => {
        const request = ++suggestionRequest;
        suggestions.value = [];
        activeSuggestionIndex.value = -1;
        if (!value?.trim()) return;

        const timer = window.setTimeout(async () => {
            const response = await fetch(
                `/buscar/sugerencias?q=${encodeURIComponent(value.trim())}`,
                {
                    headers: { Accept: 'application/json' },
                },
            );
            if (!response.ok || request !== suggestionRequest) return;
            suggestions.value = await response.json();
        }, 180);
        onCleanup(() => window.clearTimeout(timer));
    },
);

function onSearchKeydown(event: KeyboardEvent) {
    if (event.key === 'Enter') {
        event.preventDefault();
        const activeSuggestion = suggestions.value[activeSuggestionIndex.value];
        if (activeSuggestion) {
            router.visit(activeSuggestion.url);
        } else {
            applyFilters();
        }
        return;
    }

    if (!suggestions.value.length) return;

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        activeSuggestionIndex.value =
            (activeSuggestionIndex.value + 1) % suggestions.value.length;
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        activeSuggestionIndex.value =
            activeSuggestionIndex.value <= 0
                ? suggestions.value.length - 1
                : activeSuggestionIndex.value - 1;
    } else if (event.key === 'Escape') {
        event.preventDefault();
        suggestions.value = [];
        activeSuggestionIndex.value = -1;
    }
}

function clearFilters() {
    draft.value = {
        q: props.filters.q,
        sort: 'recent',
        category: props.filters.category,
    };
    applyFilters();
}

function paginationVisibleLabel(
    link: { label: string; url: string | null; active: boolean },
    index: number,
    total: number,
) {
    if (index === 0) return 'Anterior';
    if (index === total - 1) return 'Siguiente';

    const text = link.label
        .replace(/<[^>]*>/g, ' ')
        .replace(/&laquo;|&#171;/gi, ' ')
        .replace(/&raquo;|&#187;/gi, ' ')
        .replace(/&hellip;|&#8230;/gi, '…')
        .replace(/&amp;/gi, '&')
        .replace(/\s+/g, ' ')
        .trim();

    const pageNumber =
        text.match(/\d+/)?.[0] ?? link.url?.match(/[?&]page=(\d+)/)?.[1] ?? '1';

    return pageNumber;
}

function paginationAriaLabel(
    link: { label: string; url: string | null; active: boolean },
    index: number,
    total: number,
) {
    const visibleLabel = paginationVisibleLabel(link, index, total);
    if (index === 0) return `Página anterior: ${visibleLabel}`;
    if (index === total - 1) return `Página siguiente: ${visibleLabel}`;

    return link.active
        ? `Página ${visibleLabel}, actual`
        : `Página ${visibleLabel}`;
}
</script>

<template>
    <Head :title="`${title} · AURA`">
        <meta
            name="description"
            :content="page.props.seoDefaults.description"
            head-key="description"
        />
        <link rel="canonical" :href="canonicalUrl" head-key="canonical" />
    </Head>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 md:py-12">
        <div
            class="mb-8 flex flex-col gap-3 border-b border-black/10 pb-6 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <p
                    class="mb-2 text-[10px] tracking-[0.18em] text-aura-muted uppercase"
                >
                    AURA · Catálogo
                </p>
                <h1 class="font-serif text-4xl sm:text-5xl">{{ title }}</h1>
            </div>
            <label class="flex items-center gap-3 text-xs"
                >Ordenar
                <select
                    v-model="draft.sort"
                    class="rounded-full border border-black/15 bg-white px-4 py-2.5"
                    @change="applyFilters"
                >
                    <option value="recent">Más recientes</option>
                    <option value="price_asc">Precio: menor a mayor</option>
                    <option value="price_desc">Precio: mayor a menor</option>
                    <option value="name">Nombre</option>
                </select>
            </label>
        </div>
        <div
            class="mb-7 grid gap-3 rounded-aura bg-white/70 p-4 sm:grid-cols-2 lg:grid-cols-5"
        >
            <div
                class="relative grid gap-1 text-[10px] font-semibold tracking-[0.12em] text-aura-muted uppercase"
            >
                <label for="catalog-search">Buscar</label>
                <input
                    id="catalog-search"
                    v-model="draft.q"
                    type="search"
                    maxlength="100"
                    role="combobox"
                    aria-autocomplete="list"
                    aria-controls="catalog-search-suggestions"
                    :aria-expanded="suggestions.length > 0"
                    :aria-activedescendant="
                        activeSuggestionIndex >= 0
                            ? `catalog-search-suggestion-${activeSuggestionIndex}`
                            : undefined
                    "
                    placeholder="Producto o marca"
                    class="rounded-lg border border-black/10 bg-white px-3 py-2.5 text-sm font-normal tracking-normal normal-case"
                    @keydown="onSearchKeydown"
                />
                <ul
                    v-if="suggestions.length"
                    id="catalog-search-suggestions"
                    class="absolute top-full z-20 mt-1 w-full overflow-hidden rounded-lg border border-black/10 bg-white shadow-lg"
                    role="listbox"
                    aria-label="Sugerencias de productos"
                >
                    <Link
                        v-for="(suggestion, index) in suggestions"
                        :key="suggestion.url"
                        :id="`catalog-search-suggestion-${index}`"
                        :aria-selected="activeSuggestionIndex === index"
                        role="option"
                        :href="suggestion.url"
                        class="block px-3 py-2 text-sm font-normal tracking-normal normal-case hover:bg-aura-stone"
                        :class="
                            activeSuggestionIndex === index
                                ? 'bg-aura-stone'
                                : ''
                        "
                        >{{ suggestion.name }}</Link
                    >
                </ul>
            </div>
            <label
                class="grid gap-1 text-[10px] font-semibold tracking-[0.12em] text-aura-muted uppercase"
                >Categoría
                <select
                    v-model="draft.category"
                    class="rounded-lg border border-black/10 bg-white px-3 py-2.5 text-sm font-normal tracking-normal normal-case"
                    @change="applyFilters"
                >
                    <option value="">Todas</option>
                    <option
                        v-for="category in categories"
                        :key="category.slug"
                        :value="category.slug"
                    >
                        {{ category.name }}
                    </option>
                </select>
            </label>
            <label
                class="grid gap-1 text-[10px] font-semibold tracking-[0.12em] text-aura-muted uppercase"
                >Talla
                <select
                    v-model="draft.size"
                    class="rounded-lg border border-black/10 bg-white px-3 py-2.5 text-sm font-normal tracking-normal normal-case"
                    @change="applyFilters"
                >
                    <option value="">Todas</option>
                    <option
                        v-for="size in [
                            'S',
                            'M',
                            'L',
                            'XL',
                            'UNITALLA',
                            '50 CM',
                            '55 CM',
                        ]"
                        :key="size"
                        :value="size"
                    >
                        {{ size }}
                    </option>
                </select>
            </label>
            <label
                class="grid gap-1 text-[10px] font-semibold tracking-[0.12em] text-aura-muted uppercase"
                >Color
                <input
                    v-model="draft.color"
                    class="rounded-lg border border-black/10 bg-white px-3 py-2.5 text-sm font-normal tracking-normal normal-case"
                    placeholder="Ej. Negro"
                    @change="applyFilters"
                />
            </label>
            <label
                class="grid gap-1 text-[10px] font-semibold tracking-[0.12em] text-aura-muted uppercase"
                >Precio mínimo
                <input
                    v-model.number="draft.min"
                    type="number"
                    min="0"
                    class="rounded-lg border border-black/10 bg-white px-3 py-2.5 text-sm font-normal tracking-normal normal-case"
                    placeholder="$0"
                    @change="applyFilters"
                />
            </label>
            <label
                class="grid gap-1 text-[10px] font-semibold tracking-[0.12em] text-aura-muted uppercase"
                >Precio máximo
                <input
                    v-model.number="draft.max"
                    type="number"
                    min="0"
                    class="rounded-lg border border-black/10 bg-white px-3 py-2.5 text-sm font-normal tracking-normal normal-case"
                    placeholder="Sin límite"
                    @change="applyFilters"
                />
            </label>
        </div>
        <div
            class="mb-5 flex items-center justify-between text-xs text-aura-muted"
        >
            <span>{{ displayedProducts.length }} productos mostrados</span
            ><button
                v-if="activeFilterCount"
                type="button"
                class="underline underline-offset-4"
                @click="clearFilters"
            >
                Limpiar filtros ({{ activeFilterCount }})
            </button>
        </div>
        <div
            v-if="displayedProducts.length"
            class="grid grid-cols-2 gap-x-3 gap-y-8 sm:grid-cols-3 sm:gap-x-5 lg:grid-cols-4"
        >
            <ProductCard
                v-for="product in displayedProducts"
                :key="product.id"
                :product="product"
            />
        </div>
        <div
            v-else
            class="rounded-aura border border-black/10 bg-white px-6 py-14 text-center"
        >
            <h2 class="font-serif text-2xl">Aún no hay productos publicados</h2>
            <p class="mx-auto mt-3 max-w-md text-sm text-aura-muted">
                El catálogo importado está en revisión. Ajusta los filtros o
                vuelve pronto para ver las primeras piezas.
            </p>
            <Link
                href="/"
                class="mt-6 inline-flex rounded-full bg-aura-ink px-5 py-3 text-sm text-white"
                >Volver al inicio</Link
            >
        </div>
        <div v-if="products.next_page_url" class="mt-8 text-center">
            <button
                type="button"
                class="min-h-11 rounded-full border border-aura-ink px-6 py-3 text-sm font-medium hover:bg-aura-ink hover:text-white disabled:opacity-50"
                :disabled="loadingMore"
                @click="loadMore"
            >
                {{ loadingMore ? 'Cargando…' : 'Cargar más' }}
            </button>
        </div>
        <nav
            v-if="products.links.length > 3"
            class="mt-10 flex flex-wrap justify-center gap-2"
            aria-label="Paginación"
        >
            <template v-for="(link, index) in products.links" :key="link.label">
                <span
                    v-if="!link.url"
                    aria-disabled="true"
                    class="rounded-full border px-4 py-2 text-xs"
                    :class="
                        link.active
                            ? 'border-aura-ink bg-aura-ink text-white'
                            : 'border-black/15 bg-white'
                    "
                    >{{
                        paginationVisibleLabel(
                            link,
                            index,
                            products.links.length,
                        )
                    }}</span
                >
                <Link
                    v-else
                    :href="link.url"
                    :aria-current="link.active ? 'page' : undefined"
                    :aria-label="
                        paginationAriaLabel(link, index, products.links.length)
                    "
                    class="rounded-full border px-4 py-2 text-xs"
                    :class="
                        link.active
                            ? 'border-aura-ink bg-aura-ink text-white'
                            : 'border-black/15 bg-white'
                    "
                >
                    <span aria-hidden="true">{{
                        paginationVisibleLabel(
                            link,
                            index,
                            products.links.length,
                        )
                    }}</span>
                </Link>
            </template>
        </nav>
    </div>
</template>
