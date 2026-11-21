<?php
/**
 * A comparison table's builder.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Tables\TableService;
use Affibloom\Domain\Table\Table;
use Affibloom\Interface\Template;

/**
 * Lasso's table builder (F25 R13-R16, R34, D4, D5, D16; Task T3): `admin.php?page=affibloom-table&id=N`, or without an
 * id a table not yet named ("Untitled Table"), which naming creates. The settings save as they change and the builder is
 * redrawn from each answer (table-builder.js); a table that is gone sends the editor back to the list.
 */
final class TableScreen {

	/**
	 * The table being shown, read by guard().
	 *
	 * @var Table|null
	 */
	private ?Table $table = null;

	/**
	 * Builds the screen.
	 *
	 * @param Template     $template Template renderer.
	 * @param TableService $tables   The tables.
	 */
	public function __construct( private Template $template, private TableService $tables ) {}

	/**
	 * Before any output: a table that does not exist goes back to the list.
	 */
	public function guard(): void {
		$id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- which table to show, no action behind it.
		if ( 0 === $id ) {
			return;
		}
		$this->table = $this->tables->get( $id );
		if ( null === $this->table ) {
			wp_safe_redirect( TablesScreen::url() );
			exit;
		}
	}

	/**
	 * Prints the page. WordPress has already checked the capability (Capabilities::PRODUCTS, D3).
	 */
	public function render(): void {
		$this->template->render(
			'admin/table',
			array(
				'table'    => $this->table,
				'untitled' => null === $this->table ? $this->tables->untitled() : '',
				'all'      => $this->tables->count(),
			)
		);
	}
}
