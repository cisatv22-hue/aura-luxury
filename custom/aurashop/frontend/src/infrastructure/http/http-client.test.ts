import { describe, expect, it } from 'vitest'
import { NotFoundError, ServiceError } from '@/application/errors'
import { HttpClient } from './http-client'

describe('HttpClient', () => {
  const json = (status: number, body: unknown) =>
    Promise.resolve(new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } }))

  it('builds query strings without empty values', () => {
    const client = new HttpClient('/api/index.php')
    expect(client.url('/v1/catalog/products', { q: 'a b', category: null, page: 2, sort: '' })).toBe(
      '/api/index.php/v1/catalog/products?q=a+b&page=2',
    )
  })

  it('maps 404 to NotFoundError', async () => {
    const client = new HttpClient('', () => json(404, { error: { code: 'not_found', message: 'No está' } }))
    await expect(client.get('/x')).rejects.toBeInstanceOf(NotFoundError)
  })

  it('keeps the API message on other errors', async () => {
    const client = new HttpClient('', () => json(503, { error: { code: 'store_disabled', message: 'La tienda no está disponible.' } }))
    await expect(client.get('/x')).rejects.toEqual(new ServiceError('La tienda no está disponible.', 503, 'store_disabled'))
  })

  it('survives non-JSON error pages', async () => {
    const client = new HttpClient('', () => Promise.resolve(new Response('<html>502</html>', { status: 502 })))
    await expect(client.get('/x')).rejects.toBeInstanceOf(ServiceError)
  })
})
