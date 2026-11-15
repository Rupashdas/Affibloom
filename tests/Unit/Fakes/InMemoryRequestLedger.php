<?php
namespace Affibloom\Tests\Unit\Fakes;

use Affibloom\Domain\Amazon\RequestLedger;
use Affibloom\Domain\Amazon\RequestLog;

/**
 * The record of Creators API calls, kept in memory.
 */
final class InMemoryRequestLedger implements RequestLedger {
	public function __construct( public RequestLog $log = new RequestLog( '', 0, 0.0, 0 ) ) {}

	public function get(): RequestLog {
		return $this->log;
	}

	public function save( RequestLog $log ): void {
		$this->log = $log;
	}
}
