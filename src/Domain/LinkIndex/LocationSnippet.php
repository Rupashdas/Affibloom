<?php
/**
 * The paragraph a link stands in.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

use DOMDocument;
use DOMElement;
use DOMNode;

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHP's DOM API names its properties (parentNode, tagName, childNodes, ownerDocument).

/**
 * Lasso's "Link with Context" (F12 L10, D24; Task 36C): the block that holds the n-th link to a URL in a piece of stored
 * content (a paragraph, list item, heading, cell or quote), as HTML, with that link marked
 * (`class="affibloom-preview__hit"`). The caller escapes it down to its links before printing it.
 */
final class LocationSnippet {

	/** The elements a link's context is cut at. */
	private const BLOCKS = array( 'p', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'td', 'th', 'blockquote', 'figcaption', 'dd', 'dt', 'div', 'section', 'article' );

	/**
	 * The block around the link, or null when the content no longer holds it.
	 *
	 * @param string $html       The stored content.
	 * @param string $url        The link's address, as indexed.
	 * @param int    $occurrence Which link to that address (LocationKey), from 1.
	 */
	public static function of( string $html, string $url, int $occurrence ): ?string {
		if ( '' === trim( $html ) || $occurrence < 1 ) {
			return null;
		}
		$document = self::load( $html );

		$seen = 0;
		foreach ( $document->getElementsByTagName( 'a' ) as $link ) {
			if ( trim( $link->getAttribute( 'href' ) ) !== $url || ++$seen !== $occurrence ) {
				continue;
			}
			$link->setAttribute( 'class', trim( $link->getAttribute( 'class' ) . ' affibloom-preview__hit' ) );
			$block = self::block( $link );

			return null === $block ? null : trim( self::inner( $block ) );
		}

		return null;
	}

	/**
	 * Every link's block in one reading of the content (the Content Opportunities CSV's `anchor_context`, F22 R60; Task
	 * O9): by the link's address and its occurrence (as of() counts them), the block's inner HTML, unmarked.
	 *
	 * @param string $html The stored content.
	 * @return array<string, string> Keyed "<url>\n<occurrence>".
	 */
	public static function all( string $html ): array {
		if ( '' === trim( $html ) ) {
			return array();
		}
		$blocks = array();
		$seen   = array();
		foreach ( self::load( $html )->getElementsByTagName( 'a' ) as $link ) {
			$url          = trim( $link->getAttribute( 'href' ) );
			$seen[ $url ] = ( $seen[ $url ] ?? 0 ) + 1;
			$block        = self::block( $link );
			if ( null !== $block ) {
				$blocks[ $url . "\n" . $seen[ $url ] ] = trim( self::inner( $block ) );
			}
		}

		return $blocks;
	}

	/**
	 * The content as a document: the meta tag reads it as UTF-8; the wrapper gives the links a block to stop at.
	 *
	 * @param string $html The stored content.
	 */
	private static function load( string $html ): DOMDocument {
		$document = new DOMDocument();
		$previous = libxml_use_internal_errors( true );
		$document->loadHTML( '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body><div id="affibloom-snippet-root">' . $html . '</div></body></html>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		return $document;
	}

	/**
	 * The nearest block around a node; the wrapper when the link stands in no block of its own.
	 *
	 * @param DOMElement $node The link.
	 */
	private static function block( DOMElement $node ): ?DOMElement {
		for ( $parent = $node->parentNode; $parent instanceof DOMElement; $parent = $parent->parentNode ) {
			if ( in_array( strtolower( $parent->tagName ), self::BLOCKS, true ) ) {
				return $parent;
			}
		}

		return null;
	}

	/**
	 * An element's inner HTML.
	 *
	 * @param DOMNode $node The element.
	 */
	private static function inner( DOMNode $node ): string {
		$html = '';
		foreach ( $node->childNodes as $child ) {
			$html .= (string) $node->ownerDocument?->saveHTML( $child );
		}

		return $html;
	}
}
