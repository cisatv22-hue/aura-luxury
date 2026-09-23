<script setup lang="ts">
import { onMounted } from 'vue'
import { useShopStore } from '@/stores/shop'
import AppFooter from './layout/AppFooter.vue'
import AppHeader from './layout/AppHeader.vue'

const shop = useShopStore()
onMounted(() => void shop.load())
</script>

<template>
  <div class="flex min-h-screen flex-col">
    <AppHeader />
    <main class="flex-grow">
      <RouterView />
    </main>
    <AppFooter />
  </div>
</template>
