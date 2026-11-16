<?php
namespace Affibloom\Tests\Unit\Application\Box;

use Affibloom\Application\Box\BoxBuilder;
use Affibloom\Application\Box\BoxFields;
use Affibloom\Application\Box\BoxView;
use Affibloom\Domain\Box\BoxOverrides;
use Affibloom\Domain\Box\LayoutFamily;
use Affibloom\Domain\Display\DisplaySettings;
use Affibloom\Domain\Display\Theme;
use Affibloom\Domain\Fields\FieldLibrary;
use Affibloom\Domain\Fields\FieldType;
use Affibloom\Domain\Fields\ProductFields;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Button;
use Affibloom\Domain\Product\Buttons;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Settings\Settings;
use PHPUnit\Framework\TestCase;

/**
 * The Single box's view from a product, the Display settings and the site's settings (F5 R6, R9-R12, R15-R17,
 * R22, R24-R25, R37-R41, R54; Task 18A).
 */
final class BoxBuilderTest extends TestCase {

	private const THUMBNAIL = 'https://site.test/wp-content/plugins/affibloom/assets/front/images/no-thumbnail.webp';

	/**
	 * The captured amazon box's product.
	 *
	 * @param array<string, mixed> $changes Properties to change.
	 */
	private static function product( array $changes = array() ): Product {
		$product = new Product(
			id: 14727,
			name: 'adidas Ligra 8 Kids',
			url: AffiliateUrl::from_string( 'https://www.amazon.com/dp/B0D2JFK15W?tag=vbvault-20' ),
			type: ProductType::Amazon,
			new_tab: true,
			nofollow: true,
			sponsored: false,
			show_disclosure: true,
			asin: 'B0D2JFK15W',
			image_url: 'https://m.media-amazon.com/images/I/31Prn-XVTjL._SL500_.jpg',
			badge: 'Best for Kids & Youth',
			description: 'The junior version of the Ligra 8.',
			prime: true,
			slug: 'adidas-ligra-8-kids',
		);

		return $product->with( $changes );
	}

	/**
	 * The site's Single settings: Lab, the icon span in the button label.
	 */
	private static function display( string $theme = 'Lab' ): DisplaySettings {
		return DisplaySettings::from_array(
			array(
				'single' => array(
					'theme'   => $theme,
					'palette' => array( 'primary_button_text' => '<span class="amazon-icon"></span> Buy Now' ),
				),
			)
		);
	}

	/**
	 * The site's settings, as Lasso's here: price off, Prime on.
	 *
	 * @param array<string, mixed> $changes Settings to change.
	 */
	private static function settings( array $changes = array() ): Settings {
		return Settings::from_array( array_merge( array( 'show_price' => false ), $changes ) );
	}

	/**
	 * Builds the box.
	 *
	 * @param Product|null         $product  The product; the amazon box's by default.
	 * @param DisplaySettings|null $display  The Display settings.
	 * @param Settings|null        $settings The site's settings.
	 */
	private static function build( ?Product $product = null, ?DisplaySettings $display = null, ?Settings $settings = null ): BoxView {
		return ( new BoxBuilder( self::THUMBNAIL ) )->build(
			$product ?? self::product(),
			$display ?? self::display(),
			$settings ?? self::settings(),
			BoxOverrides::from_block( array( 'productId' => 14727 ) )
		);
	}

	public function test_the_lab_box_is_the_3_box_layout_with_its_wrapper(): void {
		$view = self::build();

		self::assertSame( LayoutFamily::ThreeBox, $view->family );
		self::assertMatchesRegularExpression( '/^affibloom-anchor-id-14727-\w+$/', $view->anchor );
		self::assertSame( 'affibloom-display affibloom-lab affibloom-url-adidas-ligra-8-kids affibloom-advanced', $view->classes );
		self::assertSame( 'side-by-side', $view->wrapper['data-layout-orientation'] );
		self::assertArrayNotHasKey( 'data-hide-disclosure', $view->wrapper );
	}

	/**
	 * Each box has its own anchor, so two boxes of one product never share an ID (R9 without link_id: uniqid()).
	 */
	public function test_two_boxes_of_one_product_have_different_anchors(): void {
		self::assertNotSame( self::build()->anchor, self::build()->anchor );
	}

	/**
	 * The product's own theme wins over the Single type's (R3, the first two steps); Cactus is the 6-box layout.
	 */
	public function test_the_products_theme_wins_over_the_display_types(): void {
		$view = self::build( self::product( array( 'theme' => Theme::from( 'cactus' ) ) ) );

		self::assertSame( LayoutFamily::SixBox, $view->family );
		self::assertStringContainsString( 'affibloom-cactus', $view->classes );
	}

	/**
	 * The Single type's theme draws when the product has none (R3); Cutter is the 2-box layout (R5, Task 18E).
	 */
	public function test_the_display_types_theme_draws_the_2_box_layout(): void {
		$view = self::build( null, self::display( 'Cutter' ) );

		self::assertSame( LayoutFamily::TwoBox, $view->family );
		self::assertSame( 'affibloom-display affibloom-cutter affibloom-url-adidas-ligra-8-kids affibloom-advanced', $view->classes );
	}

	public function test_the_image_title_and_buttons_link_to_the_product(): void {
		$view = self::build();

		self::assertSame( 'https://www.amazon.com/dp/B0D2JFK15W?tag=vbvault-20', $view->link['href'] );
		self::assertSame( 'nofollow noopener', $view->link['rel'] );
		self::assertSame( 'https://m.media-amazon.com/images/I/31Prn-XVTjL._SL500_.jpg', $view->image_src );
		self::assertSame( 'adidas Ligra 8 Kids', $view->image_alt );
		self::assertSame( 'adidas Ligra 8 Kids', $view->title );
		self::assertTrue( $view->title_linked );
		self::assertCount( 1, $view->buttons );
		self::assertSame( 'affibloom-button-1', $view->buttons[0]->css_class );
		self::assertSame( $view->link, $view->buttons[0]->attributes );
	}

	/**
	 * An empty button text takes the Single type's label (R17, R41); the product's own text is kept.
	 */
	public function test_the_button_text_falls_back_to_the_display_types_label(): void {
		self::assertSame( '<span class="amazon-icon"></span> Buy Now', self::build()->buttons[0]->text );
		self::assertSame( 'Shop', self::build( self::product( array( 'button_text' => 'Shop' ) ) )->buttons[0]->text );
	}

	/**
	 * Lasso decodes the name before printing it (R12); the template escapes it again.
	 */
	public function test_the_title_is_decoded_and_can_be_a_plain_span(): void {
		$view = self::build( self::product( array( 'name' => 'Nike Women&#8217;s &amp; Co' ) ), null, self::settings( array( 'link_from_display_title' => false ) ) );

		self::assertSame( "Nike Women\u{2019}s & Co", $view->title );
		self::assertFalse( $view->title_linked );
	}

	/**
	 * No image: the site's default thumbnail, else Affibloom's own (D14).
	 */
	public function test_a_product_without_an_image_shows_the_default_thumbnail(): void {
		$product = self::product( array( 'image_url' => '' ) );

		self::assertSame( self::THUMBNAIL, self::build( $product )->image_src );
		self::assertSame( 'https://site.test/none.png', self::build( $product, null, self::settings( array( 'default_thumbnail' => 'https://site.test/none.png' ) ) )->image_src );
	}

	/**
	 * The price block prints for Prime (R20 with D7: the site's Show Prime and the product's flag) or for a shown price.
	 */
	public function test_the_price_block_holds_prime_or_a_shown_price(): void {
		$view = self::build();
		self::assertTrue( $view->prime );
		self::assertTrue( $view->price_block );
		self::assertSame( '', $view->price );

		$no_prime = self::build( null, null, self::settings( array( 'show_prime' => false ) ) );
		self::assertFalse( $no_prime->prime );
		self::assertFalse( $no_prime->price_block );

		$priced = self::build(
			self::product(
				array(
					'prime'      => false,
					'price'      => '$59.99',
					'show_price' => true,
				)
			)
		);
		self::assertSame( '$59.99', $priced->price );
		self::assertTrue( $priced->price_block );

		// The product's Show Price unset follows the site's (R19).
		self::assertSame( '', self::build( self::product( array( 'price' => '$59.99' ) ) )->price );
	}

	public function test_the_description_follows_lassos_rule(): void {
		self::assertSame( 'The junior version of the Ligra 8.', self::build()->description );
		self::assertSame( '', self::build( self::product( array( 'description' => '' ) ) )->description );
		self::assertNull(
			self::build(
				self::product(
					array(
						'description'      => '',
						'show_description' => false,
					)
				)
			)->description
		);
	}

	/**
	 * The product's text, trimmed, else the site's; shown by the product's flag, and flagged on the wrapper when not
	 * (R24, R25).
	 */
	public function test_the_disclosure_takes_the_products_text_else_the_sites(): void {
		self::assertSame( 'We earn a commission if you make a purchase, at no additional cost to you.', self::build()->disclosure );
		self::assertSame( 'Ad.', self::build( self::product( array( 'disclosure' => ' Ad. ' ) ) )->disclosure );

		$hidden = self::build( self::product( array( 'show_disclosure' => false ) ) );
		self::assertNull( $hidden->disclosure );
		self::assertSame( '1', $hidden->wrapper['data-hide-disclosure'] );
	}

	/**
	 * The second button (F6 AC1, AC3): its own link and text, Lasso's secondary `rel` (sponsored only with its own
	 * NoFollow), its number before `rel` (F12's name for Lasso's `data-lasso-button`).
	 */
	public function test_the_second_button_has_its_own_link_and_lassos_secondary_attributes(): void {
		$product = self::product(
			array(
				'sponsored' => true,
				'buttons'   => new Buttons( array( new Button( 'https://www.example.com/womens', "Buy Now Women's Shoe", false, true ) ) ),
			)
		);
		$second  = self::build( $product )->buttons[1];

		self::assertSame( 'affibloom-button-2', $second->css_class );
		self::assertSame( "Buy Now Women's Shoe", $second->text );
		self::assertSame(
			array(
				'target'                       => '_self',
				'href'                         => 'https://www.example.com/womens',
				'data-affibloom-box-trackable' => 'true',
				'data-affibloom-id'            => '14727',
				'data-affibloom-name'          => 'adidas Ligra 8 Kids',
				'title'                        => 'adidas Ligra 8 Kids',
				'data-affibloom-btn'           => '2',
				'rel'                          => 'nofollow sponsored',
			),
			$second->attributes
		);

		$no_follow = self::build(
			self::product(
				array(
					'sponsored' => true,
					'buttons'   => new Buttons( array( new Button( 'https://www.example.com/a', 'A', true, false ) ) ),
				)
			)
		);
		self::assertSame( 'noopener', $no_follow->buttons[1]->attributes['rel'] );
	}

	/**
	 * F6 AC2, AC4: no button without a URL or to the post the box is in; an empty text takes the Single type's secondary
	 * label, and with that empty too the button has neither `href` nor `target`.
	 */
	public function test_a_second_button_needs_a_url_off_this_post_and_falls_back_to_the_label(): void {
		$builder = new BoxBuilder( self::THUMBNAIL, static fn( string $url ): int => 'https://site.test/this-guide/' === $url ? 77 : 0 );
		$build   = static fn( array $buttons, int $post, ?DisplaySettings $display = null ): BoxView => $builder->build(
			self::product( array( 'buttons' => new Buttons( $buttons ) ) ),
			$display ?? self::display(),
			self::settings(),
			BoxOverrides::from_block( array( 'productId' => 14727 ) ),
			$post
		);

		self::assertCount( 1, $build( array( new Button( '', 'Text only' ) ), 77 )->buttons );
		self::assertCount( 1, $build( array( new Button( 'https://site.test/this-guide/', 'Here' ) ), 77 )->buttons );
		self::assertCount( 2, $build( array( new Button( 'https://site.test/this-guide/', 'Here' ) ), 0 )->buttons );

		self::assertSame( 'Our Review', $build( array( new Button( 'https://www.example.com/b', '' ) ), 77 )->buttons[1]->text );
		$blank = DisplaySettings::from_array( array( 'single' => array( 'palette' => array( 'secondary_button_text' => '' ) ) ) );
		$bare  = $build( array( new Button( 'https://www.example.com/b', '' ) ), 77, $blank )->buttons[1]->attributes;
		self::assertArrayNotHasKey( 'href', $bare );
		self::assertArrayNotHasKey( 'target', $bare );
	}

	/**
	 * F6 AC5, F29 AC3: every button in the product's order, the default one where it was put; the first printed is
	 * button 1, the others button 2 with their number.
	 */
	public function test_every_button_prints_in_the_products_order(): void {
		$buttons = array( new Button( 'https://www.example.com/a', 'A' ), new Button( 'https://www.example.com/b', 'B' ) );
		$view    = self::build( self::product( array( 'buttons' => new Buttons( $buttons, 1 ) ) ) );

		self::assertSame( array( 'affibloom-button-1', 'affibloom-button-2', 'affibloom-button-2' ), array_map( static fn( $b ) => $b->css_class, $view->buttons ) );
		self::assertSame( array( 'A', '<span class="amazon-icon"></span> Buy Now', 'B' ), array_map( static fn( $b ) => $b->text, $view->buttons ) );
		self::assertArrayNotHasKey( 'data-affibloom-btn', $view->buttons[0]->attributes );
		self::assertSame( 'https://www.amazon.com/dp/B0D2JFK15W?tag=vbvault-20', $view->buttons[1]->attributes['href'] );
		self::assertSame( '2', $view->buttons[1]->attributes['data-affibloom-btn'] );
		self::assertSame( '3', $view->buttons[2]->attributes['data-affibloom-btn'] );

		$last = self::build( self::product( array( 'buttons' => new Buttons( $buttons, 2 ) ) ) );
		self::assertSame( 'https://www.amazon.com/dp/B0D2JFK15W?tag=vbvault-20', $last->buttons[2]->attributes['href'] );
	}

	/**
	 * F7 AC1-AC4 (Task 20A): the box's badge, description, title, title link and tag, picture and alt replace the
	 * product's, in the links' names too; each `hide` hides its element through the wrapper (R60).
	 */
	public function test_the_boxs_texts_and_image_replace_the_products(): void {
		$box  = static fn( array $attributes ): BoxView => ( new BoxBuilder( self::THUMBNAIL ) )->build(
			self::product(),
			self::display(),
			self::settings(),
			BoxOverrides::from_block( array( 'productId' => 14727 ) + $attributes )
		);
		$view = $box(
			array(
				'badge'       => 'Top Pick',
				'description' => 'Short.lasso-br-codeSweet.',
				'title'       => 'Ligra Kids',
				'titleUrl'    => 'https://www.example.com/review',
				'titleType'   => 'h3',
				'imageUrl'    => 'https://www.example.com/p.jpg',
			)
		);

		self::assertSame( 'Top Pick', $view->badge );
		self::assertSame( 'Short.<br>Sweet.', $view->description );
		self::assertSame( 'Ligra Kids', $view->title );
		self::assertSame( 'Ligra Kids', $view->image_alt );
		self::assertSame( 'Ligra Kids', $view->link['title'] );
		self::assertSame( 'Ligra Kids', $view->buttons[0]->attributes['data-affibloom-name'] );
		self::assertSame( 'https://www.example.com/review', $view->link['href'] );
		self::assertSame( '_blank', $view->link['target'] );
		self::assertSame( 'https://www.amazon.com/dp/B0D2JFK15W?tag=vbvault-20', $view->buttons[0]->attributes['href'] );
		self::assertSame( 'h3', $view->title_tag );
		self::assertSame( 'https://www.example.com/p.jpg', $view->image_src );
		self::assertArrayNotHasKey( 'data-hide-badge', $view->wrapper );

		$hidden = $box(
			array(
				'hideBadge'       => true,
				'hideDescription' => true,
				'hideTitle'       => true,
				'hideImage'       => true,
				'imageAlt'        => 'A white shoe',
			)
		);
		self::assertSame( '', $hidden->badge );
		self::assertNull( $hidden->description );
		self::assertSame( '', $hidden->title );
		self::assertSame( 'Amazon', $hidden->link['data-affibloom-name'] ); // Lasso's domain label for an empty name
		self::assertSame( 'https://m.media-amazon.com/images/I/31Prn-XVTjL._SL500_.jpg', $hidden->image_src );
		self::assertSame( 'A white shoe', $hidden->image_alt );
		foreach ( array( 'badge', 'title', 'desc', 'image' ) as $element ) {
			self::assertSame( '1', $hidden->wrapper[ 'data-hide-' . $element ] );
		}
	}

	/**
	 * R67: a box's description forces the block on, even when the product's Show Description is off.
	 */
	public function test_a_boxs_description_forces_the_block_on(): void {
		$view = ( new BoxBuilder( self::THUMBNAIL ) )->build(
			self::product( array( 'show_description' => false, 'description' => '' ) ),
			self::display(),
			self::settings(),
			BoxOverrides::from_block( array( 'productId' => 14727, 'description' => 'Box text' ) )
		);

		self::assertSame( 'Box text', $view->description );
	}

	/**
	 * F8 AC1-AC4 (Task 21A): the Primary Rating's visible row draws the stars, its value with one decimal, the field's
	 * name when the row shows it; nothing without a value, hidden or not a number.
	 */
	public function test_the_primary_rating_draws_the_stars(): void {
		$stars = static function ( array $row, array $names = array() ) {
			$fields = new BoxFields( ProductFields::from_array( array( array( 'field' => 1 ) + $row ) ), FieldLibrary::from_array( array(), $names ) );

			return ( new BoxBuilder( self::THUMBNAIL ) )->build( self::product(), self::display(), self::settings(), BoxOverrides::from_block( array( 'productId' => 14727 ) ), 0, $fields )->stars;
		};

		$plain = $stars( array( 'value' => '4.5' ) );
		self::assertSame( '4.5', $plain->rating );
		self::assertSame( '4.5', $plain->value );
		self::assertSame( '', $plain->label );

		self::assertSame( '4.0', $stars( array( 'value' => '4' ) )->value );
		self::assertSame( 'Our Rating', $stars( array( 'value' => '3', 'show_name' => true ), array( 1 => 'Our Rating' ) )->label );
		self::assertSame( 'Primary Rating', $stars( array( 'value' => '3', 'show_name' => true ) )->label );
		self::assertNull( $stars( array( 'value' => '' ) ) );
		self::assertNull( $stars( array( 'value' => '4.5', 'visible' => false ) ) );
		self::assertNull( $stars( array( 'value' => '4; color: red' ) ) );
		self::assertNull( self::build()->stars );
	}

	/**
	 * F5 R61-R63 (Task 18J): a hidden Title or Description element prints no title or description block; the links keep
	 * the name; the badge takes the override class under a hidden Badge element.
	 */
	public function test_hidden_title_description_and_badge_elements(): void {
		$display = DisplaySettings::from_array( array( 'single' => array( 'theme' => 'Lab', 'elements' => array( 'title' => false, 'desc' => false, 'badge' => false ) ) ) );
		$view    = self::build( null, $display );

		self::assertSame( '', $view->title );
		self::assertSame( 'adidas Ligra 8 Kids', $view->link['data-affibloom-name'] );
		self::assertNull( $view->description );
		self::assertSame( 'Best for Kids & Youth', $view->badge );
		self::assertTrue( $view->badge_override );
		self::assertSame( '1', $view->wrapper['data-hide-badge'] );
		self::assertFalse( self::build()->badge_override );
	}

	/**
	 * F5 R27, R64 (Task 18K): the classic box has no v2 class or wrapper attributes, shows every element by its own
	 * data, and shows the timestamp only with a printed price; a box-hidden picture is hidden by the box itself.
	 */
	public function test_the_classic_box_has_no_wrapper_and_classic_rules(): void {
		$classic = DisplaySettings::from_array( array( 'single' => array( 'elements' => array( 'title' => false ) ) ) )->with_advanced( false );
		$view    = self::build( null, $classic );

		self::assertStringNotContainsString( 'affibloom-advanced', $view->classes );
		self::assertSame( array(), $view->wrapper );
		self::assertSame( 'adidas Ligra 8 Kids', $view->title ); // the Title element does not apply
		self::assertNull( $view->date ); // no price printed
		self::assertFalse( $view->image_hidden );

		$priced = self::build( self::product( array( 'price' => '$89.99', 'show_price' => true ) ), $classic );
		self::assertSame( '', $priced->date ); // shown, no Amazon data yet

		$hidden = ( new BoxBuilder( self::THUMBNAIL ) )->build( self::product(), $classic, self::settings(), BoxOverrides::from_block( array( 'productId' => 14727, 'hideImage' => true ) ) );
		self::assertTrue( $hidden->image_hidden );
	}

	/**
	 * F7 AC5-AC9 (Task 20B): the box's theme, button URLs and texts (`hide` hides), tracking ID (the product's Amazon
	 * links only, before the box's URLs), disclosure text (shown), anchor ID or link ID, and style.
	 */
	public function test_the_boxs_theme_buttons_tracking_disclosure_anchor_and_style(): void {
		$box = static fn( array $attributes, ?Product $product = null ): BoxView => ( new BoxBuilder( self::THUMBNAIL ) )->build(
			$product ?? self::product( array( 'buttons' => new Buttons( array( new Button( 'https://www.amazon.com/dp/B0SECOND00?tag=vbvault-20', 'Check Price' ) ) ) ) ),
			self::display(),
			self::settings(),
			BoxOverrides::from_block( array( 'productId' => 14727 ) + $attributes )
		);

		$view = $box( array( 'theme' => 'Money', 'trackingId' => 'box-20', 'disclosureText' => 'Box disclosure.', 'style' => 'mobile', 'linkId' => '77' ) );
		self::assertSame( LayoutFamily::SixBox, $view->family );
		self::assertStringContainsString( 'affibloom-url-adidas-ligra-8-kids mobile affibloom-advanced', $view->classes );
		self::assertSame( 'https://www.amazon.com/dp/B0D2JFK15W?tag=box-20', $view->buttons[0]->attributes['href'] );
		self::assertSame( 'https://www.amazon.com/dp/B0D2JFK15W?tag=box-20', $view->link['href'] );
		self::assertSame( 'https://www.amazon.com/dp/B0SECOND00?tag=box-20', $view->buttons[1]->attributes['href'] );
		self::assertSame( 'Box disclosure.', $view->disclosure );
		self::assertSame( 'affibloom-anchor-id-14727-77', $view->anchor );

		$urls = $box( array( 'primaryUrl' => 'https://www.example.com/p', 'primaryText' => 'Go', 'secondaryUrl' => 'https://www.example.com/s', 'secondaryText' => 'More', 'trackingId' => 'box-20', 'anchorId' => 'mine' ) );
		self::assertSame( 'https://www.example.com/p', $urls->buttons[0]->attributes['href'] );
		self::assertSame( 'https://www.example.com/p', $urls->link['href'] );
		self::assertSame( 'Go', $urls->buttons[0]->text );
		self::assertSame( 'https://www.example.com/s', $urls->buttons[1]->attributes['href'] );
		self::assertSame( 'More', $urls->buttons[1]->text );
		self::assertSame( 'mine', $urls->anchor );

		// A second button from the box alone, and `hide` hiding each button through the wrapper.
		$added = $box( array( 'secondaryUrl' => 'https://www.example.com/s' ), self::product() );
		self::assertCount( 2, $added->buttons );
		self::assertSame( 'Our Review', $added->buttons[1]->text );
		$hidden = $box( array( 'primaryText' => 'hide', 'secondaryText' => 'hide' ) );
		self::assertSame( '1', $hidden->wrapper['data-hide-primary'] );
		self::assertSame( '1', $hidden->wrapper['data-hide-secondary'] );
	}
	/**
	 * F5 R23, R30-R34 (Task 21B): the visible rows in order, by type, without the Primary Rating, hidden rows, table-only
	 * fields and Copy to Clipboard (21C).
	 */
	public function test_the_fields_block_holds_the_visible_rows_by_type(): void {
		list( $library, $text )    = FieldLibrary::from_array( array() )->create( 'Upper', FieldType::Text, '' );
		list( $library, $list )    = $library->create( 'Steps', FieldType::NumberedList, '' );
		list( $library, $hidden )  = $library->create( 'Secret', FieldType::Text, '' );
		list( $library, $copy )    = $library->create( 'Code', FieldType::CopyClipboard, '' );
		$values = ProductFields::from_array(
			array(
				array( 'field' => 1, 'value' => '4.5' ),
				array( 'field' => $text->id, 'value' => 'Mesh' ),
				array( 'field' => 2, 'value' => "Light\nQuick" ),
				array( 'field' => $list->id, 'value' => "A\nB" ),
				array( 'field' => $hidden->id, 'value' => 'No', 'visible' => false ),
				array( 'field' => $copy->id, 'value' => 'VB10' ),
				array( 'field' => 99994, 'value' => '$1' ),
			)
		);
		$view = ( new BoxBuilder( self::THUMBNAIL ) )->build( self::product(), self::display(), self::settings(), BoxOverrides::from_block( array( 'productId' => 14727 ) ), 0, new BoxFields( $values, $library ) );

		self::assertSame(
			array( array( 'text', 'Upper' ), array( 'pros-cons', 'Pros' ), array( 'numbered', 'Steps' ) ),
			array_map( static fn( $f ) => array( $f->kind, $f->name ), $view->fields )
		);
		self::assertSame( array( 'Light', 'Quick' ), $view->fields[1]->lines() );
	}

	/**
	 * F7 AC10-AC12 (Task 20C): the box's price (hide, show, value), Prime, basis price (with Show Discount Pricing),
	 * rating (replace, add, hide), Pros and Cons (replace, add, rename) and fields (hide, demo).
	 */
	public function test_the_boxs_price_rating_pros_cons_and_fields(): void {
		$box = static function ( array $attributes, array $rows = array(), ?Product $product = null, array $settings = array() ): BoxView {
			$fields = new BoxFields( ProductFields::from_array( $rows ), FieldLibrary::from_array( array() ) );

			return ( new BoxBuilder( self::THUMBNAIL ) )->build( $product ?? self::product(), self::display(), self::settings( $settings ), BoxOverrides::from_block( array( 'productId' => 14727 ) + $attributes ), 0, $fields );
		};

		$value = $box( array( 'price' => '$79.99', 'basisPrice' => '$99.99' ), array(), null, array( 'show_discount_pricing' => true ) );
		self::assertSame( '$79.99', $value->price );
		self::assertSame( '$99.99', $value->discount );
		self::assertSame( '', $box( array( 'price' => '$79.99', 'basisPrice' => '$99.99' ) )->discount ); // the setting is off
		self::assertSame( '', $box( array( 'price' => 'hide' ), array(), self::product( array( 'price' => '$5' ) ), array( 'show_price' => true ) )->price );
		self::assertSame( '$5', $box( array( 'price' => 'show' ), array(), self::product( array( 'price' => '$5', 'show_price' => false ) ) )->price );
		self::assertTrue( $box( array( 'prime' => true ), array(), self::product( array( 'type' => ProductType::Other, 'prime' => false ) ) )->prime );

		self::assertSame( '3.0', $box( array( 'rating' => '3' ), array( array( 'field' => 1, 'value' => '4.5' ) ) )->stars->value );
		self::assertSame( '3.0', $box( array( 'rating' => '3' ) )->stars->value ); // added
		self::assertNull( $box( array( 'rating' => 'hide' ), array( array( 'field' => 1, 'value' => '4.5' ) ) )->stars );

		$pros = $box( array( 'pros' => 'Light', 'consLabel' => 'Watch out' ), array( array( 'field' => 3, 'value' => 'Small' ) ) );
		self::assertSame( array( array( 3, 'Watch out', 'Small' ), array( 2, 'Pros', 'Light' ) ), array_map( static fn( $f ) => array( $f->id, $f->name, $f->value ), $pros->fields ) );
		self::assertSame( array(), $box( array( 'fields' => 'hide' ), array( array( 'field' => 2, 'value' => 'A' ) ) )->fields );
		self::assertCount( 2, $box( array( 'fields' => 'demo', 'showProsCons' => true ) )->fields );
		self::assertSame( array(), $box( array( 'fields' => 'demo', 'showProsCons' => false ) )->fields );
		self::assertSame( '1', $box( array( 'showProsCons' => false ) )->wrapper['data-hide-proscons'] );
	}
}
