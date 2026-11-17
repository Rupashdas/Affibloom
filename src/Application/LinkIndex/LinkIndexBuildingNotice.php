<?php
/**
 * The notice while the link index is being built.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkIndex;

use Affibloom\Application\Shell\NoticeProvider;
use Affibloom\Domain\LinkIndex\RebuildStateRepository;
use Affibloom\Domain\Shell\ShellNotice;

/**
 * "We're rounding up a list of your links. See the progress." on every Affibloom page while a rebuild runs (F38 R25,
 * D14; Task ON4): Locations and opportunities are incomplete until it ends. Like Lasso's, it has no ×; the link goes to
 * Settings → General's Link Index card.
 */
final class LinkIndexBuildingNotice implements NoticeProvider {

	/**
	 * Builds the provider.
	 *
	 * @param RebuildStateRepository $state    Where the rebuild stands.
	 * @param string                 $card_url The Link Index card's address.
	 */
	public function __construct( private RebuildStateRepository $state, private string $card_url ) {}

	/**
	 * The notice while a rebuild runs, else none.
	 *
	 * @param string $page The Affibloom admin page slug being shown (every page shows it).
	 * @return list<ShellNotice>
	 */
	public function notices( string $page ): array {
		if ( ! $this->state->get()->running() ) {
			return array();
		}

		return array( new ShellNotice( 'link_index_building', ShellNotice::WARNING, array(), $this->card_url, false ) );
	}
}
