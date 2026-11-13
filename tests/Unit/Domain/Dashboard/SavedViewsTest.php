<?php
namespace Affibloom\Tests\Unit\Domain\Dashboard;

use Affibloom\Domain\Dashboard\SavedView;
use Affibloom\Domain\Dashboard\SavedViews;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\LinkKind;
use PHPUnit\Framework\TestCase;

/**
 * Lasso's saved-view rules (Dashboard_Saved_Views): 30 at most, names of 1 to 80 characters, saving under an
 * existing ID replaces it.
 */
final class SavedViewsTest extends TestCase {

	private function view( string $id, string $name = 'Amazon shoes' ): SavedView {
		return new SavedView( $id, $name, 'shoes', LinkKind::Amazon, 25 );
	}

	public function test_a_new_view_is_added_at_the_end(): void {
		$views = ( new SavedViews() )->with( $this->view( 'a' ) )->with( $this->view( 'b' ) );

		self::assertSame( array( 'a', 'b' ), array_map( static fn( SavedView $v ) => $v->id, $views->all() ) );
	}

	public function test_saving_under_an_existing_id_replaces_that_view_in_place(): void {
		$views = ( new SavedViews() )->with( $this->view( 'a' ) )->with( $this->view( 'b' ) )->with( $this->view( 'a', 'Renamed' ) );

		self::assertCount( 2, $views->all() );
		self::assertSame( 'Renamed', $views->all()[0]->name );
	}

	public function test_the_list_is_immutable(): void {
		$empty = new SavedViews();
		$empty->with( $this->view( 'a' ) );

		self::assertSame( array(), $empty->all() );
	}

	public function test_an_empty_name_is_refused(): void {
		$this->expectExceptionObject( new InvalidField( 'name', 'view_name_empty' ) );

		( new SavedViews() )->with( $this->view( 'a', '   ' ) );
	}

	public function test_eighty_characters_are_allowed_and_eighty_one_are_not(): void {
		$views = ( new SavedViews() )->with( $this->view( 'a', str_repeat( 'é', 80 ) ) );
		self::assertCount( 1, $views->all() );

		$this->expectExceptionObject( new InvalidField( 'name', 'view_name_too_long' ) );
		$views->with( $this->view( 'b', str_repeat( 'é', 81 ) ) );
	}

	public function test_the_thirty_first_view_is_refused_but_a_replacement_still_works(): void {
		$views = new SavedViews();
		for ( $i = 1; $i <= 30; $i++ ) {
			$views = $views->with( $this->view( "v$i" ) );
		}

		self::assertCount( 30, $views->with( $this->view( 'v7', 'Replaced' ) )->all() );

		$this->expectExceptionObject( new InvalidField( 'views', 'view_limit' ) );
		$views->with( $this->view( 'v31' ) );
	}

	public function test_a_view_is_removed_by_its_id(): void {
		$views = ( new SavedViews() )->with( $this->view( 'a' ) )->with( $this->view( 'b' ) );

		self::assertSame( array( 'b' ), array_map( static fn( SavedView $v ) => $v->id, $views->without( 'a' )->all() ) );
		self::assertCount( 2, $views->without( 'missing' )->all() );
	}

	public function test_a_view_knows_the_list_it_shows(): void {
		$view = $this->view( 'a' );

		self::assertTrue( $view->shows( 'shoes', LinkKind::Amazon, 25 ) );
		self::assertFalse( $view->shows( 'shoes', LinkKind::All, 25 ) );
		self::assertFalse( $view->shows( 'shoes', LinkKind::Amazon, 10 ) );
	}

	public function test_link_kind_accepts_lassos_values_and_nothing_else(): void {
		self::assertSame( LinkKind::Amazon, LinkKind::from_input( 'amazon' ) );
		self::assertSame( LinkKind::NonAmazon, LinkKind::from_input( 'non_amazon' ) );
		self::assertSame( LinkKind::All, LinkKind::from_input( '' ) );
		self::assertSame( LinkKind::All, LinkKind::from_input( 'AMAZON' ) );
	}
}
