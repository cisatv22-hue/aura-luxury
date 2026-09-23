<script setup lang="ts">
import { computed } from 'vue'
import { childCategories, rootOf, type Category } from '@/domain/catalog'

const props = defineProps<{ categories: Category[]; roots: Category[]; selected: number | null }>()
const emit = defineEmits<{ select: [id: number | null] }>()

const activeRoot = computed(() => (props.selected ? rootOf(props.categories, props.selected) : undefined))
const children = computed(() => (activeRoot.value ? childCategories(props.categories, activeRoot.value.id) : []))
</script>

<template>
  <div class="space-y-3">
    <div class="flex flex-wrap gap-2" role="group" aria-label="Categorías">
      <button type="button" :class="selected === null ? 'chip-on' : 'chip-off'" :aria-pressed="selected === null" @click="emit('select', null)">
        Todos
      </button>
      <button
        v-for="category in roots"
        :key="category.id"
        type="button"
        :class="activeRoot?.id === category.id ? 'chip-on' : 'chip-off'"
        :aria-pressed="activeRoot?.id === category.id"
        @click="emit('select', category.id)"
      >
        {{ category.label }}
      </button>
    </div>
    <div v-if="children.length" class="flex flex-wrap gap-2 border-l-2 border-zinc-800 pl-3" role="group" :aria-label="`Subcategorías de ${activeRoot?.label}`">
      <button
        type="button"
        class="text-[11px] font-bold uppercase tracking-wider transition-colors"
        :class="selected === activeRoot?.id ? 'text-white underline underline-offset-4' : 'text-zinc-500 hover:text-white'"
        @click="emit('select', activeRoot?.id ?? null)"
      >
        Todo {{ activeRoot?.label }}
      </button>
      <button
        v-for="child in children"
        :key="child.id"
        type="button"
        class="text-[11px] font-bold uppercase tracking-wider transition-colors"
        :class="selected === child.id ? 'text-white underline underline-offset-4' : 'text-zinc-500 hover:text-white'"
        @click="emit('select', child.id)"
      >
        {{ child.label }}
      </button>
    </div>
  </div>
</template>
