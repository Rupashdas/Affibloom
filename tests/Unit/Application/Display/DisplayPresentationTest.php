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
				'data-layout-orientation'    => 'side-by-side',
				'data-layout-image-position' => 'left',
				'data-layout-align'          => 'left',
				'data-layout-button-align'   => 'spread',
				'data-layout-cta-width'      => 'full',
				'data-layout-mobile-overflow' => 'stack',
				'data-layout-image-fit'      => 'contain',
				'data-density-title-lines'   => '0',
				'data-density-desc-lines'    => '0',
				'data-layout-m-orientation'  => 'side-by-side',
				'data-layout-m-image-position' => 'left',
				'data-layout-m-align'        => 'left',
				'data-layout-m-button-align' => 'center',
				'data-layout-m-cta-width'    => 'full',
				'data-layout-m-image-fit'    => 'contain',
				'data-hide-price'            => '1',
				'data-hide-proscons'         => '1',
				'data-hide-timestamp'        => '1',
				'style'                      => '--affibloom-main: #000000; --affibloom-title: #000000; --affibloom-button: #5e3f8c; '
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
}
