export class NotFoundError extends Error {
  constructor(message = 'No encontrado') {
    super(message)
    this.name = 'NotFoundError'
  }
}

export class ServiceError extends Error {
  /**
   * @param {string} message
   * @param {number} status
   * @param {string} code
   */
  constructor(message, status, code) {
    super(message)
    this.name = 'ServiceError'
    this.status = status
    this.code = code
  }
}

/** @param {unknown} error */
export function isAbort(error) {
  return error instanceof DOMException && error.name === 'AbortError'
}

/** @param {unknown} error */
export function userMessage(error) {
  if (error instanceof ServiceError) return error.message
  return 'No pudimos conectar con la tienda. Revisa tu conexión e intenta de nuevo.'
}
