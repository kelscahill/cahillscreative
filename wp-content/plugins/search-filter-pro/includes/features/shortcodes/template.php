<?php
/**
 * Sample Results Template
 *
 * This template is an absolute base example showing you what
 * you can do, for more customisation see the WordPress docs
 * and using template tags.
 *
 * http://codex.wordpress.org/Template_Tags
 *
 * @package Search_Filter_Pro
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $query ) ) {
	return;
}

$allowed_pagination_html = array(
	'a' => array(
		'href'  => array(),
		'class' => array(),
		'id'    => array(),
		'rel'   => array(),
		'title' => array(),
	),
);

if ( $query->have_posts() ) {

	$current_page = isset( $query->query['paged'] ) ? $query->query['paged'] : 1;
	?>

	Found <?php echo esc_html( $query->found_posts ); ?> Results<br />
	Page <?php echo esc_html( $current_page ); ?> of <?php echo esc_html( $query->max_num_pages ); ?><br />
	
	<div class="pagination">
		<div class="nav-previous"><?php echo wp_kses( search_filter_get_next_posts_link( 'Older posts', $query->max_num_pages ), $allowed_pagination_html ); ?></div>
		<div class="nav-next"><?php echo wp_kses( search_filter_get_previous_posts_link( 'Newer posts' ), $allowed_pagination_html ); ?></div>
		<?php
			/* example code for using the wp_pagenavi plugin */
		if ( function_exists( 'wp_pagenavi' ) ) {
			echo '<br />';
			wp_pagenavi( array( 'query' => $query ) );
		}
		?>
	</div>
	<!-- Keep the `.search-filter-query-posts` class to support the load more button -->
	<div class="search-filter-query-posts">
		<?php
		/**
		 * Standard WordPress loop.
		 *
		 * @phpstan-ignore-next-line while.alwaysTrue
		 * Standard WordPress loop - PHPStan doesn't understand have_posts() changes internal state
		 */
		while ( $query->have_posts() ) {
			$query->the_post();

			?>
			<div>
				<h2><a href="<?php echo esc_url( get_the_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a></h2>
				<br />
				<p><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php
				if ( has_post_thumbnail() ) {
					echo '<p>';
					the_post_thumbnail( 'small' );
					echo '</p>';
				}
				?>
				<?php echo esc_html( get_the_category_list( ', ' ) ); ?>
				<p><?php echo esc_html( get_the_tag_list( '', ', ' ) ); ?></p>
				<p><small><?php echo esc_html( get_the_date() ); ?></small></p>
			</div>
			<hr />
			<?php
		}
		/**
		 * Reset post data.
		 *
		 * @phpstan-ignore-next-line deadCode.unreachable
		 */
		wp_reset_postdata();
		?>
	</div>
	Page <?php echo esc_html( $current_page ); ?> of <?php echo esc_html( $query->max_num_pages ); ?><br />
	
	<div class="pagination">
		<div class="nav-previous"><?php echo wp_kses( search_filter_get_next_posts_link( 'Older posts', $query->max_num_pages ), $allowed_pagination_html ); ?></div>
		<div class="nav-next"><?php echo wp_kses( search_filter_get_previous_posts_link( 'Newer posts' ), $allowed_pagination_html ); ?></div>
		<?php
			/* example code for using the wp_pagenavi plugin */
		if ( function_exists( 'wp_pagenavi' ) ) {
			echo '<br />';
			wp_pagenavi( array( 'query' => $query ) );
		}
		?>
	</div>
	<?php
} else {
	echo 'No Results Found';
}
?>
