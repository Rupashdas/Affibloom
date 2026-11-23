<?php
/**
 * Text links printed with their product's current link and attributes.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Front;

use Affibloom\Application\LinkIndex\RenderedLinks;
use Affibloom\Domain\LinkIndex\LocationKey;
use Affibloom\Domain\LinkIndex\RenderedAttributes;
use Affibloom\Infrastructure\WordPress\Builders\TablePressSource;
use Affibloom\Interface\PublicLinks;
use Affibloom\Module;
use WP_HTML_Tag_Processor;

/**
 * Lasso rewrites posts so their links follow a product (F21 R37-R40); Affibloom prints the same at render time and
 * writes nothing (D13-D15; Task 35H). Each anchor is matched to its index row as the scan keyed it (the host, the
 * source, the URL and its occurrence among the same URL's links, D4): a tied one takes its product's public link, New
 * Window, rel and the click attributes; any other `_blank` link gets noopener. Affibloom's own displays (anchors that
 * already carry `data-affibloom-id`), and the links a helper names through the filter `affibloom_leave_link` (another
 * plugin's display, Task 29D), are left as they are drawn.
 *
 * Where: `the_content` after blocks and shortcodes (20), which also prints Thrive's copy and the recipes and Create
 * cards a post shows; Elementor's content; the chosen ACF fields (`acf/format_value`, source `acf:<meta key>`);
 * TablePress's tables (`tablepress_table_output`); widgets, which have no index rows, get the untied rule only. Beaver
 * Builder prints through `the_content` too. (F21 N1: these are every builder's output filters, Task 35E.)
 */
final class LinkAttributesFilter implements Module {

	/**
	 * Builds the filter.
	 *
	 * @param RenderedLinks $links  The post's tied links.
	 * @param PublicLinks   $public_links The products' public links.
	 */
	public function __construct( private RenderedLinks $links, private PublicLinks $public_links ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_filter( 'the_content', array( $this, 'content' ), 20 );
		add_filter( 'elementor/frontend/the_content', array( $this, 'content' ), 20 );
		add_filter( 'widget_block_content', array( $this, 'widget' ), 20 );
		add_filter( 'widget_text', array( $this, 'widget' ), 20 );
		add_filter( 'acf/format_value', array( $this, 'acf' ), 20, 3 );
		add_filter( 'tablepress_table_output', array( $this, 'table' ), 20, 2 );
	}

	/**
	 * A post's content.
	 *
	 * @param mixed $html The content.
	 * @return mixed
	 */
	public function content( mixed $html ): mixed {
		return is_string( $html ) ? $this->printed( $html, (int) get_the_ID(), 'content' ) : $html;
	}

	/**
	 * A widget's HTML: no rows, so the untied rule only.
	 *
	 * @param mixed $html The widget's HTML.
	 * @return mixed
	 */
	public function widget( mixed $html ): mixed {
		return is_string( $html ) ? $this->printed( $html, 0, '' ) : $html;
	}

	/**
	 * An ACF field's value on a post: its rows are under `acf:<meta key>` (PostContentSource), ACF giving a sub-field
	 * its full meta key as its name.
	 *
	 * @param mixed $value   The formatted value.
	 * @param mixed $post_id The post (or 'option', which has no rows).
	 * @param mixed $field   The field.
	 * @return mixed
	 */
	public function acf( mixed $value, mixed $post_id, mixed $field ): mixed {
		if ( ! is_string( $value ) || ! is_array( $field ) || ! is_string( $field['name'] ?? null ) ) {
			return $value;
		}

		return $this->printed( $value, is_numeric( $post_id ) ? (int) $post_id : 0, 'acf:' . substr( $field['name'], 0, 32 ) );
	}

	/**
	 * The HTML with its anchors printed as a reader sees them.
	 *
	 * @param string $html    The HTML.
	 * @param int    $host_id The post it belongs to, 0 for none.
	 * @param string $source  Where in the post it is stored.
	 */
	private function printed( string $html, int $host_id, string $source ): string {
		if ( false === stripos( $html, '<a' ) ) {
			return $html;
		}
		$tied = $host_id > 0 ? $this->links->for_host( $host_id ) : array();
		// The content is printed with what it shows from other places, a recipe or a Create card (Task 35E): a link is
		// looked for in "content" first, then in each such place, each with its own count of the same URL.
		$places = 'content' === $source ? self::places( $tied ) : array( $source );
		$seen   = array();
		$tags   = new WP_HTML_Tag_Processor( $html );
		$moved  = false;
		while ( $tags->next_tag( array( 'tag_name' => 'a' ) ) ) {
			$href = $tags->get_attribute( 'href' );
			// Displays draw their own links: Affibloom's (they carry data-affibloom-id), and any a helper names through
			// `affibloom_leave_link` (Task 29D). They are left as drawn and not counted, so the text keeps its rows.
			if ( ! is_string( $href ) || null !== $tags->get_attribute( 'data-affibloom-id' ) || self::left( $tags ) ) {
				continue;
			}
			$md5  = LocationKey::md5( $href );
			$link = null;
			foreach ( $places as $place ) {
				$next = ( $seen[ $place ][ $md5 ] ?? 0 ) + 1;
				$link = $tied[ LocationKey::of( $host_id, $place, $md5, $next ) ] ?? null;
				if ( null !== $link ) {
					$seen[ $place ][ $md5 ] = $next;
					break;
				}
			}
			if ( null === $link ) {
				// A link the index does not know (added at render time) counts in the first place.
				$seen[ $places[0] ][ $md5 ] = ( $seen[ $places[0] ][ $md5 ] ?? 0 ) + 1;
			}
			if ( null === $link || null === $link['product'] ) {
				$target  = $tags->get_attribute( 'target' );
				$rel     = $tags->get_attribute( 'rel' );
				$changes = RenderedAttributes::untied( is_string( $target ) ? $target : '', is_string( $rel ) ? $rel : '' );
				// Turned back to its original URL (F22 N3, E17; Task GF6): readers get that URL; the post is unchanged.
				if ( null !== $link && '' !== $link['original'] ) {
					$changes['href'] = $link['original'];
				}
			} else {
				$product = $link['product'];
				$changes = RenderedAttributes::tied( $href, $this->public_links->for( $product ), $product->new_tab, $product->nofollow, $product->sponsored, (int) $product->id, $link['id'] );
			}
			foreach ( $changes as $name => $value ) {
				$moved = true;
				if ( null === $value ) {
					$tags->remove_attribute( $name );
				} else {
					$tags->set_attribute( $name, $value );
				}
			}
		}

		return $moved ? $tags->get_updated_html() : $html;
	}

	/**
	 * Whether a helper wants a link left as drawn: the filter `affibloom_leave_link` gets false and the link's attribute
	 * names (Task 29D: the migrator names its source plugin's display links while that plugin is on).
	 *
	 * @param WP_HTML_Tag_Processor $tags The processor, at the link.
	 */
	private static function left( WP_HTML_Tag_Processor $tags ): bool {
		return true === apply_filters( 'affibloom_leave_link', false, $tags->get_attribute_names_with_prefix( '' ) ?? array() );
	}

	/**
	 * A TablePress table as TablePress prints it (`tablepress_table_output`): the table is a post of its own, found by
	 * TablePress's table ID (Task 35E).
	 *
	 * @param mixed $output The table's HTML.
	 * @param mixed $table  The table.
	 * @return mixed
	 */
	public function table( mixed $output, mixed $table ): mixed {
		if ( ! is_string( $output ) || ! is_array( $table ) || ! isset( $table['id'] ) ) {
			return $output;
		}

		return $this->printed( $output, TablePressSource::post_of( (string) $table['id'] ), 'content' );
	}

	/**
	 * The places a post's printed content may show links from: "content", then the others with rows, ACF fields aside
	 * (they are printed through their own filter).
	 *
	 * @param array<string, mixed> $tied The post's tied links by key (host|source|md5|occurrence).
	 * @return non-empty-list<string>
	 */
	private static function places( array $tied ): array {
		$places = array( 'content' );
		foreach ( array_keys( $tied ) as $key ) {
			$source = explode( '|', (string) $key )[1] ?? 'content';
			if ( ! in_array( $source, $places, true ) && ! str_starts_with( $source, 'acf:' ) ) {
				$places[] = $source;
			}
		}

		return $places;
	}
}
