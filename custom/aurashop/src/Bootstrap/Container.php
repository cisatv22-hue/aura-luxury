<?php

namespace AuraShop\Bootstrap;

use AuraShop\Application\Port\CatalogReader;
use AuraShop\Application\Port\ProductImages;
use AuraShop\Application\Port\StoreSettings;
use AuraShop\Application\UseCase\Catalog\GetProduct;
use AuraShop\Application\UseCase\Catalog\ListCategories;
use AuraShop\Application\UseCase\Catalog\ListProducts;
use AuraShop\Application\UseCase\Store\GetStoreConfig;
use AuraShop\Infrastructure\Dolibarr\DolibarrCatalogReader;
use AuraShop\Infrastructure\Dolibarr\DolibarrProductImages;
use AuraShop\Infrastructure\Dolibarr\DolibarrStoreSettings;
use AuraShop\Infrastructure\Image\GdThumbnailer;
use AuraShop\Infrastructure\Http\CatalogPresenter;
use AuraShop\Infrastructure\Http\Controller\CatalogController;
use AuraShop\Infrastructure\Http\Controller\ImageController;
use AuraShop\Infrastructure\Http\Controller\StoreController;
use AuraShop\Infrastructure\Http\Kernel;
use AuraShop\Infrastructure\Http\Router;

/**
 * Composition root: the only place that knows which adapter implements each port.
 */
final class Container
{
	/** @var array<string, object> */
	private array $instances = array();

	public function __construct(
		private readonly \DoliDB $db,
		private readonly \Conf $conf
	) {
	}

	public function apiBaseUrl(): string
	{
		return dol_buildpath('/aurashop/api/index.php', 1);
	}

	public function kernel(): Kernel
	{
		return $this->shared(Kernel::class, fn () => new Kernel($this->router()));
	}

	public function router(): Router
	{
		return $this->shared(Router::class, function () {
			$router = new Router();
			$catalog = $this->catalogController();
			$store = new StoreController(new GetStoreConfig($this->storeSettings()));
			$images = new ImageController($this->productImages());

			$router->add('GET', '/v1/config', array($store, 'config'));
			$router->add('GET', '/v1/catalog/products', array($catalog, 'products'));
			$router->add('GET', '/v1/catalog/products/{ref}', array($catalog, 'product'));
			$router->add('GET', '/v1/catalog/categories', array($catalog, 'categories'));
			$router->add('GET', '/v1/images/{ref}/{file}', array($images, 'show'));

			return $router;
		});
	}

	public function getProduct(): GetProduct
	{
		return new GetProduct($this->catalogReader());
	}

	public function catalogPresenter(): CatalogPresenter
	{
		return new CatalogPresenter($this->apiBaseUrl());
	}

	public function storeSettings(): StoreSettings
	{
		return $this->shared(StoreSettings::class, fn () => new DolibarrStoreSettings($this->conf->currency));
	}

	private function catalogController(): CatalogController
	{
		$reader = $this->catalogReader();

		return new CatalogController(
			new ListProducts($reader),
			new GetProduct($reader),
			new ListCategories($reader),
			$this->catalogPresenter()
		);
	}

	private function catalogReader(): CatalogReader
	{
		return $this->shared(CatalogReader::class, function () {
			$warehouse = getDolGlobalInt('AURASHOP_SALES_WAREHOUSE');

			return new DolibarrCatalogReader(
				$this->db,
				$this->productImages(),
				$this->conf->currency,
				$warehouse > 0 ? $warehouse : null
			);
		});
	}

	private function productImages(): ProductImages
	{
		return $this->shared(ProductImages::class, fn () => new DolibarrProductImages(
			$this->db,
			(string) $this->conf->product->multidir_output[$this->conf->entity],
			new GdThumbnailer(DOL_DATA_ROOT.'/aurashop/cache/images')
		));
	}

	/**
	 * @template T of object
	 * @param class-string<T> $id
	 * @param callable(): T   $factory
	 * @return T
	 */
	private function shared(string $id, callable $factory): object
	{
		return $this->instances[$id] ??= $factory();
	}
}
