<?php
/**
 * A notice in the admin's notice region.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Shell;

use InvalidArgumentException;

/**
 * A notice in the region under the header of every Affibloom page (F38 D8, D10): what it says (a key the Interface
 * layer puts into words, with its values), its tone, where its link goes, whether it has a ×, and the REST route that
 * remembers a × (C1N's SiteStripe notice; '' for a × that only hides it on this page).
 */
final class ShellNotice {

	public const SUCCESS = 'success';
	public const ERROR   = 'error';
	public const WARNING = 'warning';
	/**
	 * Lasso's dark purple bar, for the update notice (R24).
	 */
	public const UPDATE = 'update';

	private const TONES = array( self::SUCCESS, self::ERROR, self::WARNING, self::UPDATE );

	/**
	 * Builds the notice.
	 *
	 * @param string           $key         What it says (Interface\Messages::for_notice()).
	 * @param string           $tone        One of the constants.
	 * @param list<int|string> $args        Values the words need (a step number, an error).
	 * @param string           $action_url  Where its link goes; '' when it has none.
	 * @param bool             $dismissible Whether it has a × (most of Lasso's do; the update notice does not).
	 * @param string           $dismiss_path The REST path (under /wp-json) its × posts to, or ''.
	 * @throws InvalidArgumentException When the tone is not one of the region's.
	 */
	public function __construct(
		public readonly string $key,
		public readonly string $tone,
		public readonly array $args = array(),
		public readonly string $action_url = '',
		public readonly bool $dismissible = true,
		public readonly string $dismiss_path = '',
	) {
		if ( ! in_array( $tone, self::TONES, true ) ) {
			throw new InvalidArgumentException( 'A notice needs one of the region\'s tones.' );
		}
	}
}
