import { describe, expect, it, vi } from 'vitest'
import type { ProductPage } from '@/domain/catalog'
import { createCatalogUseCases, normalizeQuery } from './catalog'
import type { CatalogPort } from './ports'

const emptyPage: ProductPage = { items: [], pagination: { page: 1, perPage: 24, total: 0, totalPages: 0 } }

function fakePort(overrides: Partial<CatalogPort> = {}): CatalogPort {
  return {
    listProducts: vi.fn().mockResolvedValue(emptyPage),
    getProduct: vi.fn(),
    listCategories: vi.fn().mockResolvedValue([]),
    ...overrides,
  }
}

describe('normalizeQuery', () => {
  it('clamps pagination and drops empty filters', () => {
    expect(normalizeQuery({ category: 0, q: '  ', page: -2, perPage: 500 })).toEqual({
      category: null,
      q: undefined,
      sort: 'newest',
      page: 1,
      perPage: 48,
    })
  })
})

describe('catalog use cases', () => {
  it('sends a normalized query to the port', async () => {
    const port = fakePort()
    await createCatalogUseCases(port).browse({ q: ' cadena ' })
    expect(port.listProducts).toHaveBeenCalledWith(expect.objectContaining({ q: 'cadena', page: 1 }), undefined)
  })

  it('caches categories but retries after a failure', async () => {
    const listCategories = vi.fn().mockRejectedValueOnce(new Error('offline')).mockResolvedValue([])
    const catalog = createCatalogUseCases(fakePort({ listCategories }))

    await expect(catalog.categories()).rejects.toThrow('offline')
    await catalog.categories()
    await catalog.categories()
    expect(listCategories).toHaveBeenCalledTimes(2)
  })
})
