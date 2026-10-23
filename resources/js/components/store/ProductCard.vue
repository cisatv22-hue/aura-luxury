<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Heart } from '@lucide/vue';
import { money } from '@/lib/store-format';
import { useFavoritesStore } from '@/stores/favorites';

export interface StoreProduct {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    price_cents: number;
    max_price_cents?: number;
    compare_at_cents?: number | null;
    promotion_badge?: string | null;
    tag?: string | null;
    category: string | null;
    available: boolean;
    availability_label: string;
    image: string;
    image_srcset?: string | null;
    colors: Array<{
        name: string;
        image: string | null;
        image_srcset?: string | null;
        image_alt: string | null;
        gallery?: Array<{
            image: string;
            large: string;
            image_srcset: string | null;
            image_alt: string | null;
        }>;
    }>;
}

defineProps<{ product: StoreProduct }>();
const favorites = useFavoritesStore();
const page = usePage();
const auth = page.props.auth as {
    user: unknown;
    favoriteProductIds?: number[];
};
favorites.setServerIds(auth.favoriteProductIds ?? []);
</script>

<template>
    <article class="group relative min-w-0">
        <div
            class="relative aspect-[4/5] overflow-hidden rounded-aura bg-aura-stone"
        >
            <Link :href="`/producto/${product.slug}`" class="absolute inset-0">
                <img
                    :src="product.image"
                    :srcset="product.image_srcset ?? undefined"
                    sizes="(max-width: 640px) 80vw, (max-width: 1024px) 48vw, 25vw"
                    :alt="product.colors[0]?.image_alt || product.name"
                    class="h-full w-full object-cover transition duration-700 group-hover:scale-[1.035] motion-reduce:transition-none"
                    loading="lazy"
                    width="800"
                    height="1000"
                />
                <img
                    v-if="product.colors[0]?.gallery?.[1]?.image"
                    :src="product.colors[0].gallery[1].image"
                    :alt="product.colors[0].gallery[1].image_alt || ''"
                    aria-hidden="true"
                    class="absolute inset-0 h-full w-full object-cover opacity-0 transition duration-700 group-hover:scale-[1.035] group-hover:opacity-100 motion-reduce:transition-none"
                    loading="lazy"
                    width="800"
                    height="1000"
                />
                <span
                    class="absolute inset-x-3 bottom-3 translate-y-2 rounded-full bg-white/95 px-4 py-3 text-center text-[10px] font-semibold tracking-[0.14em] text-aura-ink uppercase opacity-0 shadow-sm transition duration-300 group-hover:translate-y-0 group-hover:opacity-100 sm:inset-x-4 sm:bottom-4"
                >Ver pieza <span aria-hidden="true">↗</span></span>
            </Link>
            <span
                class="absolute top-3 left-3 rounded-full bg-white/90 px-3 py-1 text-[10px] font-semibold tracking-[0.14em] uppercase"
                >{{ product.availability_label }}</span
            >
            <span
                v-if="product.promotion_badge || product.tag"
                class="absolute top-3 left-3 mt-8 rounded-full bg-aura-ink px-3 py-1 text-[10px] font-semibold tracking-[0.12em] text-white uppercase"
                >{{ product.promotion_badge || product.tag }}</span
            >
            <button
                type="button"
                class="absolute top-3 right-3 grid size-10 place-items-center rounded-full bg-white/90 text-aura-ink transition hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-aura-ink"
                :aria-label="
                    favorites.has(product.id)
                        ? 'Quitar de favoritos'
                        : 'Guardar en favoritos'
                "
                :aria-pressed="favorites.has(product.id)"
                @click="favorites.toggle(product, Boolean(auth.user))"
            >
                <Heart
                    :size="18"
                    :fill="favorites.has(product.id) ? 'currentColor' : 'none'"
                />
            </button>
        </div>
        <div class="flex items-start justify-between gap-3 pt-3">
            <div class="min-w-0">
                <p
                    v-if="product.category"
                    class="mb-1 text-[10px] tracking-[0.17em] text-aura-muted uppercase"
                >
                    {{ product.category }}
                </p>
                <Link
                    :href="`/producto/${product.slug}`"
                    class="line-clamp-2 leading-snug font-medium hover:underline"
                    >{{ product.name }}</Link
                >
            </div>
            <span class="shrink-0 text-right text-sm">
                <span
                    v-if="product.compare_at_cents"
                    class="mr-1 text-xs text-aura-muted line-through"
                    >{{ money(product.compare_at_cents) }}</span
                >
                {{
                    product.max_price_cents &&
                    product.max_price_cents > product.price_cents
                        ? `${money(product.price_cents)} – ${money(product.max_price_cents)}`
                        : money(product.price_cents)
                }}
            </span>
        </div>
    </article>
</template>
