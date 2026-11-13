<?php
namespace Affibloom\Tests\Unit\Interface\Admin;

use Affibloom\Interface\Admin\IconChoices;
use PHPUnit\Framework\TestCase;

final class IconChoicesTest extends TestCase {
	private const BASEURL = 'http://volleyballvault.local/wp-content/uploads';
	private const BASEDIR = 'C:/site/wp-content/uploads';

	public function test_a_link_into_the_uploads_folder_is_its_file_there(): void {
		self::assertSame(
			'C:/site/wp-content/uploads/2023/04/youtube-icon.svg',
			IconChoices::upload_file( 'http://volleyballvault.local/wp-content/uploads/2023/04/youtube-icon.svg', self::BASEURL, self::BASEDIR )
		);
	}

	/** The admin may run on https while the uploads URL says http, or the other way round. */
	public function test_the_scheme_does_not_matter_and_a_query_is_dropped(): void {
		self::assertSame(
			'C:/site/wp-content/uploads/2023/06/amazon-icon.svg',
			IconChoices::upload_file( 'https://volleyballvault.local/wp-content/uploads/2023/06/amazon-icon.svg?v=2', self::BASEURL, self::BASEDIR )
		);
	}

	/** Another host, or a file outside uploads: Affibloom cannot tell, so it does not flag it. */
	public function test_a_link_outside_the_uploads_folder_has_no_file(): void {
		self::assertSame( '', IconChoices::upload_file( 'https://cdn.example.com/wp-content/uploads/a.svg', self::BASEURL, self::BASEDIR ) );
		self::assertSame( '', IconChoices::upload_file( 'http://volleyballvault.local/wp-content/themes/x/a.svg', self::BASEURL, self::BASEDIR ) );
		self::assertSame( '', IconChoices::upload_file( 'http://volleyballvault.local/wp-content/uploads-old/a.svg', self::BASEURL, self::BASEDIR ) );
	}
}
