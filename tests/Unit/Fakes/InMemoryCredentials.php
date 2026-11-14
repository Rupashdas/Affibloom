<?php
namespace Affibloom\Tests\Unit\Fakes;

use Affibloom\Domain\Amazon\Credentials;
use Affibloom\Domain\Amazon\CredentialsRepository;

/**
 * Creators API credentials kept in memory.
 */
final class InMemoryCredentials implements CredentialsRepository {
	public function __construct( public Credentials $stored = new Credentials(), public string $signature = '' ) {}

	public function get(): Credentials {
		return $this->stored;
	}

	public function signature(): string {
		return $this->signature;
	}

	public function save( Credentials $credentials, string $signature ): void {
		$this->stored    = $credentials;
		$this->signature = $signature;
	}
}
