<?php
namespace Affibloom\Tests\Unit\Fakes;

use Affibloom\Application\Products\FetchedPage;
use Affibloom\Application\Products\PageFetcher;

/**
 * Answers from a table (a missing URL is "no answer") and records what was asked, so no test touches the network.
 */
final class FakePageFetcher implements PageFetcher {
	/** @var list<string> */
	public array $asked = array();

	/**
	 * @param array<string, ?FetchedPage> $pages URL => page, or null for no answer.
	 */
	public function __construct( private array $pages = array() ) {}

	public function fetch( string $url ): ?FetchedPage {
		$this->asked[] = $url;
		return $this->pages[ $url ] ?? null;
	}
}
