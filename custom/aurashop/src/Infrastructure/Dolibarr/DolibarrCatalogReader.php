<?php

namespace AuraShop\Infrastructure\Dolibarr;

use AuraShop\Application\Port\CatalogReader;
use AuraShop\Application\Port\ProductImages;
use AuraShop\Domain\Catalog\Availability;
use AuraShop\Domain\Catalog\CatalogQuery;
use AuraShop\Domain\Catalog\Category;
use AuraShop\Domain\Catalog\ProductDetail;
use AuraShop\Domain\Catalog\ProductSort;
use AuraShop\Domain\Catalog\ProductSummary;
use AuraShop\Domain\Catalog\Variant;
use AuraShop\Domain\Catalog\VariantAttribute;
use AuraShop\Domain\Shared\Money;
use AuraShop\Domain\Shared\Page;

/**
 * Read model over native Dolibarr tables. Writes never go through here.
 *
 * Variant children (llx_product_attribute_combination.fk_product_child) are hidden from the listing;
 * they are exposed as variants of their parent.
 */
final class DolibarrCatalogReader implements CatalogReader
{
	private const TYPE_SERVICE = 1;
	private const SUMMARY_LENGTH = 160;

	/** @var list<Category>|null */
	private ?array $categories = null;

	public function __construct(
		private readonly \DoliDB $db,
		private readonly ProductImages $images,
		private readonly DolibarrHtmlSanitizer $sanitizer,
		private readonly string $currency,
		private readonly ?int $salesWarehouseId
	) {
	}

	public function categories(): array
	{
		if ($this->categories !== null) {
			return $this->categories;
		}
		$sql = "SELECT c.rowid, c.label, c.fk_parent, c.description";
		$sql .= " FROM ".MAIN_DB_PREFIX."categorie as c";
		$sql .= " WHERE c.entity IN (".getEntity('category').")";
		$sql .= " AND c.type = 0 AND c.visible = 1";
		$sql .= " ORDER BY c.position ASC, c.label ASC";

		$result = array();
		foreach ($this->fetchAll($sql) as $row) {
			$result[] = new Category(
				(int) $row->rowid,
				(string) $row->label,
				((int) $row->fk_parent) > 0 ? (int) $row->fk_parent : null,
				dol_string_nohtmltag((string) $row->description)
			);
		}

		return $this->categories = $result;
	}

	public function search(CatalogQuery $query, ?array $categoryIds): Page
	{
		$where = $this->listingWhere($query, $categoryIds);

		$countRows = $this->fetchAll("SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."product as p".$where);
		$total = $countRows ? (int) $countRows[0]->nb : 0;
		if ($total === 0) {
			return new Page(array(), 0, $query->page, $query->perPage);
		}

		$combination = MAIN_DB_PREFIX."product_attribute_combination";
		$minVariantPrice = "(SELECT MIN(ch.price_ttc) FROM ".$combination." as pac JOIN ".MAIN_DB_PREFIX."product as ch ON ch.rowid = pac.fk_product_child"
			." WHERE pac.fk_product_parent = p.rowid AND ch.tosell = 1)";
		$sql = "SELECT p.rowid, p.ref, p.label, p.description, p.price_ttc, p.fk_product_type,";
		$sql .= " (SELECT COUNT(*) FROM ".$combination." as pac JOIN ".MAIN_DB_PREFIX."product as ch ON ch.rowid = pac.fk_product_child";
		$sql .= "   WHERE pac.fk_product_parent = p.rowid AND ch.tosell = 1) as nb_variants,";
		$sql .= " ".$minVariantPrice." as min_variant_price,";
		$sql .= " COALESCE(".$minVariantPrice.", p.price_ttc) as sort_price,";
		$sql .= " ".$this->stockExpr('p')." as own_qty,";
		$sql .= " (SELECT COALESCE(SUM(".$this->stockExpr('ch')."), 0) FROM ".$combination." as pac JOIN ".MAIN_DB_PREFIX."product as ch ON ch.rowid = pac.fk_product_child";
		$sql .= "   WHERE pac.fk_product_parent = p.rowid AND ch.tosell = 1) as variants_qty";
		$sql .= " FROM ".MAIN_DB_PREFIX."product as p";
		$sql .= $where;
		$sql .= $this->orderBy($query->sort);
		$sql .= $this->db->plimit($query->perPage, $query->offset());

		$rows = $this->fetchAll($sql);
		$categoriesByProduct = $this->categoriesOf(array_map(static fn ($r) => (int) $r->rowid, $rows));

		$items = array();
		foreach ($rows as $row) {
			$hasVariants = ((int) $row->nb_variants) > 0;
			$price = $hasVariants && $row->min_variant_price !== null ? $row->min_variant_price : $row->price_ttc;
			$images = $this->images->list((string) $row->ref);
			$items[] = new ProductSummary(
				(string) $row->ref,
				(string) $row->label,
				$this->summary((string) $row->description),
				Money::fromDecimal((string) $price, $this->currency),
				$images[0] ?? null,
				$categoriesByProduct[(int) $row->rowid] ?? array(),
				$this->availability((int) $row->fk_product_type, (float) ($hasVariants ? $row->variants_qty : $row->own_qty)),
				$hasVariants
			);
		}

		return new Page($items, $total, $query->page, $query->perPage);
	}

	public function findByRef(string $ref): ?ProductDetail
	{
		$sql = "SELECT p.rowid, p.ref, p.label, p.description, p.price_ttc, p.fk_product_type,";
		$sql .= " ".$this->stockExpr('p')." as own_qty";
		$sql .= " FROM ".MAIN_DB_PREFIX."product as p";
		$sql .= " WHERE p.ref = '".$this->db->escape($ref)."'";
		$sql .= " AND p.entity IN (".getEntity('product').") AND p.tosell = 1";
		$sql .= " AND NOT EXISTS (SELECT 1 FROM ".MAIN_DB_PREFIX."product_attribute_combination as pc WHERE pc.fk_product_child = p.rowid)";
		$rows = $this->fetchAll($sql);
		if (!$rows) {
			return null;
		}
		$row = $rows[0];
		$productId = (int) $row->rowid;
		$type = (int) $row->fk_product_type;

		[$attributes, $variants] = $this->variantsOf($productId, $type);

		if ($variants) {
			$availability = Availability::OutOfStock;
			foreach ($variants as $variant) {
				if ($variant->availability === Availability::InStock
					|| ($variant->availability === Availability::LowStock && $availability === Availability::OutOfStock)) {
					$availability = $variant->availability;
				}
			}
		} else {
			$availability = $this->availability($type, (float) $row->own_qty);
		}

		$description = (string) $row->description;

		return new ProductDetail(
			(string) $row->ref,
			(string) $row->label,
			$this->summary($description),
			$this->sanitizer->sanitize($description),
			Money::fromDecimal((string) $row->price_ttc, $this->currency),
			$this->images->list((string) $row->ref),
			$this->categoriesOf(array($productId))[$productId] ?? array(),
			$availability,
			$attributes,
			$variants
		);
	}

	/**
	 * @param list<int>|null $categoryIds
	 */
	private function listingWhere(CatalogQuery $query, ?array $categoryIds): string
	{
		$sql = " WHERE p.entity IN (".getEntity('product').")";
		$sql .= " AND p.tosell = 1";
		$sql .= " AND NOT EXISTS (SELECT 1 FROM ".MAIN_DB_PREFIX."product_attribute_combination as pc WHERE pc.fk_product_child = p.rowid)";
		if ($categoryIds !== null) {
			$ids = implode(',', array_map('intval', $categoryIds));
			$sql .= " AND EXISTS (SELECT 1 FROM ".MAIN_DB_PREFIX."categorie_product as cp";
			$sql .= " WHERE cp.fk_product = p.rowid AND cp.fk_categorie IN (".$ids."))";
		}
		if ($query->search !== null) {
			$sql .= natural_search(array('p.ref', 'p.label', 'p.description'), $query->search);
		}

		return $sql;
	}

	private function orderBy(ProductSort $sort): string
	{
		return match ($sort) {
			ProductSort::PriceAsc => " ORDER BY sort_price ASC, p.rowid DESC",
			ProductSort::PriceDesc => " ORDER BY sort_price DESC, p.rowid DESC",
			ProductSort::Name => " ORDER BY p.label ASC",
			ProductSort::Newest => " ORDER BY p.datec DESC, p.rowid DESC",
		};
	}

	/**
	 * SQL expression with the physical stock of a product row, limited to the sales warehouse when configured.
	 */
	private function stockExpr(string $alias): string
	{
		if ($this->salesWarehouseId !== null) {
			return "(SELECT COALESCE(SUM(ps.reel), 0) FROM ".MAIN_DB_PREFIX."product_stock as ps"
				." WHERE ps.fk_product = ".$alias.".rowid AND ps.fk_entrepot = ".((int) $this->salesWarehouseId).")";
		}

		return "COALESCE(".$alias.".stock, 0)";
	}

	/**
	 * @return array{0: list<VariantAttribute>, 1: list<Variant>}
	 */
	private function variantsOf(int $parentId, int $type): array
	{
		$sql = "SELECT pac.rowid as combination_id, ch.ref, ch.price_ttc, ".$this->stockExpr('ch')." as qty";
		$sql .= " FROM ".MAIN_DB_PREFIX."product_attribute_combination as pac";
		$sql .= " JOIN ".MAIN_DB_PREFIX."product as ch ON ch.rowid = pac.fk_product_child";
		$sql .= " WHERE pac.fk_product_parent = ".$parentId." AND ch.tosell = 1";
		$sql .= " ORDER BY ch.rowid ASC";
		$children = $this->fetchAll($sql);
		if (!$children) {
			return array(array(), array());
		}

		$combinationIds = implode(',', array_map(static fn ($c) => (int) $c->combination_id, $children));
		$sql = "SELECT c2v.fk_prod_combination, a.ref as attr_code, a.label as attr_label, v.value";
		$sql .= " FROM ".MAIN_DB_PREFIX."product_attribute_combination2val as c2v";
		$sql .= " JOIN ".MAIN_DB_PREFIX."product_attribute as a ON a.rowid = c2v.fk_prod_attr";
		$sql .= " JOIN ".MAIN_DB_PREFIX."product_attribute_value as v ON v.rowid = c2v.fk_prod_attr_val";
		$sql .= " WHERE c2v.fk_prod_combination IN (".$combinationIds.")";
		$sql .= " ORDER BY a.position ASC, a.rowid ASC, v.position ASC, v.rowid ASC";

		$labels = array();
		$values = array();
		$options = array();
		foreach ($this->fetchAll($sql) as $row) {
			$code = (string) $row->attr_code;
			$labels[$code] = (string) $row->attr_label;
			$values[$code][(string) $row->value] = true;
			$options[(int) $row->fk_prod_combination][$code] = (string) $row->value;
		}

		$attributes = array();
		foreach ($labels as $code => $label) {
			$attributes[] = new VariantAttribute($code, $label, array_map('strval', array_keys($values[$code])));
		}

		$variants = array();
		foreach ($children as $child) {
			$variants[] = new Variant(
				(string) $child->ref,
				$options[(int) $child->combination_id] ?? array(),
				Money::fromDecimal((string) $child->price_ttc, $this->currency),
				$this->availability($type, (float) $child->qty)
			);
		}

		return array($attributes, $variants);
	}

	/**
	 * @param list<int> $productIds
	 * @return array<int, list<Category>>
	 */
	private function categoriesOf(array $productIds): array
	{
		if (!$productIds) {
			return array();
		}
		$byId = array();
		foreach ($this->categories() as $category) {
			$byId[$category->id] = $category;
		}

		$sql = "SELECT cp.fk_product, cp.fk_categorie FROM ".MAIN_DB_PREFIX."categorie_product as cp";
		$sql .= " WHERE cp.fk_product IN (".implode(',', array_map('intval', $productIds)).")";
		$result = array();
		foreach ($this->fetchAll($sql) as $row) {
			$category = $byId[(int) $row->fk_categorie] ?? null;
			if ($category !== null) {
				$result[(int) $row->fk_product][] = $category;
			}
		}

		return $result;
	}

	private function availability(int $productType, float $quantity): Availability
	{
		return $productType === self::TYPE_SERVICE ? Availability::InStock : Availability::fromQuantity($quantity);
	}

	private function summary(string $description): string
	{
		return dol_trunc(trim(dol_string_nohtmltag($description)), self::SUMMARY_LENGTH);
	}

	/**
	 * @return list<object>
	 */
	private function fetchAll(string $sql): array
	{
		$resql = $this->db->query($sql);
		if (!$resql) {
			throw new \RuntimeException('AuraShop catalog query failed: '.$this->db->lasterror());
		}
		$rows = array();
		while ($obj = $this->db->fetch_object($resql)) {
			$rows[] = $obj;
		}
		$this->db->free($resql);

		return $rows;
	}
}
