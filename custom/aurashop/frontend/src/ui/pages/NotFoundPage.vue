<script setup lang="ts">
import { catalogLink } from '@/router'
import { usePageTitle } from '@/ui/composables/usePageTitle'
import StateMessage from '@/ui/design-system/StateMessage.vue'

usePageTitle('Página no encontrada')
</script>

<template>
  <StateMessage icon="fa-compass" title="Esta página no existe" text="El enlace puede estar incompleto o la página se movió.">
    <RouterLink :to="catalogLink()" class="btn-primary">Ir al catálogo</RouterLink>
  </StateMessage>
</template>
