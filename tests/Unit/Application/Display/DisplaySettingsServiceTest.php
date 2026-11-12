<?php
namespace Affibloom\Tests\Unit\Application\Display;

use Affibloom\Tests\Unit\TestCase;
use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Domain\Display\Density;
use Affibloom\Domain\Display\DisplayConfig;
use Affibloom\Domain\Display\DisplaySettings;
use Affibloom\Domain\Display\DisplaySettingsRepository;
use Affibloom\Domain\Display\DisplayType;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Domain\Settings\SettingsRepository;

final class DisplaySettingsServiceTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\Functions\when( 'sanitize_hex_color' )->alias(
			static fn( $c ) => preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', (string) $c ) ? (string) $c : null
		);
	}

	/**
	 * The service, with site-wide settings where Show Price is as given.
	 */
	private function service( DisplaySettingsRepository $repo, bool $show_price = true ): DisplaySettingsService {
		$flat = new class() implements SettingsRepository {
			public Settings $stored;
			public function __construct() {
				$this->stored = Settings::from_array( array() );
			}
			public function get(): Settings {
				return $this->stored;
			}
			public function save( Settings $settings ): void {
				$this->stored = $settings;
			}
		};
		$flat->stored = $flat->stored->with( array( 'show_price' => $show_price ) );

		return new DisplaySettingsService( $repo, $flat );
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
		$refused = ( $this->service( $repo ) )->update_type(
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
		$refused = ( $this->service( $repo ) )->update_type(
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
		( $this->service( $repo ) )->update_type(
			DisplayType::list(),
			array( 'palette' => array( 'primary_button_text' => $label ) )
		);

		self::assertSame( $label, $repo->get()->config( DisplayType::list() )->palette()->value( 'primary_button_text' ) );
	}

	public function test_an_unticked_element_is_hidden_when_the_elements_were_on_the_form(): void {
		$repo = $this->repository();
		( $this->service( $repo ) )->update_type(
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
		( $this->service( $repo ) )->update_type( DisplayType::single(), array( 'theme' => 'Lab' ) );

		self::assertSame( 10, $repo->get()->config( DisplayType::single() )->elements()->shown() );
	}

	public function test_choosing_a_preset_stores_the_preset(): void {
		$repo = $this->repository();
		( $this->service( $repo ) )->update_type(
			DisplayType::grid(),
			array( 'density' => array( 'preset' => 'compact', 'values' => Density::PRESETS['compact'] ) )
		);

		self::assertSame( 'compact', $repo->get()->config( DisplayType::grid() )->density()->preset_name() );
	}

	public function test_an_edited_value_makes_the_preset_custom_and_only_the_edit_is_clamped(): void {
		$repo = $this->repository();
		$repo->stored = $repo->stored->with_config(
			DisplayType::single(),
			DisplayConfig::defaults( DisplayType::single() )->with_density( Density::preset( 'spacious' ) )
		);

		$values        = Density::PRESETS['spacious'];
		$values['gap'] = 99; // edited, and over the maximum of 48
		( $this->service( $repo ) )->update_type(
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
		( $this->service( $repo ) )->update_type(
			DisplayType::list(),
			array( 'density' => array( 'base' => 'spacious', 'values' => $values ) )
		);
		$density = $repo->get()->config( DisplayType::list() )->density();

		self::assertSame( 'custom', $density->preset_name() );
		self::assertSame( 30, $density->value( 'gap' ) );
		self::assertSame( 320, $density->value( 'imageHeight' ) );
	}

	public function test_it_saves_the_desktop_layout_with_lassos_coercion(): void {
		$repo = $this->repository();
		( $this->service( $repo ) )->update_type(
			DisplayType::single(),
			array(
				'layout' => array(
					'desktop' => array(
						'orientation'   => 'stacked',
						'imagePosition' => 'left', // not a stacked position: becomes top
						'ctaWidth'      => 'full',
					),
				),
			)
		);
		$layout = $repo->get()->config( DisplayType::single() )->layout();

		self::assertSame( 'stacked', $layout->value( 'orientation' ) );
		self::assertSame( 'top', $layout->value( 'imagePosition' ) );
		self::assertSame( 'full', $layout->value( 'ctaWidth' ) );
	}

	public function test_the_posted_phone_overrides_replace_the_stored_ones(): void {
		$repo    = $this->repository();
		$service = $this->service( $repo );
		$service->update_type(
			DisplayType::list(),
			array( 'layout' => array( 'phone_present' => '1', 'phone' => array( 'align' => 'center', 'ctaWidth' => 'full' ) ) )
		);
		$service->update_type(
			DisplayType::list(),
			array( 'layout' => array( 'phone_present' => '1', 'phone' => array( 'align' => 'right' ) ) )
		);

		self::assertSame( array( 'align' => 'right' ), $repo->get()->config( DisplayType::list() )->layout()->phone() );
	}

	public function test_use_desktop_layout_clears_the_phone_overrides(): void {
		$repo    = $this->repository();
		$service = $this->service( $repo );
		$service->update_type( DisplayType::single(), array( 'layout' => array( 'phone_present' => '1', 'phone' => array( 'align' => 'center' ) ) ) );
		$service->update_type( DisplayType::single(), array( 'layout' => array( 'phone_present' => '1' ) ) ); // nothing posted: cleared

		self::assertSame( array(), $repo->get()->config( DisplayType::single() )->layout()->phone() );
	}

	public function test_the_phone_overrides_are_left_alone_when_the_form_did_not_carry_them(): void {
		$repo    = $this->repository();
		$service = $this->service( $repo );
		$service->update_type( DisplayType::single(), array( 'layout' => array( 'phone_present' => '1', 'phone' => array( 'align' => 'center' ) ) ) );
		$service->update_type( DisplayType::single(), array( 'theme' => 'Lab' ) );

		self::assertSame( array( 'align' => 'center' ), $repo->get()->config( DisplayType::single() )->layout()->phone() );
	}

	public function test_the_column_count_is_site_wide_and_clamped(): void {
		$repo    = $this->repository();
		$service = $this->service( $repo );
		$service->update_type( DisplayType::grid(), array( 'columns' => '2' ) );
		self::assertSame( 2, $repo->get()->columns() );

		$service->update_type( DisplayType::grid(), array( 'columns' => '7' ) );
		self::assertSame( 3, $repo->get()->columns() );
	}

	public function test_price_that_was_never_saved_follows_show_price(): void {
		$repo = $this->repository();

		self::assertFalse( $this->service( $repo, false )->config( DisplayType::grid() )->elements()->visible( 'price' ) );
		self::assertTrue( $this->service( $repo, true )->config( DisplayType::grid() )->elements()->visible( 'price' ) );
	}

	public function test_reset_returns_every_type_to_the_defaults(): void {
		$repo    = $this->repository();
		$service = $this->service( $repo );
		$service->update_type( DisplayType::single(), array( 'theme' => 'Lab', 'columns' => '1' ) );

		$service->reset();

		self::assertSame( 'Cactus', $repo->get()->config( DisplayType::single() )->theme()->name() );
		self::assertSame( 3, $repo->get()->columns() );
	}
}
