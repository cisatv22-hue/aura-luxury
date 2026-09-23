import { NotFoundError, ServiceError } from '@/application/errors'

type QueryValue = string | number | null | undefined
type FetchLike = (input: string, init?: RequestInit) => Promise<Response>

export class HttpClient {
  constructor(
    private readonly baseUrl: string,
    private readonly fetchImpl: FetchLike = (input, init) => fetch(input, init),
  ) {}

  url(path: string, query: Record<string, QueryValue> = {}): string {
    const params = new URLSearchParams()
    for (const [key, value] of Object.entries(query)) {
      if (value !== null && value !== undefined && value !== '') params.set(key, String(value))
    }
    const search = params.toString()
    return `${this.baseUrl}${path}${search ? `?${search}` : ''}`
  }

  async get<T>(path: string, query: Record<string, QueryValue> = {}, signal?: AbortSignal): Promise<T> {
    const response = await this.fetchImpl(this.url(path, query), {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
      signal,
    })
    if (!response.ok) throw await this.toError(response)
    return (await response.json()) as T
  }

  private async toError(response: Response): Promise<Error> {
    let code = 'http_error'
    let message = 'Ocurrió un error inesperado. Intenta de nuevo en unos minutos.'
    try {
      const body = (await response.json()) as { error?: { code?: string; message?: string } }
      code = body.error?.code ?? code
      message = body.error?.message ?? message
    } catch {
      // Non-JSON error page (proxy, Apache): keep the generic message.
    }
    if (response.status === 404) return new NotFoundError(message)
    return new ServiceError(message, response.status, code)
  }
}
