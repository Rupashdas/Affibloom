<?php
/**
 * The rules for product fields sent from the product page or the REST API.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use Affibloom\Domain\Display\Theme;
use Affibloom\Domain\Product\Button;
use Affibloom\Domain\Product\Buttons;
use Affibloom\Domain\Product\PriceHtml;

/**
 * Cleans each field the way Lasso's save_lasso_url cleans it (F1 requirements 9, S2-S6). Lasso refuses one thing
 * only, an empty name (S2: "Name and Primary Destination URL are required."); every other value is cleaned and stored.
 * Only the fields sent change, so a field the page did not send (an untouched description) keeps its stored bytes.
 */
final class ProductInput {

	/** Lasso's switches that are stored as 0/1 (`enable_nofollow`, `open_new_tab`, …), plus Show Price and Prime. */
	private const SWITCHES = array( 'new_tab', 'nofollow', 'sponsored', 'show_disclosure', 'prime', 'show_price' );

	/** Badge and button texts: the site's icon spans and nothing else (Lasso's get_icon_markup_wp_kses_allowed_html). */
	private const ICON_MARKUP = array( 'span' => array( 'class' => true ) );

	/** Lasso's get_price_field_wp_kses_allowed_html: the price editor's tags, each with style and class. */
	private const PRICE_TAGS = array(
		'br'     => array(),
		'p'      => array(
			'style' => true,
			'class' => true,
		),
		'b'      => array(
			'style' => true,
			'class' => true,
		),
		'strong' => array(
			'style' => true,
			'class' => true,
		),
		'i'      => array(
			'style' => true,
			'class' => true,
		),
		'em'     => array(
			'style' => true,
			'class' => true,
		),
		'u'      => array(
			'style' => true,
			'class' => true,
		),
		's'      => array(
			'style' => true,
			'class' => true,
		),
		'strike' => array(
			'style' => true,
			'class' => true,
		),
		'span'   => array(
			'style' => true,
			'class' => true,
		),
	);

	/**
	 * Builds the rules.
	 *
	 * @param LinkNormaliser $links A button's link as Lasso stores it.
	 */
	public function __construct( private LinkNormaliser $links ) {}

	/**
	 * Clean values for Product::with(), only for the fields present.
	 *
	 * @param array<string, mixed> $input Field => value, as sent.
	 * @return array<string, mixed>
	 * @throws ValidationFailed When the name is sent empty.
	 */
	public function changes( array $input ): array {
		$changes = array();

		if ( array_key_exists( 'name', $input ) ) {
			$name = self::text( $input['name'] );
			if ( '' === $name ) {
				throw new ValidationFailed( array( 'name' => 'required' ) );
			}
			$changes['name'] = $name;
		}
		if ( array_key_exists( 'disclosure', $input ) ) {
			$changes['disclosure'] = self::text( $input['disclosure'] );
		}
		foreach ( array( 'button_text', 'badge' ) as $field ) {
			if ( array_key_exists( $field, $input ) ) {
				$changes[ $field ] = self::icon_markup( $input[ $field ] );
			}
		}
		foreach ( self::SWITCHES as $field ) {
			if ( array_key_exists( $field, $input ) ) {
				$changes[ $field ] = (bool) $input[ $field ];
			}
		}
		if ( array_key_exists( 'theme', $input ) ) {
			// '' is "Default (<the type's theme>)"; Theme::from() reads an unknown name as Lasso's default theme (14B).
			$theme            = self::text( $input['theme'] );
			$changes['theme'] = '' === $theme ? null : Theme::from( $theme );
		}
		if ( array_key_exists( 'buttons', $input ) ) {
			$changes['buttons'] = $this->buttons( $input['buttons'], $input['primary_position'] ?? 0 );
		}
		if ( array_key_exists( 'image_url', $input ) || array_key_exists( 'image_id', $input ) ) {
			$changes += self::image( $input['image_url'] ?? '', $input['image_id'] ?? 0 );
		}
		if ( array_key_exists( 'price', $input ) ) {
			$changes['price'] = wp_kses( PriceHtml::normalised( self::scalar( $input['price'] ) ), self::PRICE_TAGS );
		}
		if ( array_key_exists( 'description', $input ) ) {
			// Lasso's save_scalar_html, then wp_encode_emoji (save_lasso_url 1379-1381).
			$changes['description'] = wp_encode_emoji( wp_kses_post( self::scalar( $input['description'] ) ) );
		}

		return $changes;
	}

	/**
	 * The buttons after the default one, in the order sent, and where the default one sits among them (F29).
	 *
	 * @param mixed $rows     A list of { url, text, new_tab, nofollow }.
	 * @param mixed $position The default button's place, from 0.
	 */
	private function buttons( mixed $rows, mixed $position ): Buttons {
		$buttons = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			// A toggle not sent is on, as Lasso's second-button toggles start (F29 B2).
			$buttons[] = new Button(
				$this->links->button( self::scalar( $row['url'] ?? '' ) ),
				self::icon_markup( $row['text'] ?? '' ),
				(bool) ( $row['new_tab'] ?? true ),
				(bool) ( $row['nofollow'] ?? true ),
			);
		}

		return new Buttons( $buttons, is_numeric( $position ) ? (int) $position : 0 );
	}

	/**
	 * The image: a media library item wins and gives its own link; otherwise the typed link, with no item (S6).
	 *
	 * @param mixed $link The image link field.
	 * @param mixed $id   The media library item, 0 for none.
	 * @return array{image_url: string, image_id: int}
	 */
	private static function image( mixed $link, mixed $id ): array {
		$id       = is_numeric( $id ) ? max( 0, (int) $id ) : 0;
		$attached = $id > 0 ? wp_get_attachment_url( $id ) : false;
		if ( is_string( $attached ) && '' !== $attached ) {
			return array(
				'image_url' => $attached,
				'image_id'  => $id,
			);
		}

		return array(
			'image_url' => esc_url_raw( trim( self::scalar( $link ) ) ),
			'image_id'  => 0,
		);
	}

	/**
	 * Lasso's save_scalar_string: plain text.
	 *
	 * @param mixed $value Raw value.
	 */
	private static function text( mixed $value ): string {
		return sanitize_text_field( self::scalar( $value ) );
	}

	/**
	 * Lasso's save_scalar_icon_markup: `<script>` and `<style>` blocks go with their content, then only icon spans stay.
	 *
	 * @param mixed $value Raw value.
	 */
	private static function icon_markup( mixed $value ): string {
		$text = (string) preg_replace( '/<(script|style)\b[^>]*>.*?<\/\1>/is', '', self::scalar( $value ) );

		return wp_kses( $text, self::ICON_MARKUP );
	}

	/**
	 * A scalar as a string; anything else (an array, an object, true/false, null) as '', as Lasso's save_scalar_*.
	 *
	 * @param mixed $value Raw value.
	 */
	private static function scalar( mixed $value ): string {
		return is_scalar( $value ) && ! is_bool( $value ) ? (string) $value : '';
	}
}
