<script setup lang="ts">
import { catalogLink } from '@/router'
import { useShopStore } from '@/stores/shop'
import { usePageTitle } from '@/ui/composables/usePageTitle'
import { useProductList } from '@/ui/composables/useProductList'
import SkeletonGrid from '@/ui/design-system/SkeletonGrid.vue'
import StateMessage from '@/ui/design-system/StateMessage.vue'
import ProductGrid from '@/ui/features/catalog/ProductGrid.vue'

const shop = useShopStore()
usePageTitle('Essentials Streetwear & Plata Ley .925')

const { page, loading, error, reload } = useProductList({ sort: 'newest', perPage: 8 })

const guarantees = [
  { icon: 'fa-certificate', title: 'Garantía Plata Ley .925', text: 'Joyería 100% auténtica en plata fina maciza.' },
  { icon: 'fa-truck-fast', title: 'Envíos a todo México', text: 'Entregas con guía de rastreo.' },
  { icon: 'fa-comments', title: 'Atención personal', text: 'Resolvemos dudas de tallas y existencias por WhatsApp.' },
]
</script>

<template>
  <section class="relative flex min-h-[520px] items-center justify-center overflow-hidden border-b border-zinc-800 py-24 sm:min-h-[600px]">
    <div
      class="absolute inset-0 scale-105 bg-cover bg-center"
      style="background-image: url('https://images.unsplash.com/photo-1558618666-fcd25c85cd64?q=80&w=1400&auto=format&fit=crop'); filter: brightness(0.4) contrast(1.15)"
      aria-hidden="true"
    ></div>
    <div class="absolute inset-0 bg-gradient-to-t from-neutral-950 via-neutral-950/50 to-transparent" aria-hidden="true"></div>

    <div class="relative z-10 mx-auto max-w-5xl px-4 text-center sm:px-6">
      <p
        class="mb-6 inline-flex items-center gap-2 rounded-full border border-zinc-700/80 bg-neutral-900/80 px-5 py-2.5 text-[10px] font-extrabold uppercase tracking-[0.3em] text-white shadow-2xl backdrop-blur-md sm:text-xs"
      >
        <i class="fa-solid fa-gem text-amber-300" aria-hidden="true"></i> Colección streetwear &amp; joyería
      </p>
      <h1 class="mb-6 text-balance font-display text-4xl font-black uppercase leading-tight tracking-tight text-white sm:text-6xl md:text-7xl">
        Essentials <span class="silver-text">&amp; Sterling Silver</span>
      </h1>
      <p class="mx-auto mb-10 max-w-2xl text-sm leading-relaxed tracking-wide text-zinc-300 sm:text-base">
        Cortes oversized de algodón pesado y el brillo eterno de la Plata Ley .925.
      </p>
      <RouterLink :to="catalogLink()" class="btn-primary px-8 py-4 shadow-2xl">Ver catálogo</RouterLink>
    </div>
  </section>

  <section v-if="shop.roots.length" class="mx-auto max-w-7xl px-4 pt-16 sm:px-6 lg:px-8">
    <span class="eyebrow">Colecciones</span>
    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <RouterLink
        v-for="category in shop.roots"
        :key="category.id"
        :to="catalogLink({ categoria: category.id })"
        class="metallic-border group flex items-center justify-between rounded-lg bg-zinc-900/40 p-6"
      >
        <div>
          <h2 class="font-display text-xl font-black uppercase tracking-wide text-white">{{ category.label }}</h2>
          <p v-if="category.description" class="mt-1 text-xs text-zinc-400">{{ category.description }}</p>
        </div>
        <i class="fa-solid fa-arrow-right text-zinc-500 transition-transform group-hover:translate-x-1 group-hover:text-white" aria-hidden="true"></i>
      </RouterLink>
    </div>
  </section>

  <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
    <div class="mb-8 flex items-end justify-between gap-4">
      <div>
        <span class="eyebrow">Recién llegados</span>
        <h2 class="mt-1 font-display text-3xl font-black uppercase tracking-tight text-white">Novedades</h2>
      </div>
      <RouterLink :to="catalogLink()" class="text-[11px] font-extrabold uppercase tracking-wider text-zinc-400 hover:text-white">
        Ver todo <i class="fa-solid fa-arrow-right ml-1" aria-hidden="true"></i>
      </RouterLink>
    </div>
    <SkeletonGrid v-if="loading" :count="4" />
    <StateMessage v-else-if="error" icon="fa-plug-circle-exclamation" title="No pudimos cargar las novedades" :text="error">
      <button type="button" class="btn-outline" @click="reload">Intentar de nuevo</button>
    </StateMessage>
    <StateMessage v-else-if="!page?.items.length" icon="fa-box-open" title="Muy pronto nuevas piezas" text="Estamos preparando la colección." />
    <ProductGrid v-else :products="page.items" />
  </section>

  <section class="border-t border-zinc-800 bg-neutral-950 py-12">
    <ul class="mx-auto grid max-w-7xl grid-cols-1 gap-6 px-4 text-center text-xs sm:px-6 md:grid-cols-3 lg:px-8">
      <li v-for="item in guarantees" :key="item.title" class="flex flex-col items-center rounded border border-zinc-800 bg-zinc-900/40 p-6">
        <i class="fa-solid mb-3 text-2xl text-slate-200" :class="item.icon" aria-hidden="true"></i>
        <h3 class="mb-1 font-black uppercase tracking-wider text-white">{{ item.title }}</h3>
        <p class="text-zinc-400">{{ item.text }}</p>
      </li>
    </ul>
  </section>
</template>
