<?php
/**
 * Where the Creators API credentials and their verification signature are kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * Where the Creators API credentials and their verification signature are kept (C1 D2).
 */
interface CredentialsRepository {

	/**
	 * The stored credentials; empty ones when nothing is stored.
	 */
	public function get(): Credentials;

	/**
	 * The stored verification signature, or ''.
	 */
	public function signature(): string;

	/**
	 * Replaces the credentials and the signature.
	 *
	 * @param Credentials $credentials The credentials.
	 * @param string      $signature   Their signature when Amazon accepted them, else ''.
	 */
	public function save( Credentials $credentials, string $signature ): void;
}
