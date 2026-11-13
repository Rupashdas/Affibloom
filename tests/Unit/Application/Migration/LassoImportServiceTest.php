<?php
namespace Affibloom\Tests\Unit\Application\Migration;

use Affibloom\Application\Migration\LassoImportService;
use Affibloom\Domain\Migration\ImportResult;
use Affibloom\Domain\Migration\LassoImportLog;
use Affibloom\Domain\Migration\LassoRecord;
use Affibloom\Domain\Migration\LassoSource;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use PHPUnit\Framework\TestCase;

/**
 * The one-time import of Lasso's products for the client's staging review: each record once, records with no link
 * left out and listed, and nothing more once the import has finished.
 */
final class LassoImportServiceTest extends TestCase {
	private InMemoryProductRepository $products;

	/** @var LassoSource&object{records: array<int, LassoRecord>} */
	private LassoSource $source;

	/** @var LassoImportLog&object{links: array<int, int>, result: ?ImportResult} */
	private LassoImportLog $log;

	protected function setUp(): void {
		parent::setUp();
		$this->products = new InMemoryProductRepository();
		$this->source   = new class() implements LassoSource {
			/** @var array<int, LassoRecord> */
			public array $records = array();

			public function ids(): array {
				return array_keys( $this->records );
			}

			public function records( array $ids ): array {
				return array_values( array_intersect_key( $this->records, array_flip( $ids ) ) );
			}
		};
		$this->log      = new class() implements LassoImportLog {
			/** @var array<int, int> Lasso ID => product ID. */
			public array $links = array();

			public ?ImportResult $result = null;

			public function imported_lasso_ids(): array {
				return array_keys( $this->links );
			}

			public function link( int $product_id, LassoRecord $record ): void {
				$this->links[ $record->id ] = $product_id;
			}

			public function result(): ?ImportResult {
				return $this->result;
			}

			public function save_result( ImportResult $result ): void {
				$this->result = $result;
			}
		};
	}

	private function add( int $id, string $link, string $title = 'Product' ): void {
		$this->source->records[ $id ] = new LassoRecord( $id, $title . ' ' . $id, '2024-01-01 00:00:00', '2024-02-01 00:00:00', array(), $link );
	}

	private function service(): LassoImportService {
		return new LassoImportService( $this->source, $this->log, $this->products );
	}

	public function test_every_record_with_a_link_becomes_a_product_linked_to_its_lasso_id(): void {
		$this->add( 11, 'https://www.example.com/a' );
		$this->add( 12, 'https://www.example.com/b' );

		$result = $this->service()->run( '2026-11-13 10:00:00' );

		self::assertSame( 2, $result->imported );
		self::assertCount( 2, $this->products->saved );
		self::assertSame( array( 11, 12 ), array_keys( $this->log->links ) );
		self::assertSame( 'https://www.example.com/a', $this->products->get( $this->log->links[11] )?->url->value() );
	}

	public function test_records_with_no_usable_link_are_left_out_and_listed(): void {
		$this->add( 11, 'https://www.example.com/a' );
		$this->add( 12, '', 'Empty' );
		$this->add( 13, 'not a link', 'Broken' );

		$result = $this->service()->run( '2026-11-13 10:00:00' );

		self::assertSame( 1, $result->imported );
		self::assertSame( array( '12: Empty 12', '13: Broken 13' ), $result->skipped );
	}

	public function test_pending_counts_records_not_yet_imported_and_a_rerun_after_a_break_continues(): void {
		$this->add( 11, 'https://www.example.com/a' );
		$this->add( 12, 'https://www.example.com/b' );
		$this->log->links[11] = 500; // Imported by a run that was cut off before it finished.

		self::assertSame( 1, $this->service()->pending() );

		$result = $this->service()->run( '2026-11-13 10:00:00' );

		self::assertSame( 2, $result->imported );
		self::assertCount( 1, $this->products->saved );
	}

	public function test_a_finished_import_does_not_run_again(): void {
		$this->add( 11, 'https://www.example.com/a' );
		$first = $this->service()->run( '2026-11-13 10:00:00' );
		$this->add( 12, 'https://www.example.com/b' );

		self::assertSame( 0, $this->service()->pending() );
		self::assertSame( $first, $this->service()->run( '2026-11-13 11:00:00' ) );
		self::assertCount( 1, $this->products->saved );
		self::assertSame( '2026-11-13 10:00:00', $this->service()->finished()?->finished_at );
	}

	public function test_without_lasso_records_nothing_is_pending(): void {
		self::assertSame( 0, $this->service()->pending() );
	}

	public function test_the_result_survives_storage(): void {
		$result = new ImportResult( 254, array( '14837: Empty' ), '2026-11-13 10:00:00' );

		self::assertEquals( $result, ImportResult::from_array( $result->to_array() ) );
		self::assertNull( ImportResult::from_array( array() ) );
	}
}
