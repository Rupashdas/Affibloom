<?php
namespace Affibloom\Tests\Unit\Interface\Admin;

use Affibloom\Domain\Shell\ShellNotice;
use Affibloom\Interface\Admin\SiteStripeNotice;
use Affibloom\Interface\NoticeText;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/**
 * Lasso's "Migrating from Amazon SiteStripe?" notice (C1N; R72, AC19): on every Affibloom page while Auto-Monetization
 * is off, until someone dismisses it for the site.
 */
final class SiteStripeNoticeTest extends TestCase {
	/** @var array<string, mixed> */
	private array $options = array();

	private bool $can = true;

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'current_user_can' )->alias( fn() => $this->can );
		Functions\when( 'get_option' )->alias( fn( string $name, $fallback = false ) => $this->options[ $name ] ?? $fallback );
		Functions\when( 'update_option' )->alias(
			function ( string $name, $value ): bool {
				$this->options[ $name ] = $value;
				return true;
			}
		);
		Functions\when( 'admin_url' )->alias( static fn( string $path = '' ) => 'https://site.test/wp-admin/' . $path );
		Functions\when( '__' )->returnArg();
	}

	private static function notice( bool $auto = false ): SiteStripeNotice {
		return new SiteStripeNotice( new InMemorySettingsRepository( array( 'auto_monetize_amazon' => $auto ) ) );
	}

	public function test_it_shows_while_auto_monetization_is_off_and_links_to_the_amazon_tab(): void {
		$notices = self::notice()->notices( 'affibloom' );

		self::assertCount( 1, $notices );
		$notice = $notices[0];
		self::assertSame( array( SiteStripeNotice::KEY, ShellNotice::WARNING, true ), array( $notice->key, $notice->tone, $notice->dismissible ) );
		self::assertSame( 'https://site.test/wp-admin/admin.php?page=affibloom-settings&tab=amazon', $notice->action_url );
		self::assertSame( '/affibloom/v1/notices/sitestripe/dismiss', $notice->dismiss_path );
		self::assertSame(
			array(
				'text'   => 'Migrating from Amazon SiteStripe? %s to auto-migrate all your SiteStripe links within 24-48 hours!',
				'action' => 'Turn on Auto Amazon',
			),
			NoticeText::for( $notice )
		);
	}

	public function test_it_is_hidden_while_auto_monetization_is_on(): void {
		self::assertSame( array(), self::notice( true )->notices( 'affibloom' ) );
	}

	public function test_a_dismissal_hides_it_for_the_whole_site(): void {
		SiteStripeNotice::dismiss();

		self::assertSame( '0', $this->options[ SiteStripeNotice::OPTION ] );
		self::assertSame( array(), self::notice()->notices( 'affibloom' ) );
	}

	public function test_only_people_who_may_dismiss_it_see_it(): void {
		$this->can = false;

		self::assertSame( array(), self::notice()->notices( 'affibloom' ) );
	}
}
