<?php
/**
 * Pages a helper plugin adds through the extension API.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Interface\Template;
use Closure;

/**
 * Pages a helper plugin adds (Task LM1, `Api::add_page()`), each printed in Affibloom's design: the header, the page
 * head with the helper's title and subtitle, the helper's body, the footer.
 */
final class ExtensionScreen {

	/**
	 * The added pages by slug.
	 *
	 * @var array<string, array{title: Closure(): string, subtitle: Closure(): string, body: Closure(): void}>
	 */
	private array $pages = array();

	/**
	 * Builds the screen.
	 *
	 * @param Template $template The templates.
	 */
	public function __construct( private Template $template ) {}

	/**
	 * Adds a page.
	 *
	 * @param string            $slug     The admin page slug.
	 * @param Closure(): string $title    The page title.
	 * @param Closure(): string $subtitle The line under the title.
	 * @param Closure(): void   $body     Prints the page's content.
	 */
	public function add( string $slug, Closure $title, Closure $subtitle, Closure $body ): void {
		$this->pages[ $slug ] = array(
			'title'    => $title,
			'subtitle' => $subtitle,
			'body'     => $body,
		);
	}

	/**
	 * What each added page prints, by slug, for the menu.
	 *
	 * @return array<string, callable>
	 */
	public function screens(): array {
		$screens = array();
		foreach ( array_keys( $this->pages ) as $slug ) {
			$screens[ $slug ] = fn () => $this->render( $slug );
		}

		return $screens;
	}

	/**
	 * Prints an added page.
	 *
	 * @param string $slug The page's slug.
	 */
	private function render( string $slug ): void {
		$page = $this->pages[ $slug ];
		$this->template->render(
			'admin/extension',
			array(
				'title'    => ( $page['title'] )(),
				'subtitle' => ( $page['subtitle'] )(),
				'body'     => $page['body'],
			)
		);
	}
}
