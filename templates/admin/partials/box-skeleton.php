<?php
/**
 * The shape of a product box while the first preview loads (design system, "Skeleton"): grey blocks where the badge,
 * the picture, the title, the rating, the words and the two buttons will be. The CSS shows it only while its frame is
 * loading and holds no box yet; a redraw keeps the drawn box. Shared by the product page and Settings → Display.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="affibloom-box-skeleton" aria-hidden="true">
	<span class="affibloom-skeleton affibloom-box-skeleton__badge"></span>
	<div class="affibloom-box-skeleton__body">
		<span class="affibloom-skeleton affibloom-box-skeleton__image"></span>
		<div class="affibloom-box-skeleton__text">
			<span class="affibloom-skeleton affibloom-skeleton--title"></span>
			<span class="affibloom-skeleton affibloom-skeleton--line affibloom-skeleton--short"></span>
			<span class="affibloom-skeleton affibloom-skeleton--line"></span>
			<span class="affibloom-skeleton affibloom-skeleton--line"></span>
		</div>
	</div>
	<span class="affibloom-skeleton affibloom-box-skeleton__button"></span>
	<span class="affibloom-skeleton affibloom-box-skeleton__button"></span>
</div>
