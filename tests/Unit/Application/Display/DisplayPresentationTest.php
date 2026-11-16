<?php
namespace Affibloom\Tests\Unit\Application\Display;

use Affibloom\Application\Display\DisplayPresentation;
use Affibloom\Domain\Display\DisplayConfig;
use Affibloom\Domain\Display\DisplayType;
use PHPUnit\Framework\TestCase;

/**
 * The advanced wrapper's attributes and custom properties (F5 R54-R58; Lasso's Helper 3197-3226, 3356-3431,
 * 4301-4329), under Affibloom's names (ADR-6).
 */
final class DisplayPresentationTest extends TestCase {

	/**
	 * The site's Single settings (F5 requirements, "This copy").
	 */
	private static function site(): DisplayConfig {
		return DisplayConfig::from_array(
			DisplayType::single(),
			array(
				'theme'    => 'Lab',
				'palette'  => array(
					'button'           => '#5e3f8c',
					'secondary_button' => '#5e3f8c',
					'pros'             => '#0e4a0e',
					'cons'             => '#4a0e0e',
				),
				'elements' => array(
					'price'     => false,
					'proscons'  => false,
					'timestamp' => false,
				),
				'density'  => array( 'preset' => 'spacious' ),
				'layout'   => array(
					'imagePosition' => 'left',
					'ctaWidth'      => 'full',
				),
			)
		);
	}

	/**
	 * Every attribute and property of the captured amazon box, in Lasso's order.
	 */
	public function test_the_site_settings_print_the_captured_wrapper(): void {
		self::assertSame(
			array(
				'data-layout-orientation'      => 'side-by-side',
				'data-layout-image-position'   => 'left',
				'data-layout-align'            => 'left',
				'data-layout-button-align'     => 'spread',
				'data-layout-cta-width'        => 'full',
				'data-layout-mobile-overflow'  => 'stack',
				'data-layout-image-fit'        => 'contain',
				'data-density-title-lines'     => '0',
				'data-density-desc-lines'      => '0',
				'data-layout-m-orientation'    => 'side-by-side',
				'data-layout-m-image-position' => 'left',
				'data-layout-m-align'          => 'left',
				'data-layout-m-button-align'   => 'center',
				'data-layout-m-cta-width'      => 'full',
				'data-layout-m-image-fit'      => 'contain',
				'data-hide-price'              => '1',
				'data-hide-proscons'           => '1',
				'data-hide-timestamp'          => '1',
				'style'                        => '--affibloom-main: #000000; --affibloom-title: #000000; --affibloom-button: #5e3f8c; '
					. '--affibloom-secondary-button: #5e3f8c; --affibloom-button-text: #ffffff; --affibloom-background: #ffffff; '
					. '--affibloom-pros: #0e4a0e; --affibloom-cons: #4a0e0e; --affibloom-density-preset: spacious; '
					. '--affibloom-density-gap: 32px; --affibloom-density-padding: 26px; --affibloom-density-space-between: 16px; '
					. '--affibloom-density-title-size: 30px; --affibloom-density-title-lines: 0; --affibloom-density-desc-size: 17px; '
					. '--affibloom-density-desc-lines: 0; --affibloom-density-price-size: 19px; --affibloom-density-button-size: 15px; '
					. '--affibloom-density-image-height: 320px; --affibloom-layout-columns: 3;',
			),
			DisplayPresentation::wrapper( self::site(), 3, true )
		);
	}

	/**
	 * A named preset prints its own map and custom values print as saved, with the two line clamps as attributes too
	 * (R58, R59), as Lasso printed them.
	 */
	public function test_a_preset_and_custom_values_print_lassos_density(): void {
		$density = static function ( array $stored ): string {
			$config  = DisplayConfig::from_array( DisplayType::single(), array( 'density' => $stored ) );
			$wrapper = DisplayPresentation::wrapper( $config, 3, true );
			preg_match_all( '/--affibloom-density-[a-z-]+: [^;]+;/', $wrapper['style'], $found );

			return $wrapper['data-density-title-lines'] . '/' . $wrapper['data-density-desc-lines'] . ' ' . implode( ' ', $found[0] );
		};

		// Lasso's captured boxes (tests/fixtures/baselines/themes/density-compact and density-custom, Lab-full.html).
		self::assertSame(
			'0/0 --affibloom-density-preset: compact; --affibloom-density-gap: 12px; --affibloom-density-padding: 8px; '
				. '--affibloom-density-space-between: 4px; --affibloom-density-title-size: 24px; --affibloom-density-title-lines: 0; '
				. '--affibloom-density-desc-size: 16px; --affibloom-density-desc-lines: 0; --affibloom-density-price-size: 14px; '
				. '--affibloom-density-button-size: 15px; --affibloom-density-image-height: 140px;',
			$density( array( 'preset' => 'compact' ) )
		);
		self::assertSame(
			'1/2 --affibloom-density-preset: custom; --affibloom-density-gap: 8px; --affibloom-density-padding: 12px; '
				. '--affibloom-density-space-between: 6px; --affibloom-density-title-size: 20px; --affibloom-density-title-lines: 1; '
				. '--affibloom-density-desc-size: 14px; --affibloom-density-desc-lines: 2; --affibloom-density-price-size: 22px; '
				. '--affibloom-density-button-size: 18px; --affibloom-density-image-height: 200px;',
			$density(
				array(
					'preset' => 'custom',
					'values' => array(
						'gap'          => 8,
						'padding'      => 12,
						'spaceBetween' => 6,
						'titleSize'    => 20,
						'titleLines'   => 1,
						'descSize'     => 14,
						'descLines'    => 2,
						'priceSize'    => 22,
						'buttonSize'   => 18,
						'imageHeight'  => 200,
					),
				)
			)
		);
	}

	/**
	 * A disclosure that does not show is flagged after the elements (R25), and the phone overrides reach the phone
	 * attributes only (R56).
	 */
	public function test_a_hidden_disclosure_and_phone_overrides(): void {
		$config = self::site();
		$config = $config->with_layout( $config->layout()->with_phone( 'orientation', 'stacked' ) );

		$wrapper = DisplayPresentation::wrapper( $config, 2, false );

		self::assertSame( 'side-by-side', $wrapper['data-layout-orientation'] );
		self::assertSame( 'stacked', $wrapper['data-layout-m-orientation'] );
		self::assertSame( 'top', $wrapper['data-layout-m-image-position'] );
		self::assertSame( array( 'data-hide-price', 'data-hide-proscons', 'data-hide-timestamp', 'data-hide-disclosure' ), array_values( preg_grep( '/^data-hide-/', array_keys( $wrapper ) ) ) );
		self::assertStringEndsWith( '--affibloom-layout-columns: 2;', $wrapper['style'] );
	}

	/**
	 * The page's :root block (R43): the same properties as the wrapper's style, in Lasso's form (no spaces between).
	 */
	public function test_the_root_block_holds_the_wrapper_properties(): void {
		$root = DisplayPresentation::root( self::site(), 3, false );

		self::assertStringStartsWith( ':root{--affibloom-main: #000000;--affibloom-title: #000000;--affibloom-button: #5e3f8c;', $root );
		self::assertStringContainsString( '--affibloom-density-padding: 26px;', $root );
		self::assertStringEndsWith( '--affibloom-layout-columns: 3;}', $root );
		self::assertSame(
			str_replace( ' --', '--', DisplayPresentation::wrapper( self::site(), 3, true )['style'] ),
			substr( $root, 6, -1 )
		);
	}

	/**
	 * Lasso's <head> block and late net print each value !important (Helper::get_lasso_display_css_variables( true )).
	 */
	public function test_the_root_block_can_mark_each_value_important(): void {
		$root = DisplayPresentation::root( self::site(), 3, true );

		self::assertStringStartsWith( ':root{--affibloom-main: #000000 !important;--affibloom-title: #000000 !important;', $root );
		self::assertStringEndsWith( '--affibloom-layout-columns: 3 !important;}', $root );
	}
}
