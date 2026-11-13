<?php
/**
 * The one-time import of Lasso's products.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Migration;

use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Migration\ImportResult;
use Affibloom\Domain\Migration\LassoImportLog;
use Affibloom\Domain\Migration\LassoSource;
use Affibloom\Domain\Product\ProductRepository;

/**
 * The one-time import of Lasso's products into the library, so the client's staging review shows the site's real
 * products (the user, 2026-11-13). It reads Lasso and writes only Affibloom products. Each record is imported once:
 * a run cut off part way (a server time limit) is finished by the next, and a finished import never runs again. Not
 * the migration: Tasks 25-26 build that, with its dry run, settings, report and checks.
 */
final class LassoImportService {

	/**
	 * Builds the service.
	 *
	 * @param LassoSource       $source   Lasso's records.
	 * @param LassoImportLog    $log      What has been imported.
	 * @param ProductRepository $products The library.
	 */
	public function __construct(
		private LassoSource $source,
		private LassoImportLog $log,
		private ProductRepository $products,
	) {}

	/**
	 * How many Lasso records the import would bring in now: 0 once it has finished, or when there is no Lasso data.
	 */
	public function pending(): int {
		if ( null !== $this->log->result() ) {
			return 0;
		}

		return count( array_diff( $this->source->ids(), $this->log->imported_lasso_ids() ) );
	}

	/**
	 * The finished import, or null.
	 */
	public function finished(): ?ImportResult {
		return $this->log->result();
	}

	/**
	 * Imports every record that has no product yet, then records the import as finished. Once finished, it returns
	 * that result and imports nothing.
	 *
	 * @param string $now The time, "Y-m-d H:i:s" UTC.
	 */
	public function run( string $now ): ImportResult {
		$done = $this->log->result();
		if ( null !== $done ) {
			return $done;
		}

		$mapper   = new LassoRecordMapper();
		$ids      = $this->source->ids();
		$todo     = array_values( array_diff( $ids, $this->log->imported_lasso_ids() ) );
		$imported = count( $ids ) - count( $todo );
		$skipped  = array();

		foreach ( $this->source->records( $todo ) as $record ) {
			try {
				$product = $mapper->map( $record );
			} catch ( InvalidField ) {
				$product = null;
			}
			if ( null === $product ) {
				$skipped[] = $record->id . ': ' . html_entity_decode( $record->title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				continue;
			}

			$saved = $this->products->save( $product );
			$this->log->link( (int) $saved->id, $record );
			++$imported;
		}

		$result = new ImportResult( $imported, $skipped, $now );
		$this->log->save_result( $result );

		return $result;
	}
}
