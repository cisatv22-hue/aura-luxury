<?php

namespace AuraShop\Domain\Catalog;

final class CatalogQuery
{
	public const MAX_PER_PAGE = 48;
	public const DEFAULT_PER_PAGE = 24;
	private const MAX_SEARCH_LENGTH = 100;

	public readonly ?int $categoryId;
	public readonly ?string $search;
	public readonly ProductSort $sort;
	public readonly int $page;
	public readonly int $perPage;

	public function __construct(
		?int $categoryId = null,
		?string $search = null,
		ProductSort $sort = ProductSort::Newest,
		int $page = 1,
		int $perPage = self::DEFAULT_PER_PAGE
	) {
		$this->categoryId = ($categoryId !== null && $categoryId > 0) ? $categoryId : null;
		$search = $search === null ? '' : trim($search);
		$this->search = $search === '' ? null : mb_substr($search, 0, self::MAX_SEARCH_LENGTH);
		$this->sort = $sort;
		$this->page = max(1, $page);
		$this->perPage = min(self::MAX_PER_PAGE, max(1, $perPage));
	}

	public function offset(): int
	{
		return ($this->page - 1) * $this->perPage;
	}
}
