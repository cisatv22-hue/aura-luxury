export class NotFoundError extends Error {
  constructor(message = 'No encontrado') {
    super(message)
    this.name = 'NotFoundError'
  }
}

export class ServiceError extends Error {
  constructor(
    message: string,
    readonly status: number,
    readonly code: string,
  ) {
    super(message)
    this.name = 'ServiceError'
  }
}

export function isAbort(error: unknown): boolean {
  return error instanceof DOMException && error.name === 'AbortError'
}

export function userMessage(error: unknown): string {
  if (error instanceof ServiceError) return error.message
  return 'No pudimos conectar con la tienda. Revisa tu conexión e intenta de nuevo.'
}
