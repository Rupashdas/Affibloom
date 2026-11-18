<?php
/**
 * The codes a product's link-health row carries.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkHealth;

/**
 * The codes a product's link-health row carries (F11 R1, R13; Lasso's lasso_url_issues.issue_type): the HTTP status of
 * a failing check, `200` for a checked row that never failed, `000` for out of stock (Lasso's code). An HTTP code is
 * three characters, so a row has a code from 100 to 999; anything else is "no answer" (500, as Lasso's WP_Error).
 */
final class IssueType {

	/** Checked, never failed. */
	public const OK = '200';

	/** Out of stock (Lasso's own code). */
	public const OUT_OF_STOCK = '000';

	/** A blank destination, or a product the product API does not find (R9, R10). */
	public const NOT_FOUND = '404';

	/** No answer: the request failed, timed out or gave no status (R12). */
	public const NO_ANSWER = '500';

	/** A row with no result yet: its first check failed and waits for the second (D4). */
	public const NONE = '';

	/**
	 * An HTTP status as a row's code; outside 100-999, no answer.
	 *
	 * @param int $status The status.
	 */
	public static function of_status( int $status ): string {
		return $status >= 100 && $status <= 999 ? (string) $status : self::NO_ANSWER;
	}
}
