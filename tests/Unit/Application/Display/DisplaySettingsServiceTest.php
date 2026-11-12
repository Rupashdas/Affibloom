<?php
namespace Affibloom\Tests\Unit\Application\Display;

use Affibloom\Tests\Unit\TestCase;
use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Domain\Display\Density;
use Affibloom\Domain\Display\DisplayConfig;
use Affibloom\Domain\Display\DisplaySettings;
use Affibloom\Domain\Display\DisplaySettingsRepository;
use Affibloom\Domain\Display\DisplayType;

final class DisplaySettingsServiceTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\Functions\when( 'sanitize_hex_color' )->alias(
			static fn( $c ) => preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', (string) $c ) ? (string) $c : null
		);
	}

	private function repository(): DisplaySettingsRepository {
		return new class() implements DisplaySettingsRepository {
			public DisplaySettings $stored;
			public function __construct() {
				$this->stored = DisplaySettings::defaults();
			}
			public function get(): DisplaySettings {
				return $this->stored;
			}
			public function save( DisplaySettings $settings ): void {
				$this->stored = $settings;
			}
		};
	}

	public function test_it_saves_one_type_and_leaves_the_others(): void {
		$repo    = $this->repository();
		$refused = ( new DisplaySettingsService( $repo ) )->update_type(
			DisplayType::single(),
			array(
				'theme'   => 'Llama',
				'palette' => array( 'main' => '#AABBCC' ),
			)
		);

		self::assertSame( array(), $refused );
		self::assertSame( 'Llama', $repo->get()->config( DisplayType::single() )->theme()->name() );
		self::assertSame( '#aabbcc', $repo->get()->config( DisplayType::single() )->palette()->value( 'main' ) );
		self::assertSame( 'Cactus', $repo->get()->config( DisplayType::grid() )->theme()->name() );
	}

	public function test_a_css_keyword_is_kept_and_nonsense_is_refused(): void {
		$repo    = $this->repository();
		$refused = ( new DisplaySettingsService( $repo ) )->update_type(
			DisplayType::grid(),
			array( 'palette' => array( 'title' => 'White', 'background' => '#nothex', 'button' => '' ) )
		);

		self::assertSame( array( 'background', 'button' ), $refused );
		self::assertSame( 'white', $repo->get()->config( DisplayType::grid() )->palette()->value( 'title' ) );
		self::assertSame( '#ffffff', $repo->get()->config( DisplayType::grid() )->palette()->value( 'background' ) ); // kept its default
	}

	public function test_a_label_goes_through_the_icon_allowlist(): void {
		$label = '<span class="amazon-icon"></span> Buy Now';
		\Brain\Monkey\Functions\expect( 'wp_kses' )
			->once()
			->with( $label, array( 'span' => array( 'class' => array() ) ) )
			->andReturn( $label );

		$repo = $this->repository();
		( new DisplaySettingsService( $repo ) )->update_type(
			DisplayType::list(),
			array( 'palette' => array( 'primary_button_text' => $label ) )
		);

		self::assertSame( $label, $repo->get()->config( DisplayType::list() )->palette()->value( 'primary_button_text' ) );
	}

	public function test_an_unticked_element_is_hidden_when_the_elements_were_on_the_form(): void {
		$repo = $this->repository();
		( new DisplaySettingsService( $repo ) )->update_type(
			DisplayType::single(),
			array(
				'elements_present' => '1',
				'elements'         => array( 'badge' => '1', 'title' => '1' ),
			)
		);
		$elements = $repo->get()->config( DisplayType::single() )->elements();

		self::assertTrue( $elements->visible( 'badge' ) );
		self::assertFalse( $elements->visible( 'price' ) ); // not sent, so switched off
		self::assertSame( 2, $elements->shown() );
	}

	public function test_elements_are_left_alone_when_the_form_did_not_carry_them(): void {
		$repo = $this->repository();
		( new DisplaySettingsService( $repo ) )->update_type( DisplayType::single(), array( 'theme' => 'Lab' ) );

		self::assertSame( 10, $repo->get()->config( DisplayType::single() )->elements()->shown() );
	}

	public function test_choosing_a_preset_stores_the_preset(): void {
		$repo = $this->repository();
		( new DisplaySettingsService( $repo ) )->update_type(
			DisplayType::grid(),
			array( 'density' => array( 'preset' => 'compact', 'values' => Density::PRESETS['compact'] ) )
		);

		self::assertSame( 'compact', $repo->get()->config( DisplayType::grid() )->density()->preset_name() );
	}

	public function test_an_edited_value_makes_the_preset_custom_and_only_the_edit_is_clamped(): void {
		$repo = $this->repository();
		$repo->stored = $repo->stored->with_config(
			DisplayType::single(),
			DisplayConfig::defaults()->with_density( Density::preset( 'spacious' ) )
		);

		$values        = Density::PRESETS['spacious'];
		$values['gap'] = 99; // edited, and over the maximum of 48
		( new DisplaySettingsService( $repo ) )->update_type(
			DisplayType::single(),
			array( 'density' => array( 'preset' => 'spacious', 'values' => $values ) )
		);
		$density = $repo->get()->config( DisplayType::single() )->density();

		self::assertSame( 'custom', $density->preset_name() );
		self::assertSame( 48, $density->value( 'gap' ) );           // the edit, clamped
		self::assertSame( 320, $density->value( 'imageHeight' ) ); // untouched, so not clamped, as in Lasso
	}

	public function test_an_edit_after_choosing_a_preset_starts_from_that_preset(): void {
		// The editor clicks Spacious, then nudges Gap: the chips clear (custom) and the form says which preset
		// the edit started from, so the untouched values are spacious's and are not clamped.
		$repo   = $this->repository();
		$values = Density::PRESETS['spacious'];
		$values['gap'] = 30;
		( new DisplaySettingsService( $repo ) )->update_type(
			DisplayType::list(),
			array( 'density' => array( 'base' => 'spacious', 'values' => $values ) )
		);
		$density = $repo->get()->config( DisplayType::list() )->density();

		self::assertSame( 'custom', $density->preset_name() );
		self::assertSame( 30, $density->value( 'gap' ) );
		self::assertSame( 320, $density->value( 'imageHeight' ) );
	}
}
