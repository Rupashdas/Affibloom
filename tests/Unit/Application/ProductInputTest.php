<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Products\ValidationFailed;
use Affibloom\Domain\Display\Theme;
use Affibloom\Domain\Product\Buttons;
use Affibloom\Tests\Unit\Fakes\FakePageFetcher;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use Affibloom\Tests\Unit\TestCase;

/**
 * The Details form's fields, cleaned as Lasso's save_lasso_url cleans them (F1 requirements 9, S2-S6).
 */
final class ProductInputTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\Functions\when( 'sanitize_text_field' )->alias( static fn( $s ) => trim( strip_tags( (string) $s ) ) );
		\Brain\Monkey\Functions\when( 'esc_url_raw' )->alias( static fn( $u ) => str_replace( ' ', '', (string) $u ) );
		// wp_kses keeps the tags its list allows (attributes are not checked by this stand-in).
		\Brain\Monkey\Functions\when( 'wp_kses' )->alias( static fn( $s, $allowed ) => strip_tags( (string) $s, array_keys( $allowed ) ) );
		\Brain\Monkey\Functions\when( 'wp_kses_post' )->alias( static fn( $s ) => strip_tags( (string) $s, array( 'p', 'span', 'strong', 'a', 'ul', 'li' ) ) );
		\Brain\Monkey\Functions\when( 'wp_encode_emoji' )->returnArg();
		\Brain\Monkey\Functions\when( 'wp_get_attachment_url' )->alias( static fn( $id ) => 77 === $id ? 'https://volleyballvault.local/wp-content/uploads/shoe.jpg' : false );
	}

	private function input(): ProductInput {
		return new ProductInput( new LinkNormaliser( new LinkResolver( new FakePageFetcher() ), new InMemorySettingsRepository( array( 'tracking_id' => 'vbvault-20' ) ) ) );
	}

	public function test_only_the_fields_sent_change(): void {
		self::assertSame( array( 'badge' => 'Top Pick' ), $this->input()->changes( array( 'badge' => 'Top Pick' ) ) );
		self::assertSame( array(), $this->input()->changes( array( 'unknown' => 'x' ) ) );
	}

	public function test_an_empty_name_is_the_only_refusal(): void {
		try {
			$this->input()->changes(
				array(
					'name'        => '  ',
					'badge'       => str_repeat( 'long ', 200 ),
					'disclosure'  => str_repeat( 'long ', 200 ),
					'button_text' => str_repeat( 'long ', 200 ),
				)
			);
			self::fail( 'An empty name must be refused.' );
		} catch ( ValidationFailed $refused ) {
			self::assertSame( array( 'name' => 'required' ), $refused->errors ); // Lasso has no length limits
		}
	}

	public function test_texts_are_cleaned_as_lasso_cleans_them(): void {
		$changes = $this->input()->changes(
			array(
				'name'        => ' <b>Mizuno</b> Wave ',
				'disclosure'  => ' We earn from <i>qualifying</i> purchases. ',
				'button_text' => '<span class="amazon-icon"></span> Buy Now<script>alert(1)</script>',
				'badge'       => '<strong>Best</strong> <span class="medal-icon"></span>',
			)
		);

		self::assertSame( 'Mizuno Wave', $changes['name'] );
		self::assertSame( 'We earn from qualifying purchases.', $changes['disclosure'] );
		// Icon markup: the span stays, a script goes with its content, other tags go.
		self::assertSame( '<span class="amazon-icon"></span> Buy Now', $changes['button_text'] );
		self::assertSame( 'Best <span class="medal-icon"></span>', $changes['badge'] );
	}

	public function test_switches_come_as_booleans(): void {
		$changes = $this->input()->changes(
			array(
				'new_tab'         => true,
				'nofollow'        => false,
				'sponsored'       => 1,
				'show_disclosure' => '',
				'prime'           => true,
				'show_price'      => false,
			)
		);

		self::assertSame(
			array(
				'new_tab'         => true,
				'nofollow'        => false,
				'sponsored'       => true,
				'show_disclosure' => false,
				'prime'           => true,
				'show_price'      => false,
			),
			$changes
		);
	}

	public function test_the_theme_is_a_name_or_the_display_types_own(): void {
		self::assertNull( $this->input()->changes( array( 'theme' => '' ) )['theme'] );
		$theme = $this->input()->changes( array( 'theme' => 'Splash' ) )['theme'];
		self::assertInstanceOf( Theme::class, $theme );
		self::assertSame( 'Splash', $theme->name() );
	}

	public function test_the_buttons_keep_their_order_links_and_place_of_the_default_one(): void {
		$changes = $this->input()->changes(
			array(
				'buttons'          => array(
					array(
						'url'      => 'www.amazon.com/dp/B0C7654321?tag=other-20',
						'text'     => '<span class="amazon-icon"></span> Women\'s',
						'new_tab'  => false,
						'nofollow' => true,
					),
					array(
						'url'  => '',
						'text' => '',
					),
					array(
						'url'  => 'https://www.example.com/third',
						'text' => 'Third',
					),
				),
				'primary_position' => 1,
			)
		);

		$buttons = $changes['buttons'];
		self::assertInstanceOf( Buttons::class, $buttons );
		self::assertSame( 1, $buttons->main_position() );
		self::assertSame(
			array(
				array(
					'url'      => 'https://www.amazon.com/dp/B0C7654321?tag=vbvault-20',
					'text'     => '<span class="amazon-icon"></span> Women\'s',
					'new_tab'  => false,
					'nofollow' => true,
				),
				// The empty block is dropped (Buttons' rule); a block without toggles sent gets Lasso's defaults (on).
				array(
					'url'      => 'https://www.example.com/third',
					'text'     => 'Third',
					'new_tab'  => true,
					'nofollow' => true,
				),
			),
			$buttons->to_array()
		);
	}

	public function test_a_media_library_image_wins_over_the_typed_link(): void {
		self::assertSame(
			array(
				'image_url' => 'https://volleyballvault.local/wp-content/uploads/shoe.jpg',
				'image_id'  => 77,
			),
			$this->input()->changes(
				array(
					'image_url' => 'https://elsewhere.example/other.jpg',
					'image_id'  => 77,
				)
			)
		);
	}

	public function test_a_typed_image_link_detaches_the_media_item(): void {
		self::assertSame(
			array(
				'image_url' => 'https://www.example.com/shoe.jpg',
				'image_id'  => 0,
			),
			$this->input()->changes(
				array(
					'image_url' => ' https://www.example.com/shoe.jpg ',
					'image_id'  => 0,
				)
			)
		);
		// A media item that no longer exists counts as none.
		self::assertSame( 0, $this->input()->changes( array( 'image_url' => '', 'image_id' => 5 ) )['image_id'] );
	}

	public function test_the_price_keeps_lassos_tags_with_hex_colours(): void {
		// The script goes with its content (PriceHtml); a link is not one of Lasso's price tags, its text stays.
		self::assertSame(
			'<span style="color: #141414;"><b>$29.99</b></span>link',
			$this->input()->changes( array( 'price' => '<span style="color: rgb(20, 20, 20);"><b>$29.99</b></span><a href="x">link</a><script>x</script>' ) )['price']
		);
	}

	public function test_the_description_keeps_post_markup(): void {
		self::assertSame( '<p>Light.</p>x', $this->input()->changes( array( 'description' => '<p>Light.</p><img src="x">x' ) )['description'] );
	}
}
