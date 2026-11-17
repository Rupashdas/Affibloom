<?php
/**
 * The Cloaked Link Prefixes a site has had.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Cloak;

/**
 * Old prefixes keep answering (F24 N2, decided 2026-11-14): Lasso rewrites every post when the prefix changes; Affibloom
 * writes no post, so the addresses already published under an old prefix (or under none) must still lead to their
 * products. The history holds every earlier prefix once, '' standing for "no prefix", never the current one.
 */
final class CloakPrefix {

	/**
	 * The history after the prefix changed.
	 *
	 * @param list<string> $history  The prefixes before.
	 * @param string       $previous The prefix until now.
	 * @param string       $current  The new prefix.
	 * @return list<string>
	 */
	public static function history( array $history, string $previous, string $current ): array {
		if ( $previous === $current ) {
			return $history;
		}
		$history[] = $previous;

		return array_values( array_diff( array_unique( $history ), array( $current ) ) );
	}

	/**
	 * The prefixes a path is tried with: the current one first, then the old ones.
	 *
	 * @param string       $current The prefix now.
	 * @param list<string> $history The old ones.
	 * @return list<string>
	 */
	public static function all( string $current, array $history ): array {
		return array_values( array_unique( array_merge( array( $current ), $history ) ) );
	}
}
