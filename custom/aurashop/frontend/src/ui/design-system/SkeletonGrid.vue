<script setup lang="ts">
withDefaults(defineProps<{ count?: number }>(), { count: 8 })
</script>

<template>
  <div class="grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-3 xl:grid-cols-4" aria-busy="true" aria-label="Cargando productos">
    <div v-for="n in count" :key="n" class="overflow-hidden rounded-lg border border-zinc-800 bg-neutral-950">
      <div class="aspect-[4/5] animate-pulse bg-zinc-900"></div>
      <div class="space-y-3 p-4">
        <div class="h-3 w-3/4 animate-pulse rounded bg-zinc-800"></div>
        <div class="h-3 w-1/3 animate-pulse rounded bg-zinc-800"></div>
      </div>
    </div>
  </div>
</template>
