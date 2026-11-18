<?php
/**
 * The daily link check pass: its day and what is left.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkHealth;

/**
 * The daily pass (F11 D2, D11; Lasso's lasso_check_issue_date and its queue): the site day it ran for, how many
 * products it queued, how many it has checked, and the products still waiting, in order.
 */
final class CheckPass {

	/**
	 * Builds a pass.
	 *
	 * @param string    $date    The site day (Y-m-d); '' before the first pass.
	 * @param int       $total   Products queued.
	 * @param int       $checked Products checked so far.
	 * @param list<int> $queue   Products still waiting.
	 */
	public function __construct(
		public readonly string $date,
		public readonly int $total,
		public readonly int $checked,
		public readonly array $queue,
	) {}

	/**
	 * A pass from its stored form; the empty pass for anything else.
	 *
	 * @param mixed $stored The option's value.
	 */
	public static function from_array( mixed $stored ): self {
		$stored = is_array( $stored ) ? $stored : array();
		$queue  = is_array( $stored['queue'] ?? null ) ? array_values( array_map( 'intval', $stored['queue'] ) ) : array();

		return new self( (string) ( $stored['date'] ?? '' ), (int) ( $stored['total'] ?? 0 ), (int) ( $stored['checked'] ?? 0 ), $queue );
	}

	/**
	 * The stored form.
	 *
	 * @return array{date: string, total: int, checked: int, queue: list<int>}
	 */
	public function to_array(): array {
		return array(
			'date'    => $this->date,
			'total'   => $this->total,
			'checked' => $this->checked,
			'queue'   => $this->queue,
		);
	}

	/**
	 * The pass after products left the queue.
	 *
	 * @param list<int> $queue   What is still waiting.
	 * @param int       $checked How many of those that left were checked.
	 */
	public function advanced( array $queue, int $checked ): self {
		return new self( $this->date, $this->total, $this->checked + $checked, $queue );
	}
}
