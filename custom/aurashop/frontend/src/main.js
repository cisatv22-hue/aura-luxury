import { createApp } from 'vue'
import { containerKey, createContainer } from './di/container.js'
import { readRuntimeConfig } from './infrastructure/runtime-config.js'
import { createShopRouter } from './router/index.js'
import { createShopStore, shopKey } from './stores/shop.js'
import App from './ui/App.js'

const config = readRuntimeConfig()
const container = createContainer(config)

createApp(App)
  .provide(containerKey, container)
  .provide(shopKey, createShopStore(container))
  .use(createShopRouter(config.routerBase))
  .mount('#app')
