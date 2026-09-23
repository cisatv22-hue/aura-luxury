import type { StorePort } from '@/application/ports'
import type { StoreConfig } from '@/domain/store'
import type { HttpClient } from './http/http-client'

/** Uses the config index.php already rendered into the page: no extra request on startup. */
export class InjectedStoreAdapter implements StorePort {
  constructor(private readonly config: StoreConfig) {}

  getConfig(): Promise<StoreConfig> {
    return Promise.resolve(this.config)
  }
}

/** Fallback for the Vite dev server, where no config is injected. */
export class HttpStoreAdapter implements StorePort {
  constructor(private readonly http: HttpClient) {}

  getConfig(): Promise<StoreConfig> {
    return this.http.get<StoreConfig>('/v1/config')
  }
}
