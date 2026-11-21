<?php
namespace Affibloom\Tests\Unit\Application\Tables;

use Affibloom\Application\Tables\TableService;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Domain\Table\Table;
use Affibloom\Domain\Table\TableChanged;
use Affibloom\Domain\Table\TableLocations;
use Affibloom\Domain\Table\TablePage;
use Affibloom\Domain\Table\TableRepository;
use Affibloom\Domain\Table\TableStyle;
use Affibloom\Domain\Table\UntitledName;
use PHPUnit\Framework\TestCase;

/**
 * The builder's settings (F25 R14, R15, D4; Task T3): saved at once, refused on a stale revision or a blank name.
 */
final class TableServiceTest extends TestCase {

	/** @var array<int, Table> */
	private array $stored = array();

	private TableService $service;

	protected function setUp(): void {
		$stored     = &$this->stored;
		$repository = new class( $stored ) implements TableRepository {
			/** @param array<int, Table> $stored */
			public function __construct( private array &$stored ) {}
			public function page( SearchTerm $term, int $page, int $per_page ): TablePage {
				return new TablePage( array(), 0, $page, $per_page );
			}
			public function count(): int {
				return count( $this->stored );
			}
			public function get( int $id ): ?Table {
				return $this->stored[ $id ] ?? null;
			}
			public function save( Table $table ): Table {
				$saved                       = $table->with( array( 'id' => $table->id ?? count( $this->stored ) + 1 ) );
				$this->stored[ $saved->id ] = $saved;
				return $saved;
			}
			public function name_taken( string $name ): bool {
				foreach ( $this->stored as $table ) {
					if ( $table->name === $name ) {
						return true;
					}
				}
				return false;
			}
		};
		$this->service = new TableService( $repository, $this->createStub( TableLocations::class ) );
	}

	public function test_a_setting_saves_and_bumps_the_revision(): void {
		$table = $this->service->create( 'Shoes', TableStyle::Column );

		$saved = $this->service->update( (int) $table->id, $table->revision, array( 'title' => ' Balls ', 'style' => 'row', 'theme' => 'Llama', 'show_headers' => false ) );

		self::assertNotNull( $saved );
		self::assertSame( array( 'Balls', TableStyle::Row, 'Llama', false, $table->revision + 1 ), array( $saved->name, $saved->style, $saved->theme, $saved->show_headers, $saved->revision ) );
	}

	public function test_a_stale_revision_is_refused(): void {
		$table = $this->service->create( 'Shoes', TableStyle::Column );
		$this->service->update( (int) $table->id, $table->revision, array( 'theme' => 'Llama' ) );

		$this->expectException( TableChanged::class );
		$this->service->update( (int) $table->id, $table->revision, array( 'theme' => 'Cactus' ) );
	}

	public function test_a_blank_name_is_refused_and_a_missing_table_answers_null(): void {
		$table = $this->service->create( 'Shoes', TableStyle::Column );
		self::assertNull( $this->service->update( 999, 1, array( 'theme' => 'Llama' ) ) );

		$this->expectException( InvalidField::class );
		$this->service->update( (int) $table->id, $table->revision, array( 'title' => '   ' ) );
	}

	public function test_the_untitled_name_is_the_first_one_free(): void {
		self::assertSame( 'Untitled Table', UntitledName::next( static fn(): bool => false ) );
		$this->service->create( 'Untitled Table', TableStyle::Column );
		$this->service->create( 'Untitled Table 1', TableStyle::Column );
		self::assertSame( 'Untitled Table 2', $this->service->untitled() );
	}
}
