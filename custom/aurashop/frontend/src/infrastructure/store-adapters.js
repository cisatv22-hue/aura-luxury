/**
 * Uses the config index.php already rendered into the page: no extra request on startup.
 * @implements {import('../application/ports.js').StorePort}
 */
export class InjectedStoreAdapter {
  /** @param {import('../domain/store.js').StoreConfig} config */
  constructor(config) {
    this.config = config
  }

  getConfig() {
    return Promise.resolve(this.config)
  }
}

/**
 * Fallback when no config was injected.
 * @implements {import('../application/ports.js').StorePort}
 */
export class HttpStoreAdapter {
  /** @param {import('./http/http-client.js').HttpClient} http */
  constructor(http) {
    this.http = http
  }

  getConfig() {
    return this.http.get('/v1/config')
  }
}
