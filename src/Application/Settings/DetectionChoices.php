<?php
/**
 * What Custom Link Detection can scan on this site.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Settings;

/**
 * What Custom Link Detection can scan on this site (Settings → General, Task 9I): the post types and, where ACF is
 * active, the ACF fields. The WordPress side answers; the settings service refuses any choice not listed here.
 */
interface DetectionChoices {

	/**
	 * The post types that can be chosen: every public-or-not type except the always-scanned ones and Lasso's excluded
	 * list (F9 R25).
	 *
	 * @return array<string, string> Post type name => label.
	 */
	public function post_types(): array;

	/**
	 * The ACF fields that can be chosen; empty when ACF is not active (F9 R26).
	 *
	 * @return array<string, string> Field name => label.
	 */
	public function custom_fields(): array;

	/**
	 * Whether ACF is active, so the card shows its second field.
	 */
	public function has_acf(): bool;
}
