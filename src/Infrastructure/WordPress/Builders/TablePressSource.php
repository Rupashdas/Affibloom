<?php
/**
 * A TablePress table's cells.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress\Builders;

use WP_Post;

/**
 * F21 R32 (Lasso's scan_post_content for `tablepress_table`): a table is a post of its own whose `post_content` is the
 * cells as JSON rows; the cells' HTML, in order, is the table's "content". Tables are indexed while TablePress is
 * active. TablePress prints a table through `tablepress_table_output`, where render time finds its post (35H).
 */
final class TablePressSource implements BuilderSource {

	public const TYPE = 'tablepress_table';

	/**
	 * The table's cells as its "content".
	 *
	 * @param WP_Post               $post    The post.
	 * @param array<string, string> $sources Its places so far.
	 * @return array<string, string>
	 */
	public function read( WP_Post $post, array $sources ): array {
		if ( self::TYPE !== $post->post_type ) {
			return $sources;
		}
		$rows  = json_decode( $post->post_content, true );
		$cells = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			foreach ( is_array( $row ) ? $row : array() as $cell ) {
				if ( is_string( $cell ) && '' !== $cell ) {
					$cells[] = $cell;
				}
			}
		}
		$sources['content'] = implode( "\n", $cells );

		return $sources;
	}

	/**
	 * TablePress's tables, while it is active.
	 */
	public function host_type(): ?string {
		return ActivePlugins::any( 'tablepress/tablepress.php' ) ? self::TYPE : null;
	}

	/**
	 * The post of a table TablePress prints by its own ID (option `tablepress_tables`: `table_post`), or 0.
	 *
	 * @param string $table_id TablePress's table ID.
	 */
	public static function post_of( string $table_id ): int {
		$tables = json_decode( (string) get_option( 'tablepress_tables', '' ), true );

		return is_array( $tables ) && isset( $tables['table_post'][ $table_id ] ) ? (int) $tables['table_post'][ $table_id ] : 0;
	}
}
