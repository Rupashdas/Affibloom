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
}
