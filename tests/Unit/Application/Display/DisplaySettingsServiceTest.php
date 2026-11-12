<?php
namespace Affibloom\Tests\Unit\Application\Display;

use Affibloom\Tests\Unit\TestCase;
use Affibloom\Application\Display\DisplaySettingsService;
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
		self::assertSame( 'white', $repo->get()->config( DisplayType::grid() )->palette()->value( 'background' ) ); // kept its default
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
}
