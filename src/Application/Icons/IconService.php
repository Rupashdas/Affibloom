<?php
/**
 * Button icon use cases (F28).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Icons;

use Affibloom\Domain\Icons\Icon;
use Affibloom\Domain\Icons\IconRepository;
use Affibloom\Domain\Icons\IconSet;
use Affibloom\Domain\InvalidField;

/**
 * Button icon use cases: the set, adding and removing (F28 requirements B1-B3).
 */
final class IconService {

	/**
	 * The set read in this request, so the screens and the CSS read the option once.
	 *
	 * @var IconSet|null
	 */
	private ?IconSet $set = null;

	/**
	 * Builds the service.
	 *
	 * @param IconRepository $icons Where the set is kept.
	 * @param SiteIcons      $site  The icons the site's CSS defines.
	 */
	public function __construct( private IconRepository $icons, private SiteIcons $site ) {}

	/**
	 * The icon set. The first read on a site imports the icons its CSS defines and stores them, so a later change of
	 * that CSS does not silently change the list (F28 analysis, "Data").
	 */
	public function all(): IconSet {
		if ( null === $this->set ) {
			$this->set = $this->icons->load();
			if ( null === $this->set ) {
				$this->set = new IconSet( $this->site->found() );
				$this->icons->save( $this->set );
			}
		}

		return $this->set;
	}

	/**
	 * Adds an icon at the end of the set; returns it.
	 *
	 * @param string $name       1-40 characters.
	 * @param string $url        The image's link.
	 * @param int    $attachment The media library item.
	 * @throws InvalidField When the name or the link breaks its rule.
	 */
	public function add( string $name, string $url, int $attachment ): Icon {
		$set = $this->all()->add( $name, $url, $attachment );
		$this->store( $set );

		$icons = $set->all();
		return $icons[ count( $icons ) - 1 ];
	}

	/**
	 * Removes an icon added in Affibloom.
	 *
	 * @param string $css_class The icon's class.
	 * @throws InvalidField When it is imported or not in the set.
	 */
	public function remove( string $css_class ): void {
		$this->store( $this->all()->remove( $css_class ) );
	}

	/**
	 * Keeps the new set for this request and stores it.
	 *
	 * @param IconSet $set The new set.
	 */
	private function store( IconSet $set ): void {
		$this->set = $set;
		$this->icons->save( $set );
	}
}
