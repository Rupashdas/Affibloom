<?php
/**
 * Finds the links in HTML with WordPress's HTML API.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\Html;

use Affibloom\Application\LinkIndex\LinkScanner;
use Affibloom\Domain\LinkIndex\LinkCandidate;
use WP_HTML_Tag_Processor;

/**
 * The anchors the index keeps (F21 R24, R25), read with `WP_HTML_Tag_Processor` instead of Lasso's simple_html_dom
 * (risk "Parser differences"). Blocks Lasso does not scan are left out whole; anchors inside an element whose class
 * starts like an FAQ or accordion are skipped, as are anchors with an ignored class (Affibloom's own
 * `affibloom-scan-ignore` and Kadence's overlay links), and hrefs that are empty, `#…`, `[…]`, `mailto:` or `tel:`.
 */
final class WpLinkScanner implements LinkScanner {

	/** Blocks whose links are not scanned (R24), by the start of their name. */
	private const IGNORED_BLOCKS = array( 'tph/', 'editor-blocks/', 'uagb/', 'rank-math/', 'yoast/faq', 'wordpress-seo/faq', 'aioseo/faq', 'seopress/faq', 'wpseopress/faq', 'wp-seopress/faq', 'kadence/accordion', 'kadence/pane', 'acf/' );

	/** Elements whose links are not scanned (R24), by the start of one of their classes. */
	private const IGNORED_ANCESTORS = array( 'schema-faq', 'wp-block-yoast-faq', 'wp-block-rank-math-faq', 'wp-block-aioseo-faq', 'wp-block-seopress-faq', 'wp-block-kadence-accordion', 'kt-accordion-pane', 'wp-block-acf-' );

	/** Anchor classes that keep an anchor out of the index (R24). */
	private const IGNORED_ANCHORS = array( 'affibloom-scan-ignore', 'info-box-link', 'kb-advanced-image-link', 'kt-svg-icon-link', 'kb-section-link-overlay', 'kt-sc-media-link', 'kt-imageoverlay-link' );

	/** Elements that never have a closing tag. */
	private const VOID = array( 'AREA', 'BASE', 'BR', 'COL', 'EMBED', 'HR', 'IMG', 'INPUT', 'LINK', 'META', 'SOURCE', 'TRACK', 'WBR' );

	/**
	 * The links, in document order.
	 *
	 * @param string $html Post content or another scanned place.
	 * @return list<LinkCandidate>
	 */
	public function scan( string $html ): array {
		if ( false === stripos( $html, '<a' ) ) {
			return array();
		}
		if ( str_contains( $html, '<!-- wp:' ) ) {
			$html = self::without_ignored_blocks( parse_blocks( $html ) );
		}

		$tags   = new WP_HTML_Tag_Processor( $html );
		$open   = array();
		$anchor = null;
		$links  = array();
		while ( $tags->next_token() ) {
			$type = $tags->get_token_type();
			if ( '#text' === $type ) {
				if ( null !== $anchor ) {
					$anchor['text'] .= $tags->get_modifiable_text();
				}
				continue;
			}
			if ( '#tag' !== $type ) {
				continue;
			}

			$tag = (string) $tags->get_tag();
			if ( $tags->is_tag_closer() ) {
				if ( 'A' === $tag && null !== $anchor ) {
					$links[] = $anchor;
					$anchor  = null;
				}
				self::close( $open, $tag );
				continue;
			}

			$inside  = array() !== $open && $open[ count( $open ) - 1 ][1];
			$classes = preg_split( '/\s+/', trim( self::attribute( $tags, 'class' ) ), -1, PREG_SPLIT_NO_EMPTY );
			if ( 'A' === $tag ) {
				if ( null !== $anchor ) {
					$links[] = $anchor;
				}
				$anchor = array(
					'href'    => trim( self::attribute( $tags, 'href' ) ),
					'rel'     => self::attribute( $tags, 'rel' ),
					'target'  => self::attribute( $tags, 'target' ),
					'ignored' => $inside || array() !== array_intersect( $classes, self::IGNORED_ANCHORS ),
					'image'   => false,
					'text'    => '',
				);
			} elseif ( 'IMG' === $tag && null !== $anchor ) {
				$anchor['image'] = true;
			}
			if ( ! in_array( $tag, self::VOID, true ) && ! $tags->has_self_closing_flag() ) {
				$open[] = array( $tag, $inside || self::ignores( $classes ) );
			}
		}
		if ( null !== $anchor ) {
			$links[] = $anchor;
		}

		$kept = array();
		foreach ( $links as $link ) {
			if ( ! $link['ignored'] && ! self::skipped( $link['href'] ) ) {
				$kept[] = new LinkCandidate( $link['href'], $link['rel'], $link['target'], $link['image'], $link['text'] );
			}
		}

		return $kept;
	}

	/**
	 * The HTML of the blocks Lasso scans, in order, nested blocks in their places.
	 *
	 * @param array<int, array<string, mixed>> $blocks parse_blocks()' answer, or a block's innerBlocks.
	 */
	private static function without_ignored_blocks( array $blocks ): string {
		$html = '';
		foreach ( $blocks as $block ) {
			$html .= self::block_html( $block );
		}

		return $html;
	}

	/**
	 * One block's HTML, or '' for a block Lasso does not scan.
	 *
	 * @param array<string, mixed> $block One parsed block.
	 */
	private static function block_html( array $block ): string {
		$name = (string) ( $block['blockName'] ?? '' );
		foreach ( self::IGNORED_BLOCKS as $ignored ) {
			if ( str_starts_with( $name, $ignored ) ) {
				return '';
			}
		}

		$html  = '';
		$inner = is_array( $block['innerBlocks'] ?? null ) ? array_values( $block['innerBlocks'] ) : array();
		$next  = 0;
		foreach ( is_array( $block['innerContent'] ?? null ) ? $block['innerContent'] : array() as $chunk ) {
			if ( is_string( $chunk ) ) {
				$html .= $chunk;
			} elseif ( isset( $inner[ $next ] ) && is_array( $inner[ $next ] ) ) {
				$html .= self::block_html( $inner[ $next++ ] );
			}
		}

		return $html;
	}

	/**
	 * Whether one of the classes marks an element whose links are not scanned.
	 *
	 * @param list<string> $classes An element's classes.
	 */
	private static function ignores( array $classes ): bool {
		foreach ( $classes as $class ) {
			foreach ( self::IGNORED_ANCESTORS as $prefix ) {
				if ( str_starts_with( $class, $prefix ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Closes the innermost open element with this name, and every element opened inside it.
	 *
	 * @param list<array{0: string, 1: bool}> $open The open elements, outermost first.
	 * @param string                          $tag  The closing tag's name.
	 */
	private static function close( array &$open, string $tag ): void {
		for ( $i = count( $open ) - 1; $i >= 0; $i-- ) {
			if ( $tag === $open[ $i ][0] ) {
				array_splice( $open, $i );
				return;
			}
		}
	}

	/**
	 * An attribute's value, '' when it is missing or has no value.
	 *
	 * @param WP_HTML_Tag_Processor $tags The processor, on a tag.
	 * @param string                $name The attribute.
	 */
	private static function attribute( WP_HTML_Tag_Processor $tags, string $name ): string {
		$value = $tags->get_attribute( $name );

		return is_string( $value ) ? $value : '';
	}

	/**
	 * Whether an href is not a link the index keeps (R25).
	 *
	 * @param string $href The trimmed href.
	 */
	private static function skipped( string $href ): bool {
		return '' === $href
			|| str_starts_with( $href, '#' )
			|| str_starts_with( $href, '[' )
			|| 0 === stripos( $href, 'mailto:' )
			|| 0 === stripos( $href, 'tel:' );
	}
}
