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
use Affibloom\Application\Groups\GroupService;
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
use Affibloom\Tests\Unit\Fakes\InMemoryGroupRepository;
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

	private InMemoryGroupRepository $groups;

	/** @var TableLocations&\PHPUnit\Framework\MockObject\Stub */
	private TableLocations $locations;

	protected function setUp(): void {
		Monkey\setUp();
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		$this->products = new InMemoryProductRepository();
		$this->groups   = new InMemoryGroupRepository();
		$stored     = &$this->stored;
		$repository = new class( $stored ) implements TableRepository {
			/** @param array<int, Table> $stored */
			public function __construct( private array &$stored ) {}
			public function page( SearchTerm $term, int $page, int $per_page, bool $newest = false ): TablePage {
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
			public function product_ids(): array {
				return array();
			}
			public function containing( int $product ): array {
				return array_values( array_filter( $this->stored, static fn( Table $table ): bool => $table->has_product( $product ) ) );
			}
			public function delete( int $id ): void {
				unset( $this->stored[ $id ] );
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
			$this->locations = $this->createStub( TableLocations::class ),
			$this->products,
			new TableValues( static fn( Product $product ): string => 'https://site.test/go/' . $product->id . '/' ),
			static fn(): FieldLibrary => FieldLibrary::from_array( array() ),
			new GroupService( $this->groups, $this->products )
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

	/** Task T5 (F25 R24, D6): a group's products join last, in its order, skipping those already in the table. */
	public function test_a_group_adds_its_products_once_each_in_its_order(): void {
		$table = $this->service->create( 'Shoes', TableStyle::Column );
		$a     = $this->product( 'A' );
		$b     = $this->product( 'B' );
		$c     = $this->product( 'C' );
		$group = (int) $this->groups->create( 'Shoes', '' )?->id;
		$this->groups->members[ $group ] = array( $a, $b, $c );
		$this->groups->orders[ $group ]  = array( $c, $a, $b );
		$table = $this->service->add_product( (int) $table->id, $table->revision, $a );
		self::assertNotNull( $table );

		$table = $this->service->add_group( (int) $table->id, $table->revision, $group );

		self::assertNotNull( $table );
		self::assertSame( array( $a, $c, $b ), $table->product_ids() );
		self::assertSame( 'C', $table->values[ $c ][99990] );

		$this->expectException( TableRuleBroken::class );
		$this->service->add_group( (int) $table->id, $table->revision, 999 );
	}

	/** Task T8 (F25 R35): the Primary Button keeps the product's link; the Secondary takes the one typed; text is needed. */
	public function test_a_buttons_text_saves_and_only_the_secondary_takes_an_address(): void {
		$table   = $this->service->create( 'Shoes', TableStyle::Column );
		$product = $this->product( 'Molten' );
		$table   = $this->service->add_product( (int) $table->id, $table->revision, $product );
		self::assertNotNull( $table );
		$group = $table->groups[2]['id'];
		$table = $this->service->add_field( (int) $table->id, $table->revision, $group, 99993 );
		self::assertNotNull( $table );

		$table = $this->service->set_button( (int) $table->id, $table->revision, $product, 99992, ' Check Price ', 'https://elsewhere.test/' );
		self::assertNotNull( $table );
		self::assertSame( '{"button_text":"Check Price","url":"https://site.test/go/' . $product . '/"}', $table->values[ $product ][99992] );

		$table = $this->service->set_button( (int) $table->id, $table->revision, $product, 99993, 'Compare', 'https://shop.test/v5' );
		self::assertNotNull( $table );
		self::assertSame( '{"button_text":"Compare","url":"https://shop.test/v5"}', $table->values[ $product ][99993] );

		foreach ( array( array( 99992, ' ', '', 'table_button_text' ), array( 99993, 'Compare', ' ', 'table_button_url' ) ) as [ $field, $text, $url, $reason ] ) {
			try {
				$this->service->set_button( (int) $table->id, $table->revision, $product, $field, $text, $url );
				self::fail( 'A button without its ' . $reason . ' was saved.' );
			} catch ( \Affibloom\Domain\InvalidField $refused ) {
				self::assertSame( $reason, $refused->reason );
			}
		}
	}

	/** Task T11 (F25 R10, D8): a clone has everything, Show Field Name included, under "<name> - Copy". */
	public function test_a_clone_copies_everything(): void {
		$table   = $this->service->create( 'Shoes', TableStyle::Row );
		$product = $this->product( 'Molten', array( 'badge' => 'Top' ) );
		$table   = $this->service->add_product( (int) $table->id, $table->revision, $product );
		self::assertNotNull( $table );
		$table = $this->service->update( (int) $table->id, $table->revision, array( 'theme' => 'Llama', 'show_field_name' => false ) );
		self::assertNotNull( $table );

		$copy = $this->service->clone( (int) $table->id );

		self::assertNotNull( $copy );
		$clone = $copy['table'];
		self::assertNotSame( $table->id, $clone->id );
		self::assertSame( 'Shoes - Copy', $clone->name );
		self::assertSame( array( TableStyle::Row, 'Llama', false ), array( $clone->style, $clone->theme, $clone->show_field_name ) );
		self::assertEquals( $table->products, $clone->products );
		self::assertSame( array( $table->groups, $table->values ), array( $clone->groups, $clone->values ) );
		self::assertSame( 1, $copy['page'] );
		self::assertNull( $this->service->clone( 999 ) );
	}

	/** Task T11 (F25 R41, D12): a table content shows is not deleted; an unused one is; a gone one answers false. */
	public function test_a_table_in_use_is_not_deleted(): void {
		$used   = $this->service->create( 'Used', TableStyle::Column );
		$unused = $this->service->create( 'Unused', TableStyle::Column );
		$this->locations->method( 'counts' )->willReturnCallback( static fn( array $ids ): array => in_array( $used->id, $ids, true ) ? array( (int) $used->id => 2 ) : array() );

		self::assertTrue( $this->service->delete( (int) $unused->id ) );
		self::assertNull( $this->service->get( (int) $unused->id ) );
		self::assertFalse( $this->service->delete( (int) $unused->id ) );

		$this->expectException( \Affibloom\Application\Tables\TableInUse::class );
		$this->service->delete( (int) $used->id );
	}

	/** Task T17 (F25 R60, D15): a deleted product leaves every table, with its values; the tables move on. */
	public function test_a_deleted_product_leaves_every_table(): void {
		$keep  = $this->product( 'Keep' );
		$gone  = $this->product( 'Gone' );
		$first = $this->service->create( 'One', TableStyle::Column );
		$other = $this->service->create( 'Two', TableStyle::Row );
		foreach ( array( $first, $other ) as $table ) {
			$table = $this->service->add_product( (int) $table->id, $table->revision, $keep );
			self::assertNotNull( $table );
			$this->service->add_product( (int) $table->id, $table->revision, $gone );
		}

		self::assertSame( 2, $this->service->remove_product_everywhere( $gone ) );

		foreach ( array( $first, $other ) as $table ) {
			$saved = $this->service->get( (int) $table->id );
			self::assertNotNull( $saved );
			self::assertSame( array( $keep ), $saved->product_ids() );
			self::assertArrayNotHasKey( $gone, $saved->values );
		}
		self::assertSame( 0, $this->service->remove_product_everywhere( $gone ) );
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
