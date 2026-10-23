<script setup lang="ts">
import useEmblaCarousel from 'embla-carousel-vue';
import type { StoreProduct } from './ProductCard.vue';
import ProductCard from './ProductCard.vue';

defineProps<{ title: string; products: StoreProduct[] }>();
const [emblaRef, emblaApi] = useEmblaCarousel({
    align: 'start',
    containScroll: 'trimSnaps',
});
</script>

<template>
    <section class="py-10 md:py-14">
        <div class="mb-5 flex items-end justify-between gap-4">
            <h2 class="font-serif text-2xl md:text-3xl">{{ title }}</h2>
            <div v-if="products.length > 1" class="flex gap-2">
                <button
                    type="button"
                    class="rounded-full border border-black/15 px-3 py-2 text-sm hover:bg-white focus-visible:outline-2"
                    aria-label="Productos anteriores"
                    @click="emblaApi?.scrollPrev()"
                >
                    ←
                </button>
                <button
                    type="button"
                    class="rounded-full border border-black/15 px-3 py-2 text-sm hover:bg-white focus-visible:outline-2"
                    aria-label="Productos siguientes"
                    @click="emblaApi?.scrollNext()"
                >
                    →
                </button>
            </div>
        </div>
        <div v-if="products.length" ref="emblaRef" class="overflow-hidden">
            <div class="-ml-3 flex touch-pan-y">
                <div
                    v-for="product in products"
                    :key="product.id"
                    class="min-w-0 flex-[0_0_78%] pl-3 sm:flex-[0_0_48%] lg:flex-[0_0_25%]"
                >
                    <ProductCard :product="product" />
                </div>
            </div>
        </div>
        <div
            v-else
            class="rounded-aura border border-black/10 bg-white/70 px-6 py-8 text-sm text-aura-muted"
        >
            El equipo está preparando esta selección. Vuelve pronto para
            descubrir las piezas nuevas.
        </div>
    </section>
</template>
