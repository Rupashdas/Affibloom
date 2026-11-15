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
	 * Replaces the credentials and the signature, and lifts any refusal.
	 *
	 * @param Credentials $credentials The credentials.
	 * @param string      $signature   Their signature when Amazon accepted them, else ''.
	 */
	public function save( Credentials $credentials, string $signature ): void;

	/**
	 * Why and when Amazon refused the stored credentials, or null (C1Q0).
	 */
	public function refusal(): ?Refusal;

	/**
	 * Stores credentials Amazon refused, without a signature and with the refusal (C1Q0; Lasso clears them, R22).
	 *
	 * @param Credentials $credentials The refused credentials.
	 * @param Refusal     $refusal     Why and when.
	 */
	public function refuse( Credentials $credentials, Refusal $refusal ): void;
}
