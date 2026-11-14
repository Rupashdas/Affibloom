<?php
/**
 * The post types and ACF fields Custom Link Detection offers, from WordPress.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Application\Settings\DetectionChoices;

/**
 * The post types and ACF fields Custom Link Detection offers, from WordPress (F9 R25-R26, Lasso's
 * Lasso_Helper::get_cpt_support and get_acf_fields).
 */
final class WpDetectionChoices implements DetectionChoices {

	/**
	 * Post types that are scanned always (`post`, `page`) or are not content (Lasso's excluded list, extended with
	 * Affibloom's own product type and the Groups it keeps nothing in).
	 */
	private const EXCLUDED = array(
		'post',
		'page',
		'wp_block',
		'attachment',
		'revision',
		'nav_menu_item',
		'custom_css',
		'customize_changeset',
		'oembed_cache',
		'user_request',
		'acf-field-group',
		'acf-field',
		'wpadc',
		'wpcf7_contact_form',
		'elementor_snippet',
		'elementor_font',
		'elementor_icons',
		'lasso-urls',
		'pretty-link',
		'affiliate_url',
		ProductPostType::NAME,
	);

	/**
	 * The post types that can be chosen, as name => label, in label order.
	 *
	 * @return array<string, string>
	 */
	public function post_types(): array {
		$types = array();
		foreach ( get_post_types( array(), 'objects' ) as $name => $type ) {
			if ( in_array( $name, self::EXCLUDED, true ) ) {
				continue;
			}
			$types[ $name ] = '' !== $type->label ? $type->label : $name;
		}
		asort( $types, SORT_NATURAL | SORT_FLAG_CASE );

		return $types;
	}

	/**
	 * Every ACF field, as name => label.
	 *
	 * @return array<string, string>
	 */
	public function custom_fields(): array {
		if ( ! $this->has_acf() ) {
			return array();
		}

		$fields = array();
		foreach ( get_posts(
			array(
				'post_type'      => 'acf-field',
				'post_status'    => 'publish',
				'posts_per_page' => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- every ACF field, once, for a settings page.
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		) as $field ) {
			$name = $field->post_excerpt;
			if ( '' !== $name ) {
				$fields[ $name ] = $field->post_title;
			}
		}

		return $fields;
	}

	/**
	 * Whether ACF is active.
	 */
	public function has_acf(): bool {
		return post_type_exists( 'acf-field' );
	}
}
