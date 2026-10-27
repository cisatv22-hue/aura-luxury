<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import ProductRow from '@/components/store/ProductRow.vue';
import type { StoreProduct } from '@/components/store/ProductCard.vue';

const props = defineProps<{
    categories: Array<{ id: number; name: string; slug: string }>;
    featured: StoreProduct[];
    newArrivals: StoreProduct[];
    sections: Array<{ id: number; title: string; products: StoreProduct[] }>;
    categoryHighlights: Array<{
        id: number;
        name: string;
        slug: string;
        image: string;
        image_alt: string;
        product_name: string;
    }>;
    banners: Array<{
        title: string;
        subtitle: string | null;
        image: string | null;
        image_alt: string;
        link_url: string | null;
    }>;
    looks: Array<{
        id: number;
        title: string;
        image: string;
        image_alt: string;
        items: Array<{
            id: number;
            name: string;
            url: string;
            x: number;
            y: number;
        }>;
    }>;
}>();
const activeBanner = computed(() => props.banners[0]);
const page = usePage<{
    seoDefaults: { title: string; description: string };
    storeSettings: { whatsapp_number: string };
}>();
const whatsappUrl = computed(
    () => `https://wa.me/${page.props.storeSettings.whatsapp_number.replace(/\D/g, '')}`,
);
</script>

<template>
    <Head :title="page.props.seoDefaults.title">
        <meta
            name="description"
            :content="page.props.seoDefaults.description"
            head-key="description"
        />
        <meta
            property="og:title"
            :content="page.props.seoDefaults.title"
            head-key="og:title"
        />
        <meta
            property="og:description"
            :content="page.props.seoDefaults.description"
            head-key="og:description"
        />
        <link
            rel="preload"
            as="image"
            :href="activeBanner?.image || '/images/hero_essentials.webp'"
            fetchpriority="high"
            head-key="home-hero-image"
        />
    </Head>
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <section
            class="relative mt-5 grid min-h-[470px] overflow-hidden rounded-[1.5rem] bg-[#2a2927] text-white sm:min-h-[550px] md:mt-8 md:grid-cols-[1.05fr_0.95fr]"
        >
            <div
                class="relative z-10 flex flex-col justify-center px-6 py-12 sm:px-12 md:px-16"
            >
                <p
                    class="mb-5 text-[10px] tracking-[0.28em] text-white/65 uppercase"
                >
                    AURA · Nueva temporada
                </p>
                <h1
                    class="max-w-xl font-serif text-5xl leading-[0.98] sm:text-6xl lg:text-7xl"
                >
                    {{ activeBanner?.title || 'Viste tu propia esencia.' }}
                </h1>
                <p
                    class="mt-5 max-w-md text-sm leading-6 text-white/70 sm:text-base"
                >
                    {{
                        activeBanner?.subtitle ||
                        'Piezas seleccionadas para todos los días. Siluetas cómodas, detalles que hablan por ti.'
                    }}
                </p>
                <Link
                    :href="activeBanner?.link_url || '/tienda'"
                    class="mt-8 inline-flex w-fit rounded-full bg-white px-6 py-3.5 text-xs font-semibold tracking-[0.13em] text-aura-ink uppercase transition hover:bg-aura-stone focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white"
                    >Descubrir colección</Link
                >
            </div>
            <div class="absolute inset-0 md:static md:block">
                <div
                    class="absolute inset-0 bg-[radial-gradient(ellipse_at_76%_32%,#77736d_0%,#383633_32%,#242321_75%)] md:bg-[radial-gradient(ellipse_at_70%_40%,#77736d_0%,#383633_34%,#242321_78%)]"
                />
                <img
                    :src="activeBanner?.image || '/images/hero_essentials.webp'"
                    :alt="
                        activeBanner?.image_alt ||
                        'Sudadera Essentials de AURA en tono crema'
                    "
                    class="absolute inset-0 h-full w-full object-cover object-center opacity-35 mix-blend-luminosity md:opacity-80 md:mix-blend-normal"
                    fetchpriority="high"
                    loading="eager"
                    width="1200"
                    height="1400"
                />
                <div
                    class="absolute inset-0 bg-gradient-to-r from-[#2a2927] via-[#2a2927]/70 to-transparent md:from-transparent md:via-transparent md:to-transparent"
                />
            </div>
        </section>

        <nav
            class="flex [scrollbar-width:none] gap-3 overflow-x-auto py-7 [&::-webkit-scrollbar]:hidden"
            aria-label="Categorías"
        >
            <Link
                v-for="category in categories"
                :key="category.id"
                :href="`/tienda/${category.slug}`"
                class="shrink-0 rounded-full border border-black/15 bg-white/50 px-5 py-3 text-xs font-medium hover:border-black hover:bg-white"
                >{{ category.name }}</Link
            >
            <Link
                href="/coleccion/essentials"
                class="shrink-0 rounded-full border border-black/15 bg-white/50 px-5 py-3 text-xs font-medium hover:border-black hover:bg-white"
                >Essentials</Link
            >
        </nav>

        <template v-if="sections.length">
            <ProductRow
                v-for="section in sections"
                :key="section.id"
                :title="section.title"
                :products="section.products"
            />
        </template>
        <template v-else>
            <ProductRow title="Nuevas piezas" :products="newArrivals" />
            <ProductRow
                v-if="featured.length"
                title="Selección AURA"
                :products="featured"
            />
        </template>

        <section
            v-if="categoryHighlights.length"
            class="py-8 md:py-12"
            aria-labelledby="category-highlights-heading"
        >
            <div class="mb-5 flex items-end justify-between gap-4">
                <div>
                    <p class="text-[10px] tracking-[0.2em] text-aura-muted uppercase">
                        Encuentra tu estilo
                    </p>
                    <h2 id="category-highlights-heading" class="mt-2 font-serif text-3xl sm:text-4xl">
                        Explora AURA
                    </h2>
                </div>
                <Link href="/tienda" class="hidden text-xs underline underline-offset-4 sm:inline">
                    Ver todo el catálogo
                </Link>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-5">
                <Link
                    v-for="category in categoryHighlights"
                    :key="category.id"
                    :href="`/tienda/${category.slug}`"
                    class="group relative min-h-56 overflow-hidden rounded-aura bg-aura-stone sm:aspect-[4/5]"
                >
                    <img
                        :src="category.image"
                        :alt="category.image_alt"
                        class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-105 motion-reduce:transition-none"
                        loading="lazy"
                        width="800"
                        height="1000"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent" />
                    <div class="absolute inset-x-0 bottom-0 p-4 text-white sm:p-6">
                        <p class="text-[10px] tracking-[0.16em] text-white/75 uppercase">
                            {{ category.product_name }}
                        </p>
                        <div class="mt-2 flex items-center justify-between gap-3">
                            <h3 class="font-serif text-xl sm:text-2xl">{{ category.name }}</h3>
                            <span aria-hidden="true" class="grid size-9 shrink-0 place-items-center rounded-full border border-white/65 text-lg transition group-hover:bg-white group-hover:text-aura-ink">↗</span>
                        </div>
                    </div>
                </Link>
            </div>
        </section>

        <section
            v-if="looks.length"
            class="my-12"
            aria-labelledby="looks-heading"
        >
            <div class="mb-5 flex items-end justify-between">
                <div>
                    <p
                        class="text-[10px] tracking-[0.2em] text-aura-muted uppercase"
                    >
                        Inspiración AURA
                    </p>
                    <h2 id="looks-heading" class="mt-2 font-serif text-3xl">
                        Completa el look
                    </h2>
                </div>
            </div>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <article
                    v-for="look in looks"
                    :key="look.id"
                    class="overflow-hidden rounded-aura bg-aura-stone"
                >
                    <div class="relative">
                        <img
                            :src="look.image"
                            :alt="look.image_alt"
                            class="aspect-[4/5] w-full object-cover"
                            loading="lazy"
                            width="800"
                            height="1000"
                        />
                        <Link
                            v-for="item in look.items"
                            :key="item.id"
                            :href="item.url"
                            :aria-label="`Ver ${item.name}`"
                            :title="item.name"
                            :style="{ left: `${item.x}%`, top: `${item.y}%` }"
                            class="absolute grid size-8 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full border-2 border-white bg-aura-ink text-sm text-white shadow-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black"
                            >+</Link
                        >
                    </div>
                    <h3 class="px-4 py-3 text-sm font-medium">
                        {{ look.title }}
                    </h3>
                </article>
            </div>
        </section>

        <section
            class="my-10 grid overflow-hidden rounded-aura bg-aura-ink text-white md:grid-cols-[1.1fr_0.9fr]"
        >
            <img
                src="/images/hero_essentials.webp"
                alt="Piezas de la colección AURA"
                class="h-64 w-full object-cover opacity-80 md:h-full"
                loading="lazy"
                width="1000"
                height="700"
            />
            <div class="flex flex-col justify-center p-7 sm:p-12">
                <p class="text-[10px] tracking-[0.2em] text-white/60 uppercase">
                    Atención AURA
                </p>
                <h2 class="mt-3 font-serif text-3xl sm:text-4xl">¿Tienes dudas antes de elegir?</h2>
                <p class="mt-3 max-w-md text-sm leading-6 text-white/70">
                    Escríbenos para confirmar existencias, detalles de una pieza y el proceso de tu pedido.
                </p>
                <a
                    :href="whatsappUrl"
                    target="_blank"
                    rel="noreferrer"
                    class="mt-6 inline-flex w-fit rounded-full bg-white px-5 py-3 text-xs font-semibold tracking-[0.12em] text-aura-ink uppercase transition hover:bg-aura-stone"
                    >Hablar por WhatsApp ↗</a
                >
            </div>
        </section>

        <section
            class="grid gap-4 border-y border-black/10 py-8 text-center text-xs sm:grid-cols-3 sm:py-10"
        >
            <div>
                <p class="font-semibold tracking-[0.14em] uppercase">
                    Atención personal
                </p>
                <p class="mt-2 text-aura-muted">
                    Resolvemos tus dudas por WhatsApp
                </p>
            </div>
            <div>
                <p class="font-semibold tracking-[0.14em] uppercase">
                    Pedido acompañado
                </p>
                <p class="mt-2 text-aura-muted">
                    Revisamos tu pedido contigo antes de confirmarlo
                </p>
            </div>
            <div>
                <p class="font-semibold tracking-[0.14em] uppercase">
                    Envío por confirmar
                </p>
                <p class="mt-2 text-aura-muted">
                    Costo y cobertura se acuerdan por WhatsApp
                </p>
            </div>
        </section>
    </div>
</template>
