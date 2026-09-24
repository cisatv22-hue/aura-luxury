<?php

namespace AuraShop\Infrastructure\Http;

use AuraShop\Domain\Catalog\Category;
use AuraShop\Domain\Catalog\ProductDetail;
use AuraShop\Domain\Catalog\ProductSummary;
use AuraShop\Domain\Catalog\Variant;
use AuraShop\Domain\Catalog\VariantAttribute;
use AuraShop\Domain\Shared\Money;
use AuraShop\Domain\Shared\Page;

/**
 * Maps domain objects to the public JSON contract (v1). Changing field names here is a breaking change for the SPA.
 */
final class CatalogPresenter
{
	public function __construct(private readonly string $apiBaseUrl)
	{
	}

	/**
	 * @param Page<ProductSummary> $page
	 */
	public function productPage(Page $page): array
	{
		return array(
			'items' => array_map(fn (ProductSummary $p) => $this->summary($p), $page->items),
			'pagination' => array(
				'page' => $page->page,
				'perPage' => $page->perPage,
				'total' => $page->total,
				'totalPages' => $page->totalPages(),
			),
		);
	}

	public function summary(ProductSummary $product): array
	{
		return array(
			'ref' => $product->ref,
			'label' => $product->label,
			'summary' => $product->summary,
			'price' => $this->money($product->price),
			'image' => $product->mainImage !== null ? $this->image($product->ref, $product->mainImage) : null,
			'categories' => array_map(fn (Category $c) => $this->categoryRef($c), $product->categories),
			'availability' => $product->availability->value,
			'hasVariants' => $product->hasVariants,
		);
	}

	public function detail(ProductDetail $product): array
	{
		return array(
			'ref' => $product->ref,
			'label' => $product->label,
			'summary' => $product->summary,
			'descriptionHtml' => $product->descriptionHtml,
			'price' => $this->money($product->price),
			'priceFrom' => $this->money($product->priceFrom()),
			'images' => array_map(fn (string $file) => $this->image($product->ref, $file), $product->images),
			'categories' => array_map(fn (Category $c) => $this->categoryRef($c), $product->categories),
			'availability' => $product->availability->value,
			'attributes' => array_map(static fn (VariantAttribute $a) => array(
				'code' => $a->code,
				'label' => $a->label,
				'values' => $a->values,
			), $product->attributes),
			'variants' => array_map(fn (Variant $v) => array(
				'ref' => $v->ref,
				'options' => (object) $v->options,
				'price' => $this->money($v->price),
				'availability' => $v->availability->value,
			), $product->variants),
		);
	}

	/**
	 * @param list<Category> $categories
	 */
	public function categories(array $categories): array
	{
		return array_map(static fn (Category $c) => array(
			'id' => $c->id,
			'label' => $c->label,
			'parentId' => $c->parentId,
			'description' => $c->description,
		), $categories);
	}

	private function categoryRef(Category $category): array
	{
		return array('id' => $category->id, 'label' => $category->label);
	}

	private function money(Money $money): array
	{
		return array('amount' => $money->amount, 'currency' => $money->currency);
	}

	private function image(string $ref, string $file): array
	{
		$base = $this->apiBaseUrl.'/v1/images/'.rawurlencode($ref).'/'.rawurlencode($file);

		return array(
			'mini' => $base.'?size=mini',
			'card' => $base.'?size=card',
			'full' => $base,
		);
	}
}
