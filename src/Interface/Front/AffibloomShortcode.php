<?php
/**
 * The `[affibloom]` shortcode.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Front;

use Affibloom\Application\Display\AiTextService;
use Affibloom\Application\Shortcode\ShortcodeRenderer;
use Affibloom\Domain\Shortcode\DisplayKind;
use Affibloom\Domain\Shortcode\DisplayRequest;
use Affibloom\Module;

/**
 * `[affibloom id|slug …]` (F31 D1, D2; Task 18S1): Affibloom's own shortcode, in Lasso's attribute language, drawing the
 * same box the block draws. A box that cannot draw prints nothing (D4) and is reported to `affibloom_box_failed` and,
 * with WP_DEBUG_LOG, to the debug log.
 */
final class AffibloomShortcode implements Module {

	/**
	 * The `[affibloom]` boxes drawn so far on this page, for the default anchor (D5).
	 *
	 * @var int
	 */
	private int $drawn = 0;

	/**
	 * Builds the shortcode.
	 *
	 * @param ShortcodeRenderer $renderer The renderer.
	 * @param BoxHtml           $html     The box's printer.
	 * @param DisplayHtml       $displays The group displays' and the AI text's printer (F30 DT2-DT9).
	 * @param AiTextService     $ai_texts The stored AI texts (F30 DT9).
	 */
	public function __construct(
		private readonly ShortcodeRenderer $renderer,
		private readonly BoxHtml $html,
		private readonly DisplayHtml $displays,
		private readonly AiTextService $ai_texts,
	) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'add' ) );
	}

	/**
	 * Registers the shortcode.
	 */
	public function add(): void {
		add_shortcode( 'affibloom', array( $this, 'render' ) );
	}

	/**
	 * The shortcode's HTML.
	 *
	 * @param array<int|string, mixed>|string $attributes The attributes as WordPress parsed them.
	 */
	public function render( array|string $attributes ): string {
		$request = DisplayRequest::of( $attributes );
		// F30 R39, D14: a prompt without a product shows its stored text, or nothing; no service is asked.
		if ( DisplayKind::AiText === $request->kind ) {
			$text = $this->ai_texts->find( $request->attributes['prompt'] ?? '', strtolower( trim( $request->attributes['format'] ?? '' ) ) );
			return null === $text ? '' : $this->displays->ai_text( $text );
		}
		// F30 R61: a grid or a gallery always draws, an unknown or empty group as its wrapper; a list as the empty list.
		if ( DisplayKind::Grid === $request->kind ) {
			++$this->drawn;
			return $this->displays->grid( $this->renderer->grid( $request, $this->drawn, (int) get_the_ID() ) );
		}
		if ( DisplayKind::Gallery === $request->kind ) {
			++$this->drawn;
			return $this->displays->grid( $this->renderer->gallery( $request, $this->drawn, (int) get_the_ID() ) );
		}
		// F25 R43 (Task T12): a comparison table; a missing one draws nothing.
		if ( DisplayKind::Table === $request->kind ) {
			$table = $this->renderer->table( $request, $this->drawn + 1, (int) get_the_ID() );
			if ( null === $table ) {
				return '';
			}
			++$this->drawn;
			return $this->displays->table( $table );
		}
		if ( DisplayKind::List === $request->kind ) {
			++$this->drawn;
			return $this->displays->list( $this->renderer->list( $request, $this->drawn, (int) get_the_ID() ) );
		}
		$view = $this->renderer->view( $request, $this->drawn + 1, (int) get_the_ID() );
		if ( null === $view ) {
			do_action( 'affibloom_box_failed', $attributes, (int) get_the_ID() );
			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log( 'Affibloom: [affibloom] drew nothing in post ' . (int) get_the_ID() . ' (no published product for ' . wp_json_encode( $attributes ) . ').' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- D4: the debug log line, only with WP_DEBUG_LOG.
			}
			return '';
		}
		++$this->drawn;

		return $this->html->render( $view );
	}
}
