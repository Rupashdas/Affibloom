<?php
namespace Affibloom\Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Affibloom is a standalone plugin (deviation 146): no shipped code reads Lasso's data or markup, or prints Lasso's name.
 * Comments may still cite Lasso's code as the behaviour a feature copies; they are not shipped behaviour. The move from
 * Lasso is the separate Affibloom Lasso Migrator plugin (Phase M).
 */
final class NoLassoInCoreTest extends TestCase {

	/**
	 * The folders that ship and hold code.
	 */
	private const FOLDERS = array( 'src', 'templates', 'assets', 'blocks' );

	public function test_no_shipped_code_names_lasso_outside_comments(): void {
		$root  = dirname( __DIR__, 3 );
		$found = array();
		foreach ( self::FOLDERS as $folder ) {
			$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root . '/' . $folder, RecursiveDirectoryIterator::SKIP_DOTS ) );
			foreach ( $files as $file ) {
				$path = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $root ) + 1 ) );
				$code = self::code( $path, (string) file_get_contents( $file->getPathname() ) );
				if ( null === $code ) {
					continue;
				}
				foreach ( explode( "\n", $code ) as $number => $line ) {
					if ( false !== stripos( $line, 'lasso' ) ) {
						$found[] = $path . ':' . ( $number + 1 ) . ': ' . trim( $line );
					}
				}
			}
		}

		$this->assertSame( array(), $found, "Lasso named outside comments:\n" . implode( "\n", $found ) );
	}

	/**
	 * A file's text with its comments blanked (line breaks kept, so line numbers stay true), or null for a file
	 * that is not code.
	 *
	 * @param string $path Plugin-relative path.
	 * @param string $text The file's text.
	 */
	private static function code( string $path, string $text ): ?string {
		$extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		if ( 'php' === $extension ) {
			$code = '';
			foreach ( token_get_all( $text ) as $token ) {
				if ( is_array( $token ) && in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
					$code .= str_repeat( "\n", substr_count( $token[1], "\n" ) );
					continue;
				}
				$code .= is_array( $token ) ? $token[1] : $token;
			}

			return $code;
		}
		if ( in_array( $extension, array( 'js', 'css' ), true ) ) {
			$code = (string) preg_replace_callback( '#/\*.*?\*/#s', static fn( array $comment ): string => str_repeat( "\n", substr_count( $comment[0], "\n" ) ), $text );

			// A line comment starts at a line's start or after a space, so `https://` in a string stays code.
			return 'js' === $extension ? (string) preg_replace( '#(^|\s)//.*$#m', '$1', $code ) : $code;
		}

		return null;
	}
}
