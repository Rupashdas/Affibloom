<?php
/**
 * A host post's content, for the link index.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Application\LinkIndex\ContentSource;
use Affibloom\Domain\LinkIndex\HostContent;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Infrastructure\WordPress\Builders\BuilderSource;

/**
 * An indexed post's places (F21 D6, D7): its stored `post_content` as source "content", and each ACF field chosen in
 * Custom Link Detection (`scan_custom_fields`; R33: every meta key of the post that ends with the field's name, so a
 * field inside a repeater or group is found too) as source "acf:<meta key>"; and the places the active builders and
 * recipe plugins keep (Task 35E: Thrive's copy, TablePress's cells, `wprm:<id>`, `mv:<id>`). Read, never written (D3).
 */
final class PostContentSource implements ContentSource {

	/**
	 * Builds the source.
	 *
	 * @param HostQuery           $hosts    The indexed post types.
	 * @param SettingsRepository  $settings The chosen ACF fields.
	 * @param list<BuilderSource> $builders The builders and recipe plugins read while active (Task 35E).
	 */
	public function __construct( private HostQuery $hosts, private SettingsRepository $settings, private array $builders = array() ) {}

	/**
	 * The host and its places, or null when the post is not indexed.
	 *
	 * @param int $host_id The post.
	 */
	public function read( int $host_id ): ?HostContent {
		$post = get_post( $host_id );
		if ( null === $post || ! in_array( $post->post_type, $this->hosts->types(), true ) || ! in_array( $post->post_status, HostQuery::STATUSES, true ) ) {
			return null;
		}

		$sources = array( 'content' => (string) $post->post_content ) + $this->fields( (int) $post->ID );
		foreach ( $this->builders as $builder ) {
			$sources = $builder->read( $post, $sources );
		}

		return new HostContent( (int) $post->ID, (string) get_permalink( $post ), $sources );
	}

	/**
	 * The chosen ACF fields' values in the post, by "acf:<meta key>". ACF's own references (keys starting with "_")
	 * are left out.
	 *
	 * @param int $post_id The post.
	 * @return array<string, string>
	 */
	private function fields( int $post_id ): array {
		$names = $this->settings->get()->list( 'scan_custom_fields' );
		if ( array() === $names ) {
			return array();
		}

		$found = array();
		foreach ( (array) get_post_meta( $post_id ) as $key => $values ) {
			$key = (string) $key;
			if ( str_starts_with( $key, '_' ) || ! is_array( $values ) || ! is_string( $values[0] ?? null ) ) {
				continue;
			}
			foreach ( $names as $name ) {
				if ( '' !== $name && str_ends_with( $key, $name ) ) {
					$found[ 'acf:' . substr( $key, 0, 32 ) ] = $values[0];
					break;
				}
			}
		}

		return $found;
	}
}
