<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Icons\IconService;
use Affibloom\Application\Icons\SiteIcons;
use Affibloom\Domain\Icons\Icon;
use Affibloom\Domain\Icons\IconRepository;
use Affibloom\Domain\Icons\IconSet;
use PHPUnit\Framework\TestCase;

final class IconServiceTest extends TestCase {
	private IconRepository $repo;
	private int $reads = 0;

	protected function setUp(): void {
		parent::setUp();
		$this->repo = new class() implements IconRepository {
			public ?IconSet $stored = null;
			public function load(): ?IconSet {
				return $this->stored;
			}
			public function save( IconSet $set ): void {
				$this->stored = $set;
			}
		};
	}

	private function service(): IconService {
		$site = new class( $this->reads ) implements SiteIcons {
			public function __construct( private int &$reads ) {}
			public function found(): array {
				++$this->reads;
				return array( Icon::imported( 'medal-icon', '/m.svg', 1 ) );
			}
		};
		return new IconService( $this->repo, $site );
	}

	public function test_the_sites_icons_are_imported_once_and_kept(): void {
		$service = $this->service();

		self::assertSame( 'medal-icon', $service->all()->all()[0]->css_class );
		$service->all();
		$this->service()->all();

		self::assertSame( 1, $this->reads );
		self::assertNotNull( $this->repo->load() );
	}

	public function test_an_added_and_removed_icon_is_stored(): void {
		$service = $this->service();

		$icon = $service->add( 'Star', 'https://example.com/s.svg', 5 );
		self::assertSame( 'star-icon', $icon->css_class );
		self::assertNotNull( $this->repo->load()?->find( 'star-icon' ) );

		$service->remove( 'star-icon' );
		self::assertNull( $this->repo->load()?->find( 'star-icon' ) );
	}
}
