<?php
namespace Affibloom\Tests\Unit\Domain\Icons;

use Affibloom\Domain\Icons\IconText;
use PHPUnit\Framework\TestCase;

final class IconTextTest extends TestCase {
	/**
	 * The shapes on this site (F28 requirements section 2): one leading empty span, a space, then the words.
	 *
	 * @dataProvider splits
	 */
	public function test_a_text_is_split_into_its_icon_and_words( string $value, string $css_class, string $words ): void {
		$text = IconText::split( $value );

		self::assertNotNull( $text );
		self::assertSame( $css_class, $text->css_class );
		self::assertSame( $words, $text->words );
		self::assertSame( $value, $text->compose() );
	}

	/** @return array<string, array{string, string, string}> */
	public static function splits(): array {
		return array(
			'icon and words' => array( '<span class="amazon-icon"></span> Buy Now', 'amazon-icon', 'Buy Now' ),
			'words only'     => array( 'Check Price', '', 'Check Price' ),
			'empty'          => array( '', '', '' ),
			'unknown class'  => array( '<span class="madel-icon"></span> Best Overall', 'madel-icon', 'Best Overall' ),
			'two classes'    => array( '<span class="tick mark-icon"></span> Special Mention', 'tick mark-icon', 'Special Mention' ),
			'icon only'      => array( '<span class="medal-icon"></span>', 'medal-icon', '' ),
		);
	}

	public function test_a_span_with_no_space_after_it_keeps_its_words(): void {
		$text = IconText::split( '<span class="medal-icon"></span>Best' );

		self::assertSame( 'Best', $text?->words );
		// Composed in the site's usual form, with the space.
		self::assertSame( '<span class="medal-icon"></span> Best', $text?->compose() );
	}

	/**
	 * Anything else is left to the plain field (requirements B5).
	 *
	 * @dataProvider not_split
	 */
	public function test_other_html_is_not_split( string $value ): void {
		self::assertNull( IconText::split( $value ) );
	}

	/** @return array<string, array{string}> */
	public static function not_split(): array {
		return array(
			'span in the middle' => array( 'Buy <span class="amazon-icon"></span> Now' ),
			'span with text'     => array( '<span class="amazon-icon">A</span> Buy' ),
			'two spans'          => array( '<span class="a-icon"></span> <span class="b-icon"></span> Buy' ),
			'bold'               => array( '<b>Buy</b>' ),
			'other attribute'    => array( '<span class="a-icon" style="x"></span> Buy' ),
		);
	}

	public function test_choosing_another_icon_or_none_keeps_the_words(): void {
		$text = IconText::split( '<span class="madel-icon"></span> Best Overall' );

		self::assertSame( '<span class="medal-icon"></span> Best Overall', $text?->with_icon( 'medal-icon' )->compose() );
		self::assertSame( 'Best Overall', $text?->with_icon( '' )->compose() );
	}
}
