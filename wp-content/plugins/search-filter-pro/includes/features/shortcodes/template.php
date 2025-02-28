<?php
/**
 * Sample Results Template
 *
 * This template is an absolute base example showing you what
 * you can do, for more customisation see the WordPress docs
 * and using template tags.
 *
 * http://codex.wordpress.org/Template_Tags
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $query ) ) {
	return;
}

if ( $query->have_posts() ) {

	$paged = isset( $query->query['paged'] ) ? $query->query['paged'] : 1;
	?>
	
	Found <?php echo esc_html( $query->found_posts ); ?> Results<br />
	Page <?php echo esc_html( $paged ); ?> of <?php echo esc_html( $query->max_num_pages ); ?><br />
	
	<div class="pagination">
		
		<div class="nav-previous"><?php search_filter_get_next_posts_link( 'Older posts', $query->max_num_pages ); ?></div>
		<div class="nav-next"><?php search_filter_get_previous_posts_link( 'Newer posts' ); ?></div>
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
		while ( $query->have_posts() ) {
			$query->the_post();

			?>
			<div>
				<h2><a href="<?php esc_attr( the_permalink() ); ?>"><?php esc_html( the_title() ); ?></a></h2>
				<br />
				<p><?php esc_html( the_excerpt() ); ?></p>
				<?php
				if ( has_post_thumbnail() ) {
					echo '<p>';
					the_post_thumbnail( 'small' );
					echo '</p>';
				}
				?>
				<?php esc_html( the_category() ); ?>
				<p><?php esc_html( the_tags() ); ?></p>
				<p><small><?php esc_html( the_date() ); ?></small></p>
			</div>
			<hr />
			<?php
		}
		wp_reset_postdata();
		?>
	</div>
	Page <?php echo esc_html( $paged ); ?> of <?php echo esc_html( $query->max_num_pages ); ?><br />
	
	<div class="pagination">
		<div class="nav-previous"><?php echo search_filter_get_next_posts_link( 'Older posts', $query->max_num_pages ); ?></div>
		<div class="nav-next"><?php echo search_filter_get_previous_posts_link( 'Newer posts' ); ?></div>
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
