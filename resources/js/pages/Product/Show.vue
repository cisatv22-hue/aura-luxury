<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Heart, MessageCircle, Ruler } from '@lucide/vue';
import ProductRow from '@/components/store/ProductRow.vue';
import type { StoreProduct } from '@/components/store/ProductCard.vue';
import { money } from '@/lib/store-format';
import { useBagStore } from '@/stores/bag';
import { useFavoritesStore } from '@/stores/favorites';
import { useRecentlyViewedStore } from '@/stores/recently-viewed';

type ProductDetail = Omit<StoreProduct, 'colors'> & {
    seo_title: string | null;
    seo_description: string | null;
    colors: Array<
        StoreProduct['colors'][number] & {
            image_large?: string | null;
            sizes: Array<{
                sku: string;
                size: string;
                available: boolean;
                low_stock: boolean;
                availability_label: string;
                price_cents?: number;
                compare_at_cents?: number | null;
                promotion_badge?: string | null;
            }>;
        }
    >;
};
const props = defineProps<{
    product: ProductDetail;
    related: StoreProduct[];
    sizeGuide: {
        name: string;
        unit: string;
        rows: Array<Record<string, string | number>>;
    } | null;
    reviews: Array<{
        rating: number;
        title: string | null;
        body: string | null;
        customer: string | null;
    }>;
    canReview: boolean;
}>();
const bag = useBagStore();
const page = usePage();
const whatsappUrl = computed(
    () =>
        `https://wa.me/${page.props.storeSettings.whatsapp_number.replace(/\D/g, '')}`,
);
const favorites = useFavoritesStore();
const auth = page.props.auth as {
    user: unknown;
    favoriteProductIds?: number[];
};
favorites.setServerIds(auth.favoriteProductIds ?? []);
const recentlyViewed = useRecentlyViewedStore();
const restockForm = useForm({ sku: '', email: '', phone: '' });
const reviewForm = useForm({ rating: 5, title: '', body: '' });
const colorIndex = ref(0);
const selectedSize = ref('');
const guideOpen = ref(false);
const heightCm = ref<number | string>('');
const weightKg = ref<number | string>('');
const guideColumns = computed(() =>
    Object.keys(props.sizeGuide?.rows[0] ?? {}).filter(
        (column) => !column.startsWith('_'),
    ),
);
const calculatorAvailable = computed(
    () =>
        props.sizeGuide?.rows.some(
            (row) =>
                typeof row._size === 'string' &&
                [
                    '_height_min_cm',
                    '_height_max_cm',
                    '_weight_min_kg',
                    '_weight_max_kg',
                ].every(
                    (key) =>
                        Number.isFinite(Number(row[key])) && row[key] !== '',
                ),
        ) ?? false,
);
const sizeRecommendation = computed(() => {
    const height = Number(heightCm.value);
    const weight = Number(weightKg.value);
    if (!heightCm.value || !weightKg.value || height <= 0 || weight <= 0)
        return '';
    if (!calculatorAvailable.value || !props.sizeGuide) {
        return 'AURA aún no ha cargado rangos para recomendar una talla. Consulta la tabla o escríbenos por WhatsApp.';
    }

    const matches = props.sizeGuide.rows
        .filter((row) => {
            const minHeight = Number(row._height_min_cm);
            const maxHeight = Number(row._height_max_cm);
            const minWeight = Number(row._weight_min_kg);
            const maxWeight = Number(row._weight_max_kg);

            return (
                height >= minHeight &&
                height <= maxHeight &&
                weight >= minWeight &&
                weight <= maxWeight
            );
        })
        .map((row) => String(row._size));

    if (matches.length === 1) return `Te recomendamos la talla ${matches[0]}.`;
    if (matches.length > 1)
        return `Tus medidas coinciden con las tallas ${matches.join(' y ')}. Revisa la tabla para elegir.`;
    return 'Tus medidas quedan fuera de los rangos cargados. Consulta la tabla o escríbenos por WhatsApp.';
});
const recentProducts = computed(() =>
    recentlyViewed.products
        .filter((item) => item.id !== props.product.id)
        .slice(0, 4),
);
const currentColor = computed(() => props.product.colors[colorIndex.value]);
const selectedOption = computed(() =>
    currentColor.value?.sizes.find(
        (option) => option.size === selectedSize.value,
    ),
);
const image = computed(() => currentColor.value?.image || props.product.image);
const galleryImages = computed(() => {
    const gallery = currentColor.value?.gallery ?? [];
    if (gallery.length) return gallery;
    if (!image.value) return [];

    return [
        {
            image: image.value,
            large: currentColor.value?.image_large || image.value,
            image_srcset: currentColor.value?.image_srcset ?? null,
            image_alt:
                currentColor.value?.image_alt ||
                `${props.product.name}, color ${currentColor.value?.name ?? ''}`,
        },
    ];
});
const sku = computed(() => selectedOption.value?.sku ?? '');
const currentPrice = computed(
    () => selectedOption.value?.price_cents ?? props.product.price_cents,
);
const currentCompareAt = computed(
    () =>
        selectedOption.value?.compare_at_cents ??
        props.product.compare_at_cents,
);
const lightboxIndex = ref<number | null>(null);

function moveLightbox(step: number) {
    if (lightboxIndex.value === null || !galleryImages.value.length) return;
    lightboxIndex.value =
        (lightboxIndex.value + step + galleryImages.value.length) %
        galleryImages.value.length;
}

function addToBag() {
    if (!selectedOption.value?.available) return;
    bag.add({
        productId: props.product.id,
        slug: props.product.slug,
        name: props.product.name,
        sku: sku.value,
        color: currentColor.value?.name ?? 'Único',
        size: selectedSize.value,
        priceCents: currentPrice.value,
        image: image.value,
    });
}

function requestRestockAlert() {
    if (!selectedOption.value) return;
    restockForm.sku = selectedOption.value.sku;
    restockForm.post('/avisos-reabastecimiento', { preserveScroll: true });
}

onMounted(() => recentlyViewed.record(props.product));
const productStructuredData = computed(() => {
    const offers = props.product.colors.flatMap((color) =>
        color.sizes
            .filter((variant) => typeof variant.price_cents === 'number')
            .map((variant) => ({
                '@type': 'Offer',
                sku: variant.sku,
                priceCurrency: 'MXN',
                price: (variant.price_cents! / 100).toFixed(2),
                availability: variant.available
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                url: new URL(
                    `/producto/${props.product.slug}`,
                    page.props.appUrl,
                ).toString(),
            })),
    );

    return JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'Product',
        name: props.product.name,
        description: props.product.seo_description || props.product.description,
        image: props.product.image ? [props.product.image] : undefined,
        brand: { '@type': 'Brand', name: 'AURA' },
        offers,
    }).replaceAll('<', '\\u003c');
});
</script>

<template>
    <Head :title="product.seo_title || `${product.name} · AURA`">
        <link
            rel="canonical"
            :href="`/producto/${product.slug}`"
            head-key="canonical"
        />
        <meta
            name="description"
            :content="
                product.seo_description ||
                product.description ||
                `${product.name} en AURA. Consulta colores, tallas y disponibilidad.`
            "
            head-key="description"
        />
        <meta
            property="og:title"
            :content="`${product.name} · AURA`"
            head-key="og:title"
        />
        <component
            :is="'script'"
            type="application/ld+json"
            head-key="product-jsonld"
            :innerHTML="productStructuredData"
        />
        <meta
            property="og:description"
            :content="product.description || 'Descubre esta pieza de AURA.'"
            head-key="og:description"
        />
        <meta
            v-if="image"
            property="og:image"
            :content="image"
            head-key="og:image"
        />
    </Head>
    <div class="mx-auto max-w-7xl px-4 py-7 sm:px-6 md:py-12">
        <p class="mb-6 text-xs text-aura-muted">
            <Link href="/tienda" class="hover:underline">Tienda</Link>
            <span aria-hidden="true">/</span> {{ product.category }}
            <span aria-hidden="true">/</span>
            <span class="text-aura-ink">{{ product.name }}</span>
        </p>
        <div class="grid gap-8 md:grid-cols-[1.08fr_0.92fr] md:gap-14">
            <div class="grid grid-cols-2 gap-2 sm:gap-3">
                <div
                    v-for="(photo, index) in galleryImages"
                    :key="`${currentColor?.name}-${index}`"
                    class="relative aspect-[4/5] overflow-hidden rounded-aura bg-aura-stone"
                    :class="index === 0 ? 'col-span-2' : ''"
                >
                    <button
                        type="button"
                        class="group absolute inset-0 h-full w-full cursor-zoom-in"
                        :aria-label="`Ampliar foto ${index + 1} de ${galleryImages.length}`"
                        @click="lightboxIndex = index"
                    >
                        <img
                            :src="photo.image"
                            :srcset="photo.image_srcset ?? undefined"
                            sizes="(max-width: 768px) 100vw, 58vw"
                            :alt="photo.image_alt || product.name"
                            class="h-full w-full object-cover"
                            :fetchpriority="index === 0 ? 'high' : 'auto'"
                            :loading="index === 0 ? 'eager' : 'lazy'"
                            width="1000"
                            height="1250"
                        />
                        <span
                            class="absolute right-3 bottom-3 rounded-full bg-white/90 px-3 py-2 text-xs text-aura-ink opacity-0 shadow transition group-hover:opacity-100 group-focus-visible:opacity-100"
                        >
                            Ampliar
                        </span>
                    </button>
                </div>
                <div
                    v-if="!galleryImages.length"
                    class="col-span-2 grid aspect-[4/5] place-items-center rounded-aura bg-aura-stone text-sm text-aura-muted"
                >
                    Imagen próximamente
                </div>
            </div>
            <div class="md:sticky md:top-28 md:h-fit">
                <p
                    v-if="product.category"
                    class="text-[10px] tracking-[0.18em] text-aura-muted uppercase"
                >
                    {{ product.category }}
                </p>
                <div class="mt-2 flex items-start justify-between gap-4">
                    <h1 class="font-serif text-3xl leading-tight sm:text-4xl">
                        {{ product.name }}
                    </h1>
                    <button
                        type="button"
                        class="grid size-11 shrink-0 place-items-center rounded-full border border-black/15"
                        :aria-label="
                            favorites.has(product.id)
                                ? 'Quitar de favoritos'
                                : 'Guardar en favoritos'
                        "
                        :aria-pressed="favorites.has(product.id)"
                        @click="favorites.toggle(product, Boolean(auth.user))"
                    >
                        <Heart
                            :size="20"
                            :fill="
                                favorites.has(product.id)
                                    ? 'currentColor'
                                    : 'none'
                            "
                        />
                    </button>
                </div>
                <p
                    v-if="currentCompareAt"
                    class="mt-4 text-sm text-aura-muted line-through"
                >
                    {{ money(currentCompareAt) }}
                </p>
                <p class="mt-1 text-lg">
                    {{ money(currentPrice) }}
                    <span
                        v-if="selectedOption?.promotion_badge"
                        class="ml-2 text-xs font-semibold text-aura-ink"
                        >{{ selectedOption.promotion_badge }}</span
                    >
                </p>
                <p
                    v-if="product.description"
                    class="mt-4 max-w-lg text-sm leading-6 text-aura-muted"
                >
                    {{ product.description }}
                </p>
                <div class="mt-7">
                    <div class="mb-3 flex items-center justify-between">
                        <h2
                            class="text-xs font-semibold tracking-[0.15em] uppercase"
                        >
                            Color: {{ currentColor?.name }}
                        </h2>
                        <span class="text-xs text-aura-muted"
                            >{{ product.colors.length }} opciones</span
                        >
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="(color, index) in product.colors"
                            :key="color.name"
                            type="button"
                            class="rounded-full border px-4 py-2 text-xs"
                            :class="
                                colorIndex === index
                                    ? 'border-aura-ink bg-aura-ink text-white'
                                    : 'border-black/15 bg-white'
                            "
                            :aria-pressed="colorIndex === index"
                            @click="
                                colorIndex = index;
                                selectedSize = '';
                            "
                        >
                            {{ color.name }}
                        </button>
                    </div>
                </div>
                <div class="mt-7">
                    <div class="mb-3 flex items-center justify-between">
                        <h2
                            class="text-xs font-semibold tracking-[0.15em] uppercase"
                        >
                            Talla
                        </h2>
                        <button
                            v-if="sizeGuide"
                            type="button"
                            class="inline-flex items-center gap-1 text-xs underline underline-offset-4"
                            @click="guideOpen = true"
                        >
                            <Ruler :size="14" /> Guía de tallas
                        </button>
                    </div>
                    <div class="grid grid-cols-4 gap-2">
                        <button
                            v-for="option in currentColor?.sizes"
                            :key="option.size"
                            type="button"
                            class="min-h-12 rounded-lg border text-sm"
                            :class="[
                                selectedSize === option.size
                                    ? 'border-aura-ink bg-aura-ink text-white'
                                    : 'border-black/15 bg-white',
                                !option.available
                                    ? 'line-through opacity-45'
                                    : 'hover:border-aura-ink',
                            ]"
                            :aria-pressed="selectedSize === option.size"
                            @click="selectedSize = option.size"
                        >
                            {{ option.size }}
                        </button>
                    </div>
                    <p
                        v-if="selectedOption?.low_stock"
                        class="mt-3 text-xs font-medium text-aura-ink"
                        role="status"
                        aria-live="polite"
                    >
                        {{ selectedOption.availability_label }}
                    </p>
                    <p
                        v-if="!product.available"
                        class="mt-3 text-xs text-aura-muted"
                    >
                        Agotado por ahora. Puedes solicitar un aviso cuando
                        regrese.
                    </p>
                </div>
                <button
                    v-if="selectedOption?.available"
                    type="button"
                    class="mt-8 flex min-h-14 w-full items-center justify-center rounded-full bg-aura-ink px-6 text-sm font-semibold text-white transition hover:bg-black/80 disabled:cursor-not-allowed disabled:bg-black/25"
                    @click="addToBag"
                >
                    Agregar a la bolsa
                </button>
                <form
                    v-else-if="selectedOption"
                    class="mt-8 space-y-3 rounded-aura border border-black/10 bg-white p-4"
                    @submit.prevent="requestRestockAlert"
                >
                    <p class="text-sm font-medium">
                        Esta talla está agotada. Te avisamos cuando vuelva.
                    </p>
                    <label class="block text-xs" for="restock-email"
                        >Correo electrónico</label
                    >
                    <input
                        id="restock-email"
                        v-model="restockForm.email"
                        type="email"
                        required
                        autocomplete="email"
                        class="min-h-11 w-full rounded-lg border border-black/15 px-3 text-sm"
                    />
                    <p
                        v-if="restockForm.errors.email"
                        class="text-xs text-red-700"
                    >
                        {{ restockForm.errors.email }}
                    </p>
                    <button
                        type="submit"
                        :disabled="restockForm.processing"
                        class="min-h-11 w-full rounded-full bg-aura-ink px-5 text-sm font-medium text-white disabled:opacity-50"
                    >
                        {{
                            restockForm.wasSuccessful
                                ? 'Aviso solicitado'
                                : 'Avísame cuando regrese'
                        }}
                    </button>
                </form>
                <a
                    :href="whatsappUrl"
                    class="mt-4 flex items-center justify-center gap-2 py-2 text-xs text-aura-muted hover:text-aura-ink"
                    ><MessageCircle :size="15" /> ¿Tienes dudas? Escríbenos por
                    WhatsApp</a
                >
                <div class="mt-7 border-t border-black/10 pt-5 text-sm">
                    <p class="font-medium">
                        Envío gratis desde
                        {{
                            money(
                                page.props.storeSettings
                                    .free_shipping_threshold_cents,
                            )
                        }}
                    </p>
                    <p class="mt-2 text-xs text-aura-muted">
                        Los detalles de entrega se confirman al enviar tu
                        pedido.
                    </p>
                </div>
            </div>
        </div>
        <div
            v-if="lightboxIndex !== null && galleryImages[lightboxIndex]"
            class="fixed inset-0 z-[90] grid place-items-center bg-black/90 p-4"
            role="dialog"
            aria-modal="true"
            aria-label="Galería de imágenes del producto"
            @click.self="lightboxIndex = null"
            @keydown.esc.window="lightboxIndex = null"
            @keydown.left.window="moveLightbox(-1)"
            @keydown.right.window="moveLightbox(1)"
        >
            <button
                type="button"
                class="absolute top-4 right-4 rounded-full bg-white px-4 py-2 text-sm text-aura-ink"
                aria-label="Cerrar galería"
                @click="lightboxIndex = null"
            >
                Cerrar
            </button>
            <button
                v-if="galleryImages.length > 1"
                type="button"
                class="absolute left-3 rounded-full bg-white px-4 py-3 text-aura-ink sm:left-8"
                aria-label="Foto anterior"
                @click="moveLightbox(-1)"
            >
                Anterior
            </button>
            <img
                :src="galleryImages[lightboxIndex].large"
                :alt="galleryImages[lightboxIndex].image_alt || product.name"
                class="max-h-[85vh] max-w-[85vw] rounded-aura object-contain"
            />
            <button
                v-if="galleryImages.length > 1"
                type="button"
                class="absolute right-3 rounded-full bg-white px-4 py-3 text-aura-ink sm:right-8"
                aria-label="Foto siguiente"
                @click="moveLightbox(1)"
            >
                Siguiente
            </button>
            <p class="absolute bottom-4 text-sm text-white">
                {{ lightboxIndex + 1 }} / {{ galleryImages.length }}
            </p>
        </div>
        <div
            v-if="guideOpen && sizeGuide"
            class="fixed inset-0 z-[70] grid place-items-center bg-black/45 p-4"
            @click.self="guideOpen = false"
        >
            <section
                role="dialog"
                aria-modal="true"
                aria-labelledby="size-guide-title"
                class="max-h-[85vh] w-full max-w-2xl overflow-auto rounded-aura bg-white p-5 shadow-xl sm:p-7"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p
                            class="text-xs tracking-[0.16em] text-aura-muted uppercase"
                        >
                            {{ sizeGuide.unit }}
                        </p>
                        <h2
                            id="size-guide-title"
                            class="mt-1 font-serif text-2xl"
                        >
                            {{ sizeGuide.name }}
                        </h2>
                    </div>
                    <button
                        type="button"
                        class="rounded-full border border-black/15 px-3 py-2 text-xs"
                        @click="guideOpen = false"
                    >
                        Cerrar
                    </button>
                </div>
                <div class="mt-5 overflow-x-auto">
                    <div
                        class="mb-6 grid gap-3 rounded-xl border border-black/10 bg-aura-stone/40 p-4 sm:grid-cols-2"
                    >
                        <label class="grid gap-1 text-xs" for="size-height">
                            Estatura (cm)
                            <input
                                id="size-height"
                                v-model.number="heightCm"
                                type="number"
                                min="1"
                                max="250"
                                inputmode="numeric"
                                class="min-h-11 rounded-lg border border-black/15 bg-white px-3 text-sm"
                                placeholder="Ej. 170"
                            />
                        </label>
                        <label class="grid gap-1 text-xs" for="size-weight">
                            Peso (kg)
                            <input
                                id="size-weight"
                                v-model.number="weightKg"
                                type="number"
                                min="1"
                                max="300"
                                inputmode="numeric"
                                class="min-h-11 rounded-lg border border-black/15 bg-white px-3 text-sm"
                                placeholder="Ej. 68"
                            />
                        </label>
                        <p
                            class="text-sm text-aura-muted sm:col-span-2"
                            aria-live="polite"
                        >
                            {{
                                sizeRecommendation ||
                                'Ingresa tus medidas para consultar una recomendación.'
                            }}
                        </p>
                    </div>
                    <table
                        v-if="guideColumns.length"
                        class="w-full border-collapse text-left text-sm"
                    >
                        <thead>
                            <tr>
                                <th
                                    v-for="column in guideColumns"
                                    :key="column"
                                    class="border-b border-black/15 px-3 py-2 font-semibold"
                                >
                                    {{ column }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, index) in sizeGuide.rows"
                                :key="index"
                            >
                                <td
                                    v-for="column in guideColumns"
                                    :key="column"
                                    class="border-b border-black/10 px-3 py-2"
                                >
                                    {{ row[column] }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p
                        v-else
                        class="text-sm whitespace-pre-line text-aura-muted"
                    >
                        {{ JSON.stringify(sizeGuide.rows, null, 2) }}
                    </p>
                </div>
            </section>
        </div>
        <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
            <h2 class="font-serif text-2xl">Reseñas</h2>
            <p v-if="!reviews.length" class="mt-3 text-sm text-aura-muted">
                Este producto todavía no tiene reseñas publicadas.
            </p>
            <article
                v-for="(review, index) in reviews"
                :key="index"
                class="mt-5 border-b border-black/10 pb-4"
            >
                <p
                    class="text-sm"
                    :aria-label="`${review.rating} de 5 estrellas`"
                >
                    {{ '★'.repeat(review.rating)
                    }}{{ '☆'.repeat(5 - review.rating) }}
                </p>
                <h3 v-if="review.title" class="mt-1 font-medium">
                    {{ review.title }}
                </h3>
                <p v-if="review.body" class="mt-1 text-sm text-aura-muted">
                    {{ review.body }}
                </p>
                <p class="mt-2 text-xs text-aura-muted">
                    {{ review.customer }}
                </p>
            </article>
            <form
                v-if="canReview"
                class="mt-6 grid max-w-xl gap-3"
                @submit.prevent="
                    reviewForm.post(`/cuenta/productos/${product.id}/resena`)
                "
            >
                <label class="grid gap-1 text-sm"
                    >Calificación<select
                        v-model.number="reviewForm.rating"
                        class="rounded border p-3"
                    >
                        <option
                            v-for="value in [5, 4, 3, 2, 1]"
                            :key="value"
                            :value="value"
                        >
                            {{ value }} de 5
                        </option>
                    </select></label
                >
                <input
                    v-model="reviewForm.title"
                    maxlength="120"
                    class="rounded border p-3"
                    placeholder="Título (opcional)"
                />
                <textarea
                    v-model="reviewForm.body"
                    maxlength="2000"
                    rows="4"
                    class="rounded border p-3"
                    placeholder="Cuéntanos qué te pareció"
                />
                <p v-if="reviewForm.errors.body" class="text-sm text-red-700">
                    {{ reviewForm.errors.body }}
                </p>
                <button
                    :disabled="reviewForm.processing"
                    class="w-fit rounded-full bg-aura-ink px-5 py-3 text-sm text-white"
                >
                    Enviar reseña
                </button>
                <p class="text-xs text-aura-muted">
                    Solo puedes reseñar una compra entregada. La reseña se
                    publica después de moderación.
                </p>
            </form>
        </section>
        <ProductRow
            v-if="related.length"
            title="Combínalo con"
            :products="related"
        />
        <ProductRow
            v-if="recentProducts.length"
            title="Vistos recientemente"
            :products="recentProducts"
        />
    </div>
</template>
