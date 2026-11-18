<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\LinkHealth\ProductLinkChecks;
use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Products\ProductService;
use Affibloom\Application\Products\ValidationFailed;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Tests\Unit\Fakes\FakePageFetcher;
use Affibloom\Tests\Unit\Fakes\InMemoryAmazonProducts;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use Affibloom\Tests\Unit\TestCase;
use DateTimeImmutable;

/**
 * The check on product save (Task 30B; F11 R15, D12, AC6): Save Changes schedules the product's link check when its
 * destination changed or it was last saved more than an hour ago; a refused save schedules nothing.
 */
final class ProductServiceLinkCheckTest extends TestCase {

	private InMemoryProductRepository $repo;

	private ?DateTimeImmutable $last_saved = null;

	/** @var list<int> */
	public array $scheduled = array();

	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\Functions\when( 'sanitize_text_field' )->alias( static fn( $s ) => trim( strip_tags( (string) $s ) ) );
		$this->repo = new InMemoryProductRepository();
	}

	private function service(): ProductService {
		$settings = new InMemorySettingsRepository();
		$resolver = new LinkResolver( new FakePageFetcher() );
		$links    = new LinkNormaliser( $resolver, $settings );
		$test     = $this;
		$checks   = new class( $test ) implements ProductLinkChecks {
			public function __construct( private ProductServiceLinkCheckTest $test ) {}

			public function last_saved( int $product_id ): ?DateTimeImmutable {
				return $this->test->last_saved();
			}

			public function schedule( int $product_id ): void {
				$this->test->scheduled[] = $product_id;
			}
		};

		return new ProductService( $this->repo, $resolver, $settings, new ProductInput( $links ), $links, InMemoryAmazonProducts::without_amazon(), link_checks: $checks );
	}

	public function last_saved(): ?DateTimeImmutable {
		return $this->last_saved;
	}

	private function stored(): int {
		return (int) $this->repo->save( Product::draft( 'Mizuno Wave', AffiliateUrl::from_string( 'https://www.example.com/mizuno' ), Settings::from_array( array() ) ) )->id;
	}

	public function test_a_new_destination_is_checked(): void {
		$id               = $this->stored();
		$this->last_saved = new DateTimeImmutable( '-5 minutes' );

		$this->service()->update( $id, array( 'url' => 'https://www.example.com/mizuno-2' ) );

		self::assertSame( array( $id ), $this->scheduled );
	}

	public function test_a_product_last_saved_over_an_hour_ago_is_checked(): void {
		$id               = $this->stored();
		$this->last_saved = new DateTimeImmutable( '-61 minutes' );

		$this->service()->update( $id, array( 'name' => 'Mizuno Wave Lightning' ) );

		self::assertSame( array( $id ), $this->scheduled );
	}

	public function test_a_product_saved_within_the_hour_with_the_same_link_is_not(): void {
		$id               = $this->stored();
		$this->last_saved = new DateTimeImmutable( '-10 minutes' );

		$this->service()->update( $id, array( 'name' => 'Mizuno Wave Lightning', 'url' => 'https://www.example.com/mizuno' ) );

		self::assertSame( array(), $this->scheduled );
	}

	public function test_a_refused_save_schedules_nothing(): void {
		$id               = $this->stored();
		$this->last_saved = new DateTimeImmutable( '-2 hours' );

		try {
			$this->service()->update( $id, array( 'name' => '' ) );
			self::fail( 'The empty name is refused.' );
		} catch ( ValidationFailed $refused ) {
			self::assertSame( array(), $this->scheduled );
		}
	}
}
