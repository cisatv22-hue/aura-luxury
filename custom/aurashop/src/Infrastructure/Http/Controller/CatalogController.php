<?php

namespace AuraShop\Infrastructure\Http\Controller;

use AuraShop\Application\UseCase\Catalog\GetProduct;
use AuraShop\Application\UseCase\Catalog\ListCategories;
use AuraShop\Application\UseCase\Catalog\ListProducts;
use AuraShop\Domain\Catalog\CatalogQuery;
use AuraShop\Domain\Catalog\ProductSort;
use AuraShop\Infrastructure\Http\CatalogPresenter;
use AuraShop\Infrastructure\Http\Request;
use AuraShop\Infrastructure\Http\Response;

final class CatalogController
{
	private const CACHE = array('Cache-Control' => 'public, max-age=60');

	public function __construct(
		private readonly ListProducts $listProducts,
		private readonly GetProduct $getProduct,
		private readonly ListCategories $listCategories,
		private readonly CatalogPresenter $presenter
	) {
	}

	public function products(Request $request): Response
	{
		$query = new CatalogQuery(
			$request->queryInt('category'),
			$request->queryString('q'),
			ProductSort::fromInput($request->queryString('sort')),
			$request->queryInt('page') ?? 1,
			$request->queryInt('perPage') ?? CatalogQuery::DEFAULT_PER_PAGE
		);

		return Response::json($this->presenter->productPage(($this->listProducts)($query)), 200, self::CACHE);
	}

	/**
	 * @param array{ref: string} $params
	 */
	public function product(Request $request, array $params): Response
	{
		return Response::json($this->presenter->detail(($this->getProduct)($params['ref'])), 200, self::CACHE);
	}

	public function categories(Request $request): Response
	{
		return Response::json(array('items' => $this->presenter->categories(($this->listCategories)())), 200, self::CACHE);
	}
}
