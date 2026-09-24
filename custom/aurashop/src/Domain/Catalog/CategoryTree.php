<?php

namespace AuraShop\Domain\Catalog;

final class CategoryTree
{
	/** @var array<int, Category> */
	private array $byId = array();

	/** @var array<int, list<int>> */
	private array $children = array();

	/**
	 * @param list<Category> $categories
	 */
	public function __construct(array $categories)
	{
		foreach ($categories as $category) {
			$this->byId[$category->id] = $category;
		}
		foreach ($categories as $category) {
			if ($category->parentId !== null && isset($this->byId[$category->parentId])) {
				$this->children[$category->parentId][] = $category->id;
			}
		}
	}

	/**
	 * @return list<Category>
	 */
	public function all(): array
	{
		return array_values($this->byId);
	}

	public function has(int $id): bool
	{
		return isset($this->byId[$id]);
	}

	/**
	 * The category itself plus all its descendants, so filtering by "Joyeria" also shows "Cadenas".
	 *
	 * @return list<int>
	 */
	public function withDescendants(int $id): array
	{
		if (!$this->has($id)) {
			return array();
		}
		$result = array();
		$pending = array($id);
		while ($pending) {
			$current = array_shift($pending);
			if (in_array($current, $result, true)) {
				continue;
			}
			$result[] = $current;
			foreach ($this->children[$current] ?? array() as $childId) {
				$pending[] = $childId;
			}
		}

		return $result;
	}
}
