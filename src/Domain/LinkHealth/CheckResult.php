<?php
/**
 * What one check of a product's link found.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkHealth;

/**
 * What one check of a product's link found (F11 D4, D6): a 200; a failure that counts once until a second check
 * confirms it (an HTTP answer); a certain result that counts at once (a blank destination, a product-API answer); or
 * no check at all (WordPress refused to send the request).
 */
final class CheckResult {

	private const OK           = 'ok';
	private const FAILED       = 'failed';
	private const CERTAIN      = 'certain';
	private const NOT_EXECUTED = 'not_executed';

	/**
	 * Use the named constructors.
	 *
	 * @param string $kind One of the constants above.
	 * @param string $type The row's code (IssueType) the result gives; '' when there is none.
	 */
	private function __construct( private string $kind, public readonly string $type ) {}

	/**
	 * The link answered 200.
	 */
	public static function ok(): self {
		return new self( self::OK, IssueType::OK );
	}

	/**
	 * The link answered something else, or nothing (status 0).
	 *
	 * @param int $status The HTTP status.
	 */
	public static function failed( int $status ): self {
		return new self( self::FAILED, IssueType::of_status( $status ) );
	}

	/**
	 * A result that needs no second check.
	 *
	 * @param string $type The row's code (IssueType::NOT_FOUND, IssueType::OUT_OF_STOCK, or an OK from the product API).
	 */
	public static function certain( string $type ): self {
		return new self( self::CERTAIN, $type );
	}

	/**
	 * No request went out (WordPress's http_request_not_executed: blocked hosts).
	 */
	public static function not_executed(): self {
		return new self( self::NOT_EXECUTED, IssueType::NONE );
	}

	/**
	 * Whether the link works.
	 */
	public function is_ok(): bool {
		return self::OK === $this->kind || ( self::CERTAIN === $this->kind && IssueType::OK === $this->type );
	}

	/**
	 * Whether it failed and needs a second check before it counts.
	 */
	public function needs_confirmation(): bool {
		return self::FAILED === $this->kind;
	}

	/**
	 * Whether a check happened at all.
	 */
	public function was_checked(): bool {
		return self::NOT_EXECUTED !== $this->kind;
	}
}
