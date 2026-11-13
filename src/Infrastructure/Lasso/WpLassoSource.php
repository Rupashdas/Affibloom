<?php
/**
 * Reads Lasso's records from its tables.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\Lasso;

use Affibloom\Domain\Migration\LassoRecord;
use Affibloom\Domain\Migration\LassoSource;

/**
 * Lasso's published records from the database, with SELECTs only: the `lasso-urls` posts and the meta Lasso reads,
 * `lasso_url_details` and `lasso_amazon_products`. Works with Lasso active or not; a missing Lasso table reads as
 * empty, so a site that never had Lasso has no records.
 */
final class WpLassoSource implements LassoSource {

	/** Lasso's post type. */
	private const POST_TYPE = 'lasso-urls';

	/** Records read per query, to keep each IN list short. */
	private const CHUNK = 100;

	/** The post meta LassoRecordMapper reads. */
	private const META_KEYS = array(
		'lasso_custom_redirect',
		'lasso_final_url',
		'open_new_tab',
		'enable_nofollow',
		'enable_sponsored',
		'show_disclosure',
		'buy_btn_text',
		'second_btn_url',
		'second_btn_text',
		'open_new_tab2',
		'enable_nofollow2',
		'custom_theme',
		'badge_text',
		'price',
		'show_price',
		'affiliate_desc',
		'show_description',
		'disclosure_text',
		'lasso_custom_thumbnail',
		'lasso_thumbnail_url',
		'_thumbnail_id',
	);

	/**
	 * The IDs of Lasso's published records, oldest change first.
	 *
	 * @return list<int>
	 */
	public function ids(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- a one-time read of Lasso's posts.
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s ORDER BY post_modified ASC, ID ASC",
				self::POST_TYPE,
				'publish'
			)
		);

		return array_map( 'intval', $ids );
	}

	/**
	 * These records, in the order given.
	 *
	 * @param list<int> $ids From ids().
	 * @return list<LassoRecord>
	 */
	public function records( array $ids ): array {
		$records = array();
		foreach ( array_chunk( $ids, self::CHUNK ) as $chunk ) {
			array_push( $records, ...$this->chunk( $chunk ) );
		}

		return $records;
	}

	/**
	 * One chunk of records.
	 *
	 * @param list<int> $ids Up to CHUNK IDs.
	 * @return list<LassoRecord>
	 */
	private function chunk( array $ids ): array {
		global $wpdb;

		$in = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- one-time reads of Lasso's tables; $in holds only %d placeholders, filled by prepare().
		$posts = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title, post_date, post_modified FROM {$wpdb->posts} WHERE ID IN ( $in )", ...$ids ), OBJECT_K );

		$keys = implode( ',', array_fill( 0, count( self::META_KEYS ), '%s' ) );
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id IN ( $in ) AND meta_key IN ( $keys ) ORDER BY meta_id ASC",
				...array_merge( $ids, self::META_KEYS )
			)
		);

		$details = array();
		if ( $this->has_table( 'lasso_url_details' ) ) {
			$details = $wpdb->get_results(
				$wpdb->prepare( "SELECT lasso_id, redirect_url, product_type, product_id, country FROM {$wpdb->prefix}lasso_url_details WHERE lasso_id IN ( $in )", ...$ids ),
				OBJECT_K
			);
		}

		$asins  = array_values( array_unique( array_filter( array_map( static fn( object $row ): string => 'amazon' === (string) $row->product_type ? (string) $row->product_id : '', $details ) ) ) );
		$amazon = array();
		if ( array() !== $asins && $this->has_table( 'lasso_amazon_products' ) ) {
			$list = implode( ',', array_fill( 0, count( $asins ), '%s' ) );
			foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}lasso_amazon_products WHERE amazon_id IN ( $list )", ...$asins ), ARRAY_A ) as $row ) {
				$amazon[ (string) $row['amazon_id'] ][ (string) $row['country'] ] = array_map( 'strval', $row );
			}
		}
		// phpcs:enable

		// get_post_meta( …, true ) gives the first value of a key, so the first row of each wins.
		$meta = array();
		foreach ( $rows as $row ) {
			$meta[ (int) $row->post_id ][ (string) $row->meta_key ] ??= (string) $row->meta_value;
		}

		$records = array();
		foreach ( $ids as $id ) {
			if ( ! isset( $posts[ $id ] ) ) {
				continue;
			}
			$post         = $posts[ $id ];
			$detail       = $details[ $id ] ?? null;
			$asin         = null === $detail ? '' : (string) $detail->product_id;
			$country      = null === $detail ? '' : (string) $detail->country;
			$thumbnail_id = (int) ( $meta[ $id ]['_thumbnail_id'] ?? 0 );
			$thumbnail    = $thumbnail_id > 0 ? wp_get_attachment_image_url( $thumbnail_id, 'large' ) : false;

			$records[] = new LassoRecord(
				id: $id,
				title: (string) $post->post_title,
				date: (string) $post->post_date,
				modified: (string) $post->post_modified,
				meta: $meta[ $id ] ?? array(),
				redirect_url: null === $detail ? '' : (string) $detail->redirect_url,
				product_type: null === $detail ? '' : (string) $detail->product_type,
				product_id: $asin,
				country: $country,
				// Lasso keeps one row per ASIN and store; the record's own store first, else the ASIN's only row.
				amazon: $amazon[ $asin ][ $country ] ?? ( 1 === count( $amazon[ $asin ] ?? array() ) ? reset( $amazon[ $asin ] ) : null ),
				thumbnail_url: is_string( $thumbnail ) ? $thumbnail : '',
				thumbnail_id: is_string( $thumbnail ) ? $thumbnail_id : 0,
			);
		}

		return $records;
	}

	/**
	 * Whether one of Lasso's tables exists on this site.
	 *
	 * @param string $name The table's name without the prefix.
	 */
	private function has_table( string $name ): bool {
		global $wpdb;

		$table = $wpdb->prefix . $name;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- whether Lasso's table exists.
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
	}
}
