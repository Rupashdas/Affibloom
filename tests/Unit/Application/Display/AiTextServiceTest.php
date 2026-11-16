<?php
namespace Affibloom\Tests\Unit\Application\Display;

use Affibloom\Application\Display\AiTextService;
use Affibloom\Domain\Display\AiText;
use Affibloom\Domain\Display\AiTextRepository;
use PHPUnit\Framework\TestCase;

/**
 * The stored AI text (F30 R40, D14; Task DT9).
 */
final class AiTextServiceTest extends TestCase {

	/**
	 * The texts kept, by key.
	 *
	 * @param array<string, string> $texts The store.
	 */
	private static function service( array $texts ): AiTextService {
		return new AiTextService(
			new class( $texts ) implements AiTextRepository {
				/**
				 * @param array<string, string> $texts The store.
				 */
				public function __construct( private array $texts ) {}

				public function find( string $key ): ?string {
					return $this->texts[ $key ] ?? null;
				}
			}
		);
	}

	/**
	 * Lasso's key: the format, then md5 of the serialized trimmed prompt; an unknown format is `html`.
	 */
	public function test_lassos_key(): void {
		self::assertSame( 'html' . md5( serialize( 'Best shoes?' ) ), AiText::key( '  Best shoes? ', 'html' ) );
		self::assertSame( 'text' . md5( serialize( 'Best shoes?' ) ), AiText::key( 'Best shoes?', 'text' ) );
		self::assertSame( AiText::key( 'Best shoes?', 'html' ), AiText::key( 'Best shoes?', 'markdown' ) );
	}

	/**
	 * The stored text for the prompt and format; none when nothing is stored, the text is only tags, or no prompt.
	 */
	public function test_the_stored_text_or_none(): void {
		$service = self::service(
			array(
				AiText::key( 'Best shoes?', 'html' ) => '<p>These.</p>',
				AiText::key( 'Empty?', 'html' )      => '<p> </p>',
			)
		);
		self::assertSame( '<p>These.</p>', $service->find( 'Best shoes?', '' ) );
		self::assertNull( $service->find( 'Best shoes?', 'text' ) );
		self::assertNull( $service->find( 'Empty?', 'html' ) );
		self::assertNull( $service->find( 'Unknown?', 'html' ) );
		self::assertNull( $service->find( '  ', 'html' ) );
	}
}
