<?php
/**
 * One row of the link index: a link or display in a host's content.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

/**
 * One row of `{prefix}affibloom_link_locations` (F21 D1): where a link sits (host, source, occurrence), what it is
 * (link and display type, product) and what it says (URL, anchor text, domain, flags, Amazon IDs, platform).
 * `original_url`, `is_ignored` and `assigned_product_id` are kept from the row with the same key on a rescan (D10);
 * the repository fills them, the indexer leaves them at their defaults.
 */
final class LinkLocation {

	/**
	 * Describes the row.
	 *
	 * @param int         $host_id             The post the link is in.
	 * @param string      $host_url            Its permalink when indexed.
	 * @param string      $source              Where in the host: "content", later "acf:<key>", "elementor", ….
	 * @param int         $occurrence          1 for the first link with this URL in this host and source, 2 for the next.
	 * @param int         $product_id          The product it is tied to, 0 when none.
	 * @param string      $link_type           LinkType's value.
	 * @param string      $display_type        DisplayType's label.
	 * @param string      $url                 The link as written.
	 * @param string      $url_md5             LocationKey::md5() of the URL.
	 * @param string|null $anchor_text         The text, null when there is none.
	 * @param string|null $domain              The host without "www.", null for product and internal links.
	 * @param string|null $asin                An Amazon link's ASIN.
	 * @param string|null $tracking_id         An Amazon link's tag.
	 * @param string      $country             An Amazon link's store, else "us".
	 * @param bool        $no_follow           `rel` is exactly "nofollow" (R2).
	 * @param bool        $new_window          `target` is exactly "_blank" (R2).
	 * @param string|null $affiliate_platform  AffiliatePlatform::of()'s answer.
	 * @param string      $detected_at         When it was indexed, UTC "Y-m-d H:i:s".
	 * @param string|null $display_ref         "group:<id>" or "table:<id>" for a group display (35B).
	 * @param string      $original_url        The URL the first time this location was indexed.
	 * @param bool        $is_ignored          Dismissed on the Opportunities screens (F22).
	 * @param int         $assigned_product_id The product an editor tied the link to (F22 N1-A), 0 when none.
	 */
	public function __construct(
		public readonly int $host_id,
		public readonly string $host_url,
		public readonly string $source,
		public readonly int $occurrence,
		public readonly int $product_id,
		public readonly string $link_type,
		public readonly string $display_type,
		public readonly string $url,
		public readonly string $url_md5,
		public readonly ?string $anchor_text = null,
		public readonly ?string $domain = null,
		public readonly ?string $asin = null,
		public readonly ?string $tracking_id = null,
		public readonly string $country = 'us',
		public readonly bool $no_follow = false,
		public readonly bool $new_window = false,
		public readonly ?string $affiliate_platform = null,
		public readonly string $detected_at = '',
		public readonly ?string $display_ref = null,
		public readonly string $original_url = '',
		public readonly bool $is_ignored = false,
		public readonly int $assigned_product_id = 0,
	) {}

	/**
	 * The row's identity across rescans (D4): host, source, URL and occurrence.
	 */
	public function key(): string {
		return LocationKey::of( $this->host_id, $this->source, $this->url_md5, $this->occurrence );
	}

	/**
	 * The same row with what a rescan keeps from the stored one (D10): the first URL, a dismissal, an assignment. An
	 * assignment ties the row to its product whatever the link resolves to (F22 N1-A; Task 35I's pinned rows).
	 *
	 * @param string $original_url        The stored row's first URL.
	 * @param bool   $is_ignored          Whether it was dismissed.
	 * @param int    $assigned_product_id The product an editor tied it to.
	 */
	public function keeping( string $original_url, bool $is_ignored, int $assigned_product_id ): self {
		$values                        = get_object_vars( $this );
		$values['original_url']        = '' !== $original_url ? $original_url : $this->url;
		$values['is_ignored']          = $is_ignored;
		$values['assigned_product_id'] = $assigned_product_id;
		if ( $assigned_product_id > 0 ) {
			$values['product_id'] = $assigned_product_id;
			$values['link_type']  = LinkType::Product->value;
			$values['domain']     = null;
		}

		return new self( ...$values );
	}
}
