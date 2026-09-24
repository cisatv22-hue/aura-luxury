import { NotFoundError, ServiceError } from '../../application/errors.js'

export class HttpClient {
  /**
   * @param {string} baseUrl
   * @param {(input: string, init?: RequestInit) => Promise<Response>} [fetchImpl]
   */
  constructor(baseUrl, fetchImpl = (input, init) => fetch(input, init)) {
    this.baseUrl = baseUrl
    this.fetchImpl = fetchImpl
  }

  /**
   * @param {string} path
   * @param {Record<string, string | number | null | undefined>} [query]
   */
  url(path, query = {}) {
    const params = new URLSearchParams()
    for (const [key, value] of Object.entries(query)) {
      if (value !== null && value !== undefined && value !== '') params.set(key, String(value))
    }
    const search = params.toString()
    return `${this.baseUrl}${path}${search ? `?${search}` : ''}`
  }

  /**
   * @template T
   * @param {string} path
   * @param {Record<string, string | number | null | undefined>} [query]
   * @param {AbortSignal} [signal]
   * @returns {Promise<T>}
   */
  async get(path, query = {}, signal) {
    const response = await this.fetchImpl(this.url(path, query), {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
      signal,
    })
    if (!response.ok) throw await this.toError(response)
    return response.json()
  }

  /** @param {Response} response */
  async toError(response) {
    let code = 'http_error'
    let message = 'Ocurrió un error inesperado. Intenta de nuevo en unos minutos.'
    try {
      const body = await response.json()
      code = body.error?.code ?? code
      message = body.error?.message ?? message
    } catch {
      // Non-JSON error page (proxy, Apache): keep the generic message.
    }
    if (response.status === 404) return new NotFoundError(message)
    return new ServiceError(message, response.status, code)
  }
}
