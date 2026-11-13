<?php
/**
 * A product's added buttons, in order, and where its default button sits among them (F29).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

/**
 * A product's added buttons, in order, and where its default button sits among them (F29 requirements B1, D1, D4, D6).
 * The default button is the product's own link (Primary Destination URL) and is not in this list; the user decided
 * (2026-11-13) that it moves among the others like any button, and that the first button in a box is its primary one
 * (D7). Lasso's secondary button is the first added one. There is no limit (D1).
 */
final class Buttons {

	/**
	 * The buttons, none of them empty.
	 *
	 * @var list<Button>
	 */
	private array $buttons;

	/**
	 * Where the default button sits: 0 before every added button, count() after them all.
	 *
	 * @var int
	 */
	private int $main_position;

	/**
	 * Builds the list; empty buttons are left out, and the default button's position is kept within it.
	 *
	 * @param list<Button> $buttons       The added buttons, in order.
	 * @param int          $main_position Where the default button sits among them, from 0.
	 */
	public function __construct( array $buttons, int $main_position = 0 ) {
		$this->buttons       = array_values( array_filter( $buttons, static fn( Button $button ): bool => ! $button->is_empty() ) );
		$this->main_position = max( 0, min( $main_position, count( $this->buttons ) ) );
	}

	/**
	 * The list from storage (`_affibloom_buttons`). A row that is not an array is skipped; a toggle stored as a boolean
	 * or as "1"/"0" is read as such, and a missing one as on, Lasso's new-record default.
	 *
	 * @param array<mixed> $stored        Rows as to_array() writes them.
	 * @param int          $main_position Where the default button sits (`_affibloom_primary_position`).
	 */
	public static function from_array( array $stored, int $main_position = 0 ): self {
		$flag = static fn( mixed $value ): bool => null === $value || true === $value || '1' === $value || 1 === $value;

		$buttons = array();
		foreach ( $stored as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$buttons[] = new Button(
				is_string( $row['url'] ?? null ) ? trim( $row['url'] ) : '',
				is_string( $row['text'] ?? null ) ? $row['text'] : '',
				$flag( $row['new_tab'] ?? null ),
				$flag( $row['nofollow'] ?? null ),
			);
		}

		return new self( $buttons, $main_position );
	}

	/**
	 * The list as stored.
	 *
	 * @return list<array{url: string, text: string, new_tab: bool, nofollow: bool}>
	 */
	public function to_array(): array {
		return array_map(
			static fn( Button $button ): array => array(
				'url'      => $button->url,
				'text'     => $button->text,
				'new_tab'  => $button->new_tab,
				'nofollow' => $button->nofollow,
			),
			$this->buttons
		);
	}

	/**
	 * Every button, in order.
	 *
	 * @return list<Button>
	 */
	public function all(): array {
		return $this->buttons;
	}

	/**
	 * Where the default button sits among the added ones: 0 is first.
	 */
	public function main_position(): int {
		return $this->main_position;
	}
}
