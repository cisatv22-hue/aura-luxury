<script setup lang="ts">
import { nextTick, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { catalogLink } from '@/router'
import { useShopStore } from '@/stores/shop'

const shop = useShopStore()
const route = useRoute()
const router = useRouter()

const menuOpen = ref(false)
const searchOpen = ref(false)
const term = ref('')
const searchInput = ref<HTMLInputElement | null>(null)

watch(
  () => route.fullPath,
  () => {
    menuOpen.value = false
  },
)

async function toggleSearch(): Promise<void> {
  searchOpen.value = !searchOpen.value
  if (searchOpen.value) {
    term.value = typeof route.query.q === 'string' ? route.query.q : ''
    await nextTick()
    searchInput.value?.focus()
  }
}

function submitSearch(): void {
  searchOpen.value = false
  void router.push(catalogLink({ q: term.value.trim() }))
}

function isActiveCategory(id: number): boolean {
  return route.name === 'catalog' && route.query.categoria === String(id)
}
</script>

<template>
  <div
    class="flex items-center justify-center gap-3 border-b border-zinc-800/80 bg-gradient-to-r from-neutral-950 via-zinc-900 to-neutral-950 px-4 py-2.5 text-center text-[10px] font-extrabold uppercase tracking-widest text-zinc-300 sm:text-[11px]"
  >
    <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-emerald-400" aria-hidden="true"></span>
    <span>Envío express gratis a todo México en compras desde $1,200 MXN</span>
    <span class="hidden text-zinc-600 md:inline">//</span>
    <span class="hidden text-amber-200 md:inline"><i class="fa-solid fa-certificate mr-1" aria-hidden="true"></i>Plata .925 certificada</span>
  </div>

  <header class="glass sticky top-0 z-40 border-b border-zinc-800/80">
    <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
      <button
        type="button"
        class="p-2 text-zinc-300 hover:text-white md:hidden"
        :aria-expanded="menuOpen"
        aria-label="Abrir menú"
        @click="menuOpen = !menuOpen"
      >
        <i class="fa-solid fa-bars-staggered text-xl" aria-hidden="true"></i>
      </button>

      <RouterLink to="/" class="group flex flex-col items-center md:items-start" aria-label="Inicio">
        <span class="silver-text font-display text-2xl font-black tracking-widest transition-transform group-hover:scale-105 sm:text-3xl">A U R A</span>
        <span class="-mt-1 text-[9px] font-black uppercase tracking-[0.35em] text-zinc-400">High Streetwear &amp; Silver .925</span>
      </RouterLink>

      <nav class="hidden items-center gap-8 text-xs font-black uppercase tracking-luxe text-zinc-300 md:flex" aria-label="Principal">
        <RouterLink
          :to="catalogLink()"
          class="border-b-2 py-2 transition-colors hover:text-white"
          :class="route.name === 'catalog' && !route.query.categoria ? 'border-white text-white' : 'border-transparent'"
        >
          Catálogo
        </RouterLink>
        <RouterLink
          v-for="category in shop.roots"
          :key="category.id"
          :to="catalogLink({ categoria: category.id })"
          class="border-b-2 py-2 transition-colors hover:text-white"
          :class="isActiveCategory(category.id) ? 'border-white text-white' : 'border-transparent'"
        >
          {{ category.label }}
        </RouterLink>
      </nav>

      <button
        type="button"
        class="p-2 text-zinc-300 transition-colors hover:text-white"
        :aria-expanded="searchOpen"
        aria-label="Buscar"
        @click="toggleSearch"
      >
        <i class="fa-solid fa-magnifying-glass text-lg" aria-hidden="true"></i>
      </button>
    </div>

    <nav v-show="menuOpen" class="space-y-4 border-t border-zinc-800 bg-neutral-950 px-6 py-6 md:hidden" aria-label="Menú móvil">
      <RouterLink :to="catalogLink()" class="block text-xs font-extrabold uppercase tracking-widest text-slate-200">Todo el catálogo</RouterLink>
      <RouterLink
        v-for="category in shop.roots"
        :key="category.id"
        :to="catalogLink({ categoria: category.id })"
        class="block text-xs font-extrabold uppercase tracking-widest text-slate-200"
      >
        {{ category.label }}
      </RouterLink>
    </nav>

    <form v-show="searchOpen" class="border-t border-zinc-800 bg-neutral-900/95 px-4 py-4 sm:px-8" role="search" @submit.prevent="submitSearch">
      <div class="mx-auto flex max-w-3xl items-center gap-3">
        <i class="fa-solid fa-magnifying-glass text-zinc-500" aria-hidden="true"></i>
        <label for="site-search" class="sr-only">Buscar productos</label>
        <input
          id="site-search"
          ref="searchInput"
          v-model="term"
          type="search"
          placeholder="Buscar sudaderas, cadenas, anillos…"
          class="w-full bg-transparent text-sm font-semibold text-white placeholder-zinc-500 focus:outline-none"
        />
        <button type="submit" class="rounded bg-white px-4 py-1.5 text-xs font-black uppercase tracking-wider text-black">Buscar</button>
      </div>
    </form>
  </header>
</template>
