<?php
/**
 * What the extension API may do with products.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Extension;

use Affibloom\Application\Products\DuplicateLink;
use Affibloom\Application\Products\ProductNotFound;
use Affibloom\Application\Products\ProductService;
use Affibloom\Application\Products\ValidationFailed;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Interface\Rest\ProductResource;
use Closure;
use InvalidArgumentException;

/**
 * What the extension API may do with products (Tasks 25A, 26A, 26B): check fields, import a product, read its fields,
 * change them, retire or trash it, each through Affibloom's own service and repository, so a helper never writes
 * Affibloom's posts or meta itself. A refusal comes out as an InvalidArgumentException naming the fields.
 */
final class ProductAccess {

	/** What the product page's form does not send back: who it is, where it is shown, which groups hold it. */
	private const NOT_FIELDS = array( 'id', 'public_link', 'groups', 'primary_position', 'slug', 'type', 'asin' );

	/**
	 * Builds the access.
	 *
	 * @param ProductService                     $service  The product page's rules.
	 * @param ProductRepository                  $products The library.
	 * @param Closure(int, string, string): void $dates    Writes a product's created and changed dates.
	 */
	public function __construct( private ProductService $service, private ProductRepository $products, private Closure $dates ) {}

	/**
	 * What Save Changes would refuse.
	 *
	 * @param array<string, mixed> $fields Field => value.
	 * @return array<string, string>
	 */
	public function check( array $fields ): array {
		return $this->service->check( $fields );
	}

	/**
	 * A new published product from the fields, with its dates.
	 *
	 * @param array<string, mixed> $fields   Field => value; `name` and `url` needed.
	 * @param string               $created  "Y-m-d H:i:s" in the site's time, '' for now.
	 * @param string               $modified As $created.
	 * @throws InvalidArgumentException When a field is refused.
	 */
	public function import( array $fields, string $created, string $modified ): int {
		try {
			$id = (int) $this->service->import( $fields )->id;
		} catch ( ValidationFailed $refused ) {
			throw new InvalidArgumentException( esc_html( self::refusals( $refused ) ) );
		}
		if ( '' !== $created && '' !== $modified ) {
			( $this->dates )( $id, $created, $modified );
		}

		return $id;
	}

	/**
	 * A product's fields as the product page holds them, or null when there is no such product.
	 *
	 * @param int $id The product.
	 * @return array<string, mixed>|null
	 */
	public function fields( int $id ): ?array {
		$product = $this->products->get( $id );

		return null === $product ? null : array_diff_key( ( new ProductResource() )->full( $product ), array_flip( self::NOT_FIELDS ) );
	}

	/**
	 * A product's cloaked address: its slug, the old slugs that still answer (F24 N1), its cloaking switch, and
	 * whether it is retired (its address still answers, F24 D3); null when there is no published or retired product
	 * with this ID (Task 26G; the cutover rehearsal's F2).
	 *
	 * @param int $id The product.
	 * @return array{slug: string, old_slugs: list<string>, cloak: bool, retired: bool}|null
	 */
	public function address( int $id ): ?array {
		$product = $this->products->answering( $id );

		return null === $product ? null : array(
			'slug'      => $product->slug,
			'old_slugs' => $product->old_slugs,
			'cloak'     => $product->cloak,
			'retired'   => null === $this->products->get( $id ),
		);
	}

	/**
	 * The product that answers at a slug, as its slug or an old one; 0 when none (Task 26G).
	 *
	 * @param string $slug The slug.
	 */
	public function holder( string $slug ): int {
		$slug = sanitize_title( $slug );
		if ( '' === $slug ) {
			return 0;
		}

		return ( $this->products->find_by_slug( $slug ) ?? $this->products->find_by_old_slug( $slug ) )->id ?? 0;
	}

	/**
	 * Sets a published or retired product's slug, its old slugs and its cloaking switch (Task 26G); a retired product
	 * stays retired (the cutover rehearsal's F2). Slugs are taken in WordPress's form (sanitize_title(): another
	 * plugin's post slugs, underscores and full length kept); one another product answers at, as its slug or an old
	 * one, is refused, so no address changes hands.
	 *
	 * @param int          $id        The product.
	 * @param string       $slug      Its slug.
	 * @param list<string> $old_slugs The old slugs that keep answering.
	 * @param bool         $cloak     Its cloaking switch.
	 * @throws InvalidArgumentException When there is no such product, or a slug is not one or is another product's.
	 */
	public function set_address( int $id, string $slug, array $old_slugs, bool $cloak ): void {
		$product = $this->products->answering( $id );
		if ( null === $product ) {
			throw new InvalidArgumentException( esc_html( "No product $id." ) );
		}
		$slug = sanitize_title( $slug );
		if ( '' === $slug ) {
			throw new InvalidArgumentException( 'slug: invalid' );
		}
		$old = array();
		foreach ( array_merge( array( $slug ), $old_slugs ) as $index => $typed ) {
			$clean = sanitize_title( (string) $typed );
			$other = '' === $clean ? null : ( $this->products->find_by_slug( $clean ) ?? $this->products->find_by_old_slug( $clean ) );
			if ( null !== $other && $other->id !== $id ) {
				throw new InvalidArgumentException( esc_html( ( 0 === $index ? 'slug' : 'old_slugs' ) . ": taken ($clean, product {$other->id})" ) );
			}
			if ( 0 !== $index && '' !== $clean && $clean !== $slug ) {
				$old[] = $clean;
			}
		}
		$old = array_values( array_unique( $old ) );
		if ( null === $this->products->get( $id ) ) {
			// Retired: save() would publish it, so only its address is written.
			if ( ! $this->products->move_address( $id, $slug, $old, $cloak ) ) {
				throw new InvalidArgumentException( esc_html( "No product $id." ) );
			}
			$saved = $this->products->answering( $id ) ?? $product;
		} else {
			$saved = $this->products->save(
				$product->with(
					array(
						'slug'      => $slug,
						'old_slugs' => $old,
						'cloak'     => $cloak,
					)
				)
			);
		}
		if ( $saved->slug !== $slug ) {
			throw new InvalidArgumentException( esc_html( "slug: taken ($slug, saved as {$saved->slug})" ) );
		}
	}

	/**
	 * Changes a published product's fields, as Save Changes does.
	 *
	 * @param int                  $id     The product.
	 * @param array<string, mixed> $fields Field => value.
	 * @throws InvalidArgumentException When a field is refused, the link is another product's, or there is no such
	 *                                  published product.
	 */
	public function update( int $id, array $fields ): void {
		try {
			$this->service->update( $id, $fields );
		} catch ( ValidationFailed $refused ) {
			throw new InvalidArgumentException( esc_html( self::refusals( $refused ) ) );
		} catch ( ProductNotFound ) {
			throw new InvalidArgumentException( esc_html( "No published product $id." ) );
		} catch ( DuplicateLink ) {
			throw new InvalidArgumentException( 'url: already_exists' );
		}
	}

	/**
	 * Retires a product, pointing at the product it was merged into (0 for none).
	 *
	 * @param int $id The product.
	 * @param int $to The product it points at.
	 * @throws InvalidArgumentException When there is no such product.
	 */
	public function retire( int $id, int $to ): void {
		$product = $this->products->get( $id );
		if ( null === $product ) {
			throw new InvalidArgumentException( esc_html( "No product $id." ) );
		}
		$this->products->retire( $product, $to );
	}

	/**
	 * Writes a product's created and changed dates, as given (after a status change, which stamps "now").
	 *
	 * @param int    $id       The product.
	 * @param string $created  "Y-m-d H:i:s" in the site's time.
	 * @param string $modified As $created.
	 */
	public function date( int $id, string $created, string $modified ): void {
		( $this->dates )( $id, $created, $modified );
	}

	/**
	 * Moves a product to the trash.
	 *
	 * @param int $id The product.
	 * @throws InvalidArgumentException When it could not be trashed.
	 */
	public function trash( int $id ): void {
		if ( ! $this->products->trash( $id ) ) {
			throw new InvalidArgumentException( esc_html( "Could not trash product $id." ) );
		}
	}

	/**
	 * The refused fields as one line, "url: invalid_url; name: required".
	 *
	 * @param ValidationFailed $refused The refusal.
	 */
	private static function refusals( ValidationFailed $refused ): string {
		$parts = array();
		foreach ( $refused->errors as $field => $reason ) {
			$parts[] = $field . ': ' . $reason;
		}

		return implode( '; ', $parts );
	}
}
