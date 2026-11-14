<?php
declare( strict_types=1 );

namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Dashboard\SavedViewService;
use Affibloom\Application\Dashboard\ViewNotFound;
use Affibloom\Domain\Dashboard\SavedView;
use Affibloom\Domain\Dashboard\SavedViewRepository;
use Affibloom\Domain\Dashboard\SavedViews;
use Affibloom\Domain\Product\LinkKind;
use PHPUnit\Framework\TestCase;

/**
 * Deleting a saved view (Task 13J; F19 R15, D14): Lasso answers "Saved view not found." for an ID that is not there.
 */
final class SavedViewServiceTest extends TestCase {

	/** Views by user, as the user meta holds them. */
	private SavedViewRepository $repository;

	protected function setUp(): void {
		$this->repository = new class() implements SavedViewRepository {
			/** @var array<int, SavedViews> */
			public array $stored = array();

			public function for_user( int $user ): SavedViews {
				return $this->stored[ $user ] ?? new SavedViews();
			}

			public function save( int $user, SavedViews $views ): void {
				$this->stored[ $user ] = $views;
			}
		};
		$this->repository->save( 7, new SavedViews( array( new SavedView( 'v1', 'Amazon only', '', LinkKind::Amazon, 10 ) ) ) );
	}

	public function test_deleting_a_view_removes_it(): void {
		$left = ( new SavedViewService( $this->repository ) )->delete( 7, 'v1' );

		self::assertSame( array(), $left->all() );
		self::assertSame( array(), $this->repository->for_user( 7 )->all() );
	}

	public function test_deleting_a_view_that_is_not_there_is_refused_and_changes_nothing(): void {
		$this->expectException( ViewNotFound::class );

		try {
			( new SavedViewService( $this->repository ) )->delete( 7, 'gone' );
		} finally {
			self::assertCount( 1, $this->repository->for_user( 7 )->all() );
		}
	}
}
