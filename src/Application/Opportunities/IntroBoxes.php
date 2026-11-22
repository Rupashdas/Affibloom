<?php
/**
 * The Opportunities screens' intro boxes.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Opportunities;

use Affibloom\Domain\Settings\SettingsRepository;

/**
 * Lasso's education boxes on the Opportunities screens (F22 R5, R6, D7, D8; Task O1): a box closed with × stays closed
 * for the site (`affibloom_settings['education_hidden']`), and Settings' "Disable help tips" hides them all. F38 ON5
 * takes this over for every screen on its second use.
 */
final class IntroBoxes {

	/** The boxes these screens show. */
	public const BOXES = array( 'links', 'domains', 'content' );

	/**
	 * Builds the boxes.
	 *
	 * @param SettingsRepository $settings The site's settings.
	 */
	public function __construct( private SettingsRepository $settings ) {}

	/**
	 * Whether a box is shown.
	 *
	 * @param string $box One of BOXES.
	 */
	public function shows( string $box ): bool {
		$site = $this->settings->get();

		return ! $site->bool( 'disable_help_tips' ) && ! in_array( $box, $site->list( 'education_hidden' ), true );
	}

	/**
	 * Closes a box for good; an unknown name changes nothing.
	 *
	 * @param string $box One of BOXES.
	 */
	public function hide( string $box ): void {
		if ( ! in_array( $box, self::BOXES, true ) ) {
			return;
		}
		$site   = $this->settings->get();
		$hidden = $site->list( 'education_hidden' );
		if ( ! in_array( $box, $hidden, true ) ) {
			$this->settings->save( $site->with( array( 'education_hidden' => array_merge( $hidden, array( $box ) ) ) ) );
		}
	}

	/**
	 * Brings every closed box back (F38 R35; Task ON5): Lasso's `?reseteducation` on an opportunities page.
	 */
	public function reset(): void {
		$site = $this->settings->get();
		if ( array() !== $site->list( 'education_hidden' ) ) {
			$this->settings->save( $site->with( array( 'education_hidden' => array() ) ) );
		}
	}
}
