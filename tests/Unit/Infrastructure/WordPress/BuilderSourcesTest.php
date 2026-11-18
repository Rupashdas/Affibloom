<?php
namespace Affibloom\Tests\Unit\Infrastructure\WordPress;

use Affibloom\Domain\LinkIndex\LinkType;
use Affibloom\Infrastructure\WordPress\Builders\MvCreateSource;
use Affibloom\Infrastructure\WordPress\Builders\TablePressSource;
use Affibloom\Infrastructure\WordPress\Builders\WprmSource;
use Affibloom\Tests\Unit\TestCase;

/**
 * F21 R32 (Task 35E): where the builders and recipe plugins keep a post's links.
 */
final class BuilderSourcesTest extends TestCase {

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		if ( ! class_exists( \WP_Post::class ) ) {
			require_once dirname( __DIR__, 7 ) . '/wp-includes/class-wp-post.php';
		}
	}

	public function test_a_tablepress_table_is_its_cells(): void {
		$table               = new \WP_Post( (object) array() );
		$table->post_type    = 'tablepress_table';
		$table->post_content = '[["Name","Link"],["Shoe","<a href=\"https://www.example.com/shoe\">Buy</a>"],["",""]]';

		self::assertSame( array( 'content' => "Name\nLink\nShoe\n<a href=\"https://www.example.com/shoe\">Buy</a>" ), ( new TablePressSource() )->read( $table, array( 'content' => $table->post_content ) ) );
	}

	public function test_the_recipes_and_cards_a_post_shows_are_found(): void {
		self::assertSame( array( 12, 34, 56 ), WprmSource::recipe_ids( '[wprm-recipe id="12"] <!--WPRM Recipe 34--> <!-- wp:wp-recipe-maker/recipe {"id":56,"align":"wide"} /-->' ) );
		self::assertSame( array( 7, 8 ), MvCreateSource::card_ids( '[mv_create key="7" type="recipe"] <!-- wp:mv/recipe {"id":8} /-->' ) );
	}

	public function test_a_recipe_or_card_place_gives_lassos_link_type(): void {
		self::assertSame( LinkType::WpRecipeMaker, LinkType::of_source( 'wprm:12' ) );
		self::assertSame( LinkType::MvCreate, LinkType::of_source( 'mv:7' ) );
		self::assertNull( LinkType::of_source( 'content' ) );
	}
}
