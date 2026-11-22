<?php
namespace Affibloom\Tests\Unit\Domain\Display;

use Affibloom\Domain\Display\OverrideCatalog;
use Affibloom\Tests\Unit\TestCase;

/**
 * Lasso's Customize Display rules (F4 R36-R40, Task 24A): the fields per type, the toggles' stored values, the cleaning.
 */
final class OverrideCatalogTest extends TestCase {

	public function test_each_type_has_lassos_fields_in_lassos_order(): void {
		self::assertSame( array( 'theme', 'button_type', 'primary_url', 'primary_text', 'anchor_id' ), OverrideCatalog::fields( 'button' ) );
		self::assertSame( array( 'image_url', 'anchor_id' ), OverrideCatalog::fields( 'image' ) );
		self::assertSame( array( 'columns', 'limit', 'anchor_id' ), OverrideCatalog::fields( 'gallery' ) );
		self::assertSame( 'title', OverrideCatalog::fields( '' )[0] );
		self::assertSame( array(), OverrideCatalog::fields( 'carousel' ) );
	}

	public function test_toggles_store_only_their_non_default_value(): void {
		$stored = OverrideCatalog::overrides(
			'grid',
			array(
				'price'      => 'hide',
				'field'      => 'show',
				'rating'     => '',
				'disclosure' => 'enable',
			)
		);
		self::assertSame(
			array(
				'price'      => 'hide',
				'disclosure' => 'enable',
			),
			$stored
		);
		self::assertSame( array(), OverrideCatalog::overrides( 'table', array( 'data_nosnippet' => 'hide' ) ) );
		self::assertSame( 'toggle', OverrideCatalog::kind( 'data_nosnippet' ) );
		self::assertTrue( OverrideCatalog::on_by_default( 'rating' ) );
		self::assertFalse( OverrideCatalog::on_by_default( 'disclosure' ) );
	}

	public function test_values_lose_quotes_anchor_ids_their_spaces_and_empty_ones_go(): void {
		$stored = OverrideCatalog::overrides(
			'single',
			array(
				'badge'       => ' Best "Overall" ',
				'anchor_id'   => 'my top  pick',
				'title'       => '',
				'description' => "Line one\nLine two",
				'columns'     => '3',
				'theme'       => array( 'Lab' ),
			)
		);
		self::assertSame(
			array(
				'description' => "Line one\nLine two",
				'badge'       => 'Best Overall',
				'anchor_id'   => 'my-top-pick',
			),
			$stored
		);
		self::assertSame( 'textarea', OverrideCatalog::kind( 'description' ) );
		self::assertSame( 'text', OverrideCatalog::kind( 'badge' ) );
	}

	/** Task 24G: the badge and button texts take an icon, except a grid's badge, which only hides (its help text). */
	public function test_badge_and_button_texts_take_an_icon(): void {
		self::assertTrue( OverrideCatalog::icon_text( 'single', 'badge' ) );
		self::assertTrue( OverrideCatalog::icon_text( '', 'primary_text' ) );
		self::assertTrue( OverrideCatalog::icon_text( 'single', 'secondary_text' ) );
		self::assertTrue( OverrideCatalog::icon_text( 'button', 'primary_text' ) );
		self::assertFalse( OverrideCatalog::icon_text( 'grid', 'badge' ) );
		self::assertFalse( OverrideCatalog::icon_text( 'single', 'title' ) );
	}

	/** Task 24G: the editors' icon form keeps its single quotes; the site's double-quoted form still loses its quotes (R40). */
	public function test_an_icon_in_single_quotes_is_kept(): void {
		$stored = OverrideCatalog::overrides(
			'single',
			array(
				'badge'        => "<span class='medal-icon'></span> Our Pick",
				'primary_text' => '<span class="amazon-icon"></span> Buy',
			)
		);

		self::assertSame( "<span class='medal-icon'></span> Our Pick", $stored['badge'] );
		self::assertSame( '<span class=amazon-icon></span> Buy', $stored['primary_text'] );
	}
}
