<?php
namespace Affibloom\Tests\Unit\Domain\Display;

use Affibloom\Domain\Display\ListBullets;
use PHPUnit\Framework\TestCase;

/**
 * A list's bullets (F30 R21; Task DT4).
 */
final class ListBulletsTest extends TestCase {

	/**
	 * Each `bullets` value's element and classes; none or an unknown one (Lasso compares as written) is the numbered
	 * `affibloom-list-ol`.
	 *
	 * @dataProvider cases
	 */
	public function test_the_bullets( string $bullets, string $tag, string $classes ): void {
		$list = ListBullets::of( $bullets );
		self::assertSame( $tag, $list->tag );
		self::assertSame( $classes, $list->classes );
	}

	/**
	 * @return array<string, array{string, string, string}>
	 */
	public static function cases(): array {
		return array(
			'none'    => array( '', 'ol', 'affibloom-list-ol affibloom-list-style-decimal' ),
			'decimal' => array( 'decimal', 'ol', 'affibloom-list affibloom-list-style-decimal' ),
			'alpha'   => array( 'alpha', 'ol', 'affibloom-list affibloom-list-style-alpha' ),
			'roman'   => array( 'roman', 'ol', 'affibloom-list affibloom-list-style-roman' ),
			'square'  => array( 'square', 'ul', 'affibloom-list affibloom-list-style-square' ),
			'circle'  => array( 'circle', 'ul', 'affibloom-list affibloom-list-style-circle' ),
			'hide'    => array( 'hide', 'ul', 'affibloom-list affibloom-list-style-hide' ),
			'Roman'   => array( 'Roman', 'ol', 'affibloom-list-ol affibloom-list-style-decimal' ),
			'unknown' => array( 'stars', 'ol', 'affibloom-list-ol affibloom-list-style-decimal' ),
		);
	}
}
