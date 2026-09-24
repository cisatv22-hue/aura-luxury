import { catalogLink } from '../../router/index.js'
import { usePageTitle } from '../composables/usePageTitle.js'
import StateMessage from '../design-system/StateMessage.js'

export default {
  name: 'NotFoundPage',
  components: { StateMessage },
  setup() {
    usePageTitle('Página no encontrada')
    return { catalogLink }
  },
  template: `
    <StateMessage icon="fa-compass" title="Esta página no existe" text="El enlace puede estar incompleto o la página se movió.">
      <RouterLink :to="catalogLink()" class="btn-primary">Ir al catálogo</RouterLink>
    </StateMessage>
  `,
}
