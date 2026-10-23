<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Heart } from '@lucide/vue';
import ProductCard from '@/components/store/ProductCard.vue';
import { useFavoritesStore } from '@/stores/favorites';

const favorites = useFavoritesStore();
const page = usePage();
const auth = page.props.auth as {
    user: unknown;
    favoriteProductIds?: number[];
};
const props = defineProps<{
    savedProducts: import('@/components/store/ProductCard.vue').StoreProduct[];
}>();
favorites.setServerIds(auth.favoriteProductIds ?? []);
const products = computed(() => [
    ...new Map(
        [...props.savedProducts, ...favorites.items].map((item) => [
            item.id,
            item,
        ]),
    ).values(),
]);
</script>

<template>
    <Head title="Favoritos · AURA" />
    <section class="mx-auto min-h-[55vh] max-w-7xl px-4 py-12 sm:px-6">
        <div class="mb-8 flex items-center gap-3">
            <Heart :size="22" />
            <h1 class="font-serif text-3xl">Tus favoritos</h1>
        </div>
        <div
            v-if="products.length"
            class="grid grid-cols-2 gap-x-3 gap-y-8 sm:grid-cols-3 sm:gap-x-5 lg:grid-cols-4"
        >
            <ProductCard
                v-for="product in products"
                :key="product.id"
                :product="product"
            />
        </div>
        <div v-else class="grid min-h-[40vh] place-items-center text-center">
            <div>
                <p class="font-serif text-xl">Todavía no guardaste piezas</p>
                <p class="mt-2 text-sm text-aura-muted">
                    Explora la tienda y toca el corazón para guardar tus
                    favoritas.
                </p>
                <Link
                    href="/tienda"
                    class="mt-5 inline-flex rounded-full bg-aura-ink px-5 py-3 text-sm text-white"
                    >Explorar la tienda</Link
                >
            </div>
        </div>
    </section>
</template>
