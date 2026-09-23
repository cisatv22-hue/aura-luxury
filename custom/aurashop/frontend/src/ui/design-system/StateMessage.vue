<script setup lang="ts">
defineProps<{ icon: string; title: string; text?: string }>()
</script>

<template>
  <div class="flex flex-col items-center px-4 py-20 text-center">
    <i class="fa-solid mb-4 text-4xl text-zinc-700" :class="icon" aria-hidden="true"></i>
    <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-zinc-300">{{ title }}</p>
    <p v-if="text" class="mt-2 max-w-md text-sm text-zinc-500">{{ text }}</p>
    <div v-if="$slots.default" class="mt-6"><slot /></div>
  </div>
</template>
