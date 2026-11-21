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
use Affibloom\Application\Tables\TableValues;
use Affibloom\Domain\Fields\FieldLibrary;
use Affibloom\Domain\Fields\ProductFields;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Button;
use Affibloom\Domain\Product\Buttons;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Table\StartingLayout;
use Affibloom\Domain\Table\TableRuleBroken;
use Affibloom\Domain\Table\UntitledName;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * The builder's settings (F25 R14, R15, D4; Task T3): saved at once, refused on a stale revision or a blank name.
 */
final class TableServiceTest extends TestCase {

	/** @var array<int, Table> */
	private array $stored = array();

	private TableService $service;

	private InMemoryProductRepository $products;

	protected function setUp(): void {
		Monkey\setUp();
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		$this->products = new InMemoryProductRepository();
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
		$this->service = new TableService(
			$repository,
			$this->createStub( TableLocations::class ),
			$this->products,
			new TableValues( static fn( Product $product ): string => 'https://site.test/go/' . $product->id . '/' ),
			static fn(): FieldLibrary => FieldLibrary::from_array( array() )
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
	}

	private function product( string $name, array $changes = array() ): int {
		$product = new Product( null, $name, AffiliateUrl::from_string( 'https://www.example.com/' . rawurlencode( $name ) ), ProductType::Other, true, true, false, true );

		return (int) $this->products->save( $product->with( $changes ) )->id;
	}

	/** Task T4 (F25 R18, R19, R22): the first product brings Lasso's starting layout, each cell its own value. */
	public function test_the_first_product_brings_the_starting_layout_filled_from_the_product(): void {
		$table   = $this->service->create( 'Shoes', TableStyle::Column );
		$product = $this->product(
			'Molten V5',
			array(
				'image_url'   => 'https://img.test/v5.jpg',
				'price'       => '$49',
				'badge'       => 'Best Pick',
				'button_text' => 'See Price',
				'buttons'     => new Buttons( array( new Button( 'https://shop.test/v5', 'Compare' ) ) ),
			)
		);
		$this->products->save_fields( $product, ProductFields::from_array( array( array( 'field' => 1, 'value' => '4.5' ) ) ) );

		$saved = $this->service->add_product( (int) $table->id, $table->revision, $product );

		self::assertNotNull( $saved );
		self::assertSame( array( array( StartingLayout::IMAGE ), array( 99990, 1, 99994 ), array( 99992 ) ), array_column( $saved->groups, 'fields' ) );
		self::assertMatchesRegularExpression( '/^g_[0-9a-f]{12}$/', $saved->groups[0]['id'] );
		self::assertSame( 'Best Pick', $saved->products[0]->badge );
		self::assertSame(
			array(
				99991 => 'https://img.test/v5.jpg',
				99990 => 'Molten V5',
				1     => '4.5',
				99994 => '$49',
				99992 => '{"button_text":"See Price","url":"https://site.test/go/' . $product . '/"}',
			),
			$saved->values[ $product ]
		);
		self::assertSame( $table->revision + 1, $saved->revision );
	}

	/** Task T4 (F25 R18, R20, R23): later products keep the layout, a product comes once, removing takes its cells. */
	public function test_later_products_keep_the_layout_and_a_product_comes_once(): void {
		$table  = $this->service->create( 'Shoes', TableStyle::Row );
		$first  = $this->product( 'First' );
		$second = $this->product( 'Second' );
		$table  = $this->service->add_product( (int) $table->id, $table->revision, $first );
		self::assertNotNull( $table );
		$layout = $table->groups;

		$table = $this->service->add_product( (int) $table->id, $table->revision, $second );
		self::assertNotNull( $table );
		self::assertSame( $layout, $table->groups );
		self::assertSame( array( $first, $second ), $table->product_ids() );
		self::assertSame( 'Second', $table->values[ $second ][99990] );

		try {
			$this->service->add_product( (int) $table->id, $table->revision, $first );
			self::fail( 'A product came twice.' );
		} catch ( TableRuleBroken $broken ) {
			self::assertSame( 'product_exists', $broken->rule );
		}

		$table = $this->service->set_badge( (int) $table->id, $table->revision, $second, '  Runner-up ' );
		self::assertNotNull( $table );
		self::assertSame( 'Runner-up', $table->products[1]->badge );

		$table = $this->service->remove_product( (int) $table->id, $table->revision, $first );
		self::assertNotNull( $table );
		self::assertSame( array( $second ), $table->product_ids() );
		self::assertArrayNotHasKey( $first, $table->values );

		// The last product gone, the layout goes too: the next first product starts afresh.
		$table = $this->service->remove_product( (int) $table->id, $table->revision, $second );
		self::assertNotNull( $table );
		self::assertSame( array( array(), array() ), array( $table->groups, $table->values ) );
	}

	/** Task T4 (F25 R18): a product that does not exist is refused. */
	public function test_a_missing_product_is_refused(): void {
		$table = $this->service->create( 'Shoes', TableStyle::Column );

		$this->expectException( TableRuleBroken::class );
		$this->service->add_product( (int) $table->id, $table->revision, 999 );
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
