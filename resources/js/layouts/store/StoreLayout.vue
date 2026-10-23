<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { Heart, Menu, Search, ShoppingBag } from '@lucide/vue';
import BagDrawer from '@/components/store/BagDrawer.vue';
import { useBagStore } from '@/stores/bag';

const page = usePage();
const announcement = computed(
    () =>
        (
            page.props as {
                announcement?: {
                    message: string;
                    link_url: string | null;
                } | null;
            }
        ).announcement ?? null,
);
const bag = useBagStore();
const storeSettings = computed(() => page.props.storeSettings);
const whatsappUrl = computed(
    () =>
        `https://wa.me/${storeSettings.value.whatsapp_number.replace(/\D/g, '')}`,
);
const bagOpen = ref(false);
const menuOpen = ref(false);
const desktopSearchInput = ref<HTMLInputElement | null>(null);
const mobileSearchInput = ref<HTMLInputElement | null>(null);
const newsletter = useForm({ email: '', consent: false });
const searchTerm = ref(
    String(page.url.split('?q=')[1] ?? '').split('&')[0] ?? '',
);

function search() {
    const query = searchTerm.value.trim();
    window.location.assign(
        query ? `/buscar?q=${encodeURIComponent(query)}` : '/buscar',
    );
}

function subscribe() {
    newsletter.post('/newsletter', {
        preserveScroll: true,
        onSuccess: () => newsletter.reset('email', 'consent'),
    });
}

function focusSearchShortcut(event: KeyboardEvent) {
    if (
        event.key !== '/' ||
        event.altKey ||
        event.ctrlKey ||
        event.metaKey ||
        event.shiftKey
    ) {
        return;
    }

    const target = event.target;
    if (
        target instanceof HTMLElement &&
        (target.isContentEditable ||
            ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName))
    ) {
        return;
    }

    event.preventDefault();
    if (desktopSearchInput.value?.getClientRects().length) {
        desktopSearchInput.value.focus();
        return;
    }

    if (window.matchMedia('(max-width: 767px)').matches) {
        menuOpen.value = true;
        void nextTick(() => mobileSearchInput.value?.focus());
        return;
    }

    window.location.assign('/buscar');
}

onMounted(() => window.addEventListener('keydown', focusSearchShortcut));
onUnmounted(() => window.removeEventListener('keydown', focusSearchShortcut));
</script>

<template>
    <div class="min-h-screen bg-aura-paper text-aura-ink">
        <Link
            v-if="announcement?.link_url"
            :href="announcement.link_url"
            class="block bg-aura-ink px-4 py-2 text-center text-[10px] font-medium tracking-[0.12em] text-white sm:text-xs"
            >{{ announcement.message }}</Link
        >
        <div
            v-else
            class="bg-aura-ink px-4 py-2 text-center text-[10px] font-medium tracking-[0.12em] text-white sm:text-xs"
        >
            {{
                announcement?.message ||
                'ATENCIÓN PERSONALIZADA POR WHATSAPP · PEDIDOS SUJETOS A CONFIRMACIÓN'
            }}
        </div>
        <header
            class="sticky top-0 z-40 border-b border-black/10 bg-aura-paper/95 backdrop-blur"
        >
            <div
                class="mx-auto flex h-[68px] max-w-7xl items-center justify-between gap-4 px-4 sm:px-6"
            >
                <button
                    type="button"
                    class="grid size-10 place-items-center rounded-full md:hidden"
                    aria-label="Abrir menú"
                    :aria-expanded="menuOpen"
                    @click="menuOpen = !menuOpen"
                >
                    <Menu :size="20" />
                </button>
                <Link
                    href="/"
                    class="flex items-center gap-2 text-[1.35rem] font-semibold tracking-[0.3em]"
                    >AURA<span
                        aria-hidden="true"
                        class="text-base text-aura-silver"
                        >✦</span
                    ></Link
                >
                <nav
                    class="hidden items-center gap-7 text-[11px] font-medium tracking-[0.12em] uppercase md:flex"
                    aria-label="Navegación principal"
                >
                    <Link href="/tienda" class="hover:text-aura-muted"
                        >Tienda</Link
                    >
                    <Link href="/tienda/hoodies" class="hover:text-aura-muted"
                        >Sudaderas</Link
                    >
                    <Link
                        href="/tienda/streetwear"
                        class="hover:text-aura-muted"
                        >Streetwear</Link
                    >
                    <Link href="/tienda/chains" class="hover:text-aura-muted"
                        >Plata .925</Link
                    >
                    <Link href="/tienda/combos" class="hover:text-aura-muted"
                        >Kits</Link
                    >
                </nav>
                <div class="flex items-center gap-1">
                    <form
                        class="hidden items-center rounded-full border border-black/10 bg-white/60 px-3 lg:flex"
                        role="search"
                        @submit.prevent="search"
                    >
                        <Search :size="15" class="text-aura-muted" />
                        <input
                            ref="desktopSearchInput"
                            v-model="searchTerm"
                            aria-label="Buscar productos"
                            placeholder="Buscar"
                            class="w-28 border-0 bg-transparent px-2 py-2 text-xs outline-none focus:ring-0"
                        />
                    </form>
                    <Link
                        href="/favoritos"
                        class="grid size-10 place-items-center rounded-full hover:bg-white"
                        aria-label="Favoritos"
                        ><Heart :size="19"
                    /></Link>
                    <button
                        type="button"
                        class="relative grid size-10 place-items-center rounded-full hover:bg-white"
                        :aria-label="`Abrir bolsa (${bag.count} productos)`"
                        @click="bagOpen = true"
                    >
                        <ShoppingBag :size="19" />
                        <span
                            v-if="bag.count"
                            class="absolute top-0 right-0 grid min-w-4 place-items-center rounded-full bg-aura-ink px-1 text-[9px] leading-4 text-white"
                            >{{ bag.count }}</span
                        >
                    </button>
                </div>
            </div>
            <nav
                v-if="menuOpen"
                class="grid gap-1 border-t border-black/10 px-4 py-3 text-sm md:hidden"
                aria-label="Navegación móvil"
            >
                <Link
                    href="/tienda"
                    class="rounded-lg px-3 py-2 hover:bg-white"
                    @click="menuOpen = false"
                    >Tienda</Link
                >
                <Link
                    href="/tienda/hoodies"
                    class="rounded-lg px-3 py-2 hover:bg-white"
                    @click="menuOpen = false"
                    >Sudaderas</Link
                >
                <Link
                    href="/tienda/streetwear"
                    class="rounded-lg px-3 py-2 hover:bg-white"
                    @click="menuOpen = false"
                    >Streetwear</Link
                >
                <Link
                    href="/tienda/chains"
                    class="rounded-lg px-3 py-2 hover:bg-white"
                    @click="menuOpen = false"
                    >Plata .925</Link
                >
                <Link
                    href="/tienda/combos"
                    class="rounded-lg px-3 py-2 hover:bg-white"
                    @click="menuOpen = false"
                    >Kits</Link
                >
                <form
                    class="flex items-center gap-2 rounded-lg bg-white px-3 py-2"
                    role="search"
                    @submit.prevent="search"
                >
                    <Search :size="15" /><input
                        ref="mobileSearchInput"
                        v-model="searchTerm"
                        aria-label="Buscar productos"
                        placeholder="Buscar productos"
                        class="w-full border-0 bg-transparent text-sm outline-none focus:ring-0"
                    />
                </form>
            </nav>
        </header>
        <main><slot /></main>
        <footer class="mt-16 bg-aura-ink px-5 py-10 text-white">
            <div class="mx-auto grid max-w-7xl gap-8 sm:grid-cols-4">
                <div>
                    <p class="text-xl font-semibold tracking-[0.28em]">AURA</p>
                    <p class="mt-3 max-w-xs text-sm text-white/65">
                        Prendas y accesorios seleccionados para acompañar tu
                        estilo.
                    </p>
                </div>
                <div>
                    <h2
                        class="text-xs tracking-[0.18em] text-white/55 uppercase"
                    >
                        Novedades AURA
                    </h2>
                    <p class="mt-3 text-sm text-white/65">
                        Recibe lanzamientos y noticias. Confirma tu correo para
                        activar la suscripción.
                    </p>
                    <form class="mt-3 space-y-3" @submit.prevent="subscribe">
                        <label class="sr-only" for="newsletter-email"
                            >Correo electrónico</label
                        >
                        <input
                            id="newsletter-email"
                            v-model="newsletter.email"
                            type="email"
                            required
                            autocomplete="email"
                            placeholder="tu@correo.com"
                            class="min-h-11 w-full rounded-lg border border-white/25 bg-white/10 px-3 text-sm text-white placeholder:text-white/45"
                        />
                        <label
                            class="flex items-start gap-2 text-xs leading-5 text-white/70"
                            ><input
                                v-model="newsletter.consent"
                                type="checkbox"
                                required
                                class="mt-1"
                            /><span
                                >Acepto recibir novedades de AURA. Puedo darme
                                de baja cuando quiera.</span
                            ></label
                        >
                        <button
                            type="submit"
                            :disabled="newsletter.processing"
                            class="min-h-10 rounded-full border border-white/35 px-4 text-xs font-medium disabled:opacity-50"
                        >
                            {{
                                newsletter.wasSuccessful
                                    ? 'Revisa tu correo'
                                    : 'Suscribirme'
                            }}
                        </button>
                        <p
                            v-if="
                                newsletter.errors.email ||
                                newsletter.errors.consent
                            "
                            class="text-xs text-red-200"
                        >
                            {{
                                newsletter.errors.email ||
                                'Debes aceptar para suscribirte.'
                            }}
                        </p>
                    </form>
                </div>
                <div>
                    <h2
                        class="text-xs tracking-[0.18em] text-white/55 uppercase"
                    >
                        Atención
                    </h2>
                    <a
                        :href="whatsappUrl"
                        class="mt-3 inline-block text-sm hover:underline"
                        >WhatsApp · {{ storeSettings.whatsapp_number }}</a
                    >
                </div>
                <div>
                    <h2
                        class="text-xs tracking-[0.18em] text-white/55 uppercase"
                    >
                        Explora
                    </h2>
                    <div class="mt-3 grid gap-2 text-sm">
                        <Link href="/tienda" class="hover:underline">Ver catálogo</Link>
                        <Link href="/tienda/hoodies" class="hover:underline">Sudaderas</Link>
                        <Link href="/tienda/chains" class="hover:underline">Plata .925</Link>
                        <a :href="whatsappUrl" target="_blank" rel="noreferrer" class="hover:underline">Preguntas por WhatsApp</a>
                    </div>
                </div>
            </div>
            <p
                class="mx-auto mt-9 max-w-7xl border-t border-white/15 pt-5 text-xs text-white/70"
            >
                © {{ new Date().getFullYear() }} AURA. Todos los derechos
                reservados.
            </p>
        </footer>
        <BagDrawer :open="bagOpen" @close="bagOpen = false" />
    </div>
</template>
