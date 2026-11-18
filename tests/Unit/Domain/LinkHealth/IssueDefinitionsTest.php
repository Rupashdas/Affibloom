<?php
namespace Affibloom\Tests\Unit\Domain\LinkHealth;

use Affibloom\Domain\LinkHealth\IssueDefinitions;
use PHPUnit\Framework\TestCase;

/**
 * Lasso's 41 issue codes with Affibloom's own one-line descriptions (F11 R2, D3; Task 30A).
 */
final class IssueDefinitionsTest extends TestCase {

	public function test_it_holds_lassos_41_codes(): void {
		$codes = IssueDefinitions::codes();

		$this->assertCount( 41, $codes );
		$this->assertSame( '000', $codes[0] );
		$this->assertContains( '418', $codes );
		$this->assertContains( '511', $codes );
		$this->assertNotContains( '200', $codes );
	}

	public function test_each_code_has_a_label_and_a_short_description(): void {
		foreach ( IssueDefinitions::codes() as $code ) {
			$this->assertNotSame( '', IssueDefinitions::label( $code ), $code );
			$this->assertNotSame( '', IssueDefinitions::description( $code ), $code );
			$this->assertLessThanOrEqual( 120, strlen( IssueDefinitions::description( $code ) ), $code );
		}
		$this->assertSame( 'Amazon Out of Stock', IssueDefinitions::label( '000' ) );
		$this->assertSame( 'Not Found', IssueDefinitions::label( '404' ) );
	}

	public function test_an_unknown_code_gets_a_plain_label(): void {
		$this->assertSame( 'HTTP 599', IssueDefinitions::label( '599' ) );
		$this->assertSame( '', IssueDefinitions::description( '599' ) );
	}
}
