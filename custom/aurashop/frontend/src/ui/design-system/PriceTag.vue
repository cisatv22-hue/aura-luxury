<script setup lang="ts">
import { computed } from 'vue'
import { formatMoney, type Money } from '@/domain/money'

const props = withDefaults(defineProps<{ price: Money; from?: boolean; size?: 'sm' | 'lg' }>(), {
  from: false,
  size: 'sm',
})

const text = computed(() => formatMoney(props.price))
</script>

<template>
  <p class="font-black text-brand-price" :class="size === 'lg' ? 'text-2xl' : 'text-base'">
    <span v-if="from" class="mr-1 text-[0.7em] font-bold uppercase tracking-wider text-zinc-400">Desde</span>
    {{ text }}
    <span class="ml-0.5 text-[0.6em] font-bold text-zinc-500">{{ price.currency }}</span>
  </p>
</template>
