<?php
/**
 *
 * @file
 * Register custom gutenberg blocks.
 *
 * @package WordPress
 */

/**
 * Register custom block types.
 */
function register_custom_block_types() {
  if ( function_exists( 'acf_register_block_type' ) ) {
    // Register a banner block.
    acf_register_block_type(
      array(
        'name'            => 'accordion',
        'title'           => 'Accordion',
        'description'     => 'A custom accordion block.',
        'category'        => 'custom',
        'icon'            => 'list-view',
        'keywords'        => array( 'accordion', 'section' ),
        'render_template' => get_stylesheet_directory() . '/views/blocks/accordion.php',
        'mode'            => 'edit',
        'supports'        => array(
          'mode' => false,
          'anchor' => true,
        ),
      )
    );
    // Register a cards block.
    acf_register_block_type(
      array(
        'name'            => 'cards',
        'title'           => 'Cards',
        'description'     => 'A custom cards block.',
        'category'        => 'custom',
        'icon'            => 'grid-view',
        'keywords'        => array( 'cards', 'blocks'),
        'render_template' => get_stylesheet_directory() . '/views/blocks/cards.php',
        'mode'            => 'edit',
        'supports'        => array(
          'mode' => false,
          'anchor' => true,
        ),
      )
    );
    // Register a cards block.
    acf_register_block_type(
      array(
        'name'            => 'image_row',
        'title'           => 'Image Row',
        'description'     => 'A custom image row block.',
        'category'        => 'custom',
        'icon'            => 'format-gallery',
        'keywords'        => array( 'image', 'row', 'section'),
        'render_template' => get_stylesheet_directory() . '/views/blocks/image-row.php',
        'mode'            => 'edit',
        'supports'        => array(
          'mode' => false,
          'anchor' => true,
        ),
      )
    );
    // Register a newsletter block.
    acf_register_block_type(
      array(
        'name'            => 'newsletter',
        'title'           => 'Newsletter',
        'description'     => 'A custom newsletter block.',
        'category'        => 'custom',
        'icon'            => 'email',
        'keywords'        => array( 'newsletter', 'blocks'),
        'render_template' => get_stylesheet_directory() . '/views/blocks/newsletter.php',
        'mode'            => 'edit',
        'supports'        => array(
          'mode' => false,
          'anchor' => true,
        ),
      )
    );
    // Register a posts slider block.
    acf_register_block_type(
      array(
        'name'            => 'posts-slider',
        'title'           => 'Posts Slider',
        'description'     => 'A custom posts slider block.',
        'category'        => 'custom',
        'icon'            => 'slides',
        'keywords'        => array( 'posts', 'slider' ),
        'render_template' => get_stylesheet_directory() . '/views/blocks/posts-slider.php',
        'mode'            => 'edit',
        'supports'        => array(
          'mode' => false,
          'anchor' => true,
        ),
      )
    );
    // Register a promo block.
    acf_register_block_type(
      array(
        'name'            => 'promo',
        'title'           => 'Promo',
        'description'     => 'A custom promo block.',
        'category'        => 'custom',
        'icon'            => 'megaphone',
        'keywords'        => array( 'promo', 'section'),
        'render_template' => get_stylesheet_directory() . '/views/blocks/promo.php',
        'mode'            => 'edit',
        'supports'        => array(
          'mode' => false,
          'anchor' => true,
        ),
      )
    );
    // Register a steps block.
    acf_register_block_type(
      array(
        'name'            => 'steps',
        'title'           => 'Steps',
        'description'     => 'A custom steps block.',
        'category'        => 'custom',
        'icon'            => 'editor-ol',
        'keywords'        => array( 'steps', 'blocks'),
        'render_template' => get_stylesheet_directory() . '/views/blocks/steps.php',
        'mode'            => 'edit',
        'supports'        => array(
          'mode' => false,
          'anchor' => true,
        ),
      )
    );
    // Register a disclaimer block.
    acf_register_block_type(
      array(
        'name'            => 'disclaimer',
        'title'           => 'Disclaimer',
        'description'     => 'A custom disclaimer block.',
        'category'        => 'custom',
        'icon'            => 'warning',
        'keywords'        => array( 'disclaimer', 'text'),
        'render_template' => get_stylesheet_directory() . '/views/blocks/disclaimer.php',
        'mode'            => 'edit',
        'supports'        => array(
          'mode' => false,
          'anchor' => true,
        ),
      )
    );
  }
}
add_action( 'init', 'register_custom_block_types' );
