import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from '@/ui/App.vue'
import { containerKey, createContainer } from '@/di/container'
import { readRuntimeConfig } from '@/infrastructure/runtime-config'
import { createShopRouter } from '@/router'
import './style.css'

const config = readRuntimeConfig()

createApp(App)
  .provide(containerKey, createContainer(config))
  .use(createPinia())
  .use(createShopRouter(config.routerBase))
  .mount('#app')
