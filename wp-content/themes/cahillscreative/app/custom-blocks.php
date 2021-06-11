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
        'icon'            => 'id',
        'keywords'        => array( 'accordion', 'section' ),
        'render_template' => get_stylesheet_directory() . '/views/blocks/accordion.php',
        'mode'            => 'edit',
        'supports'        => array(
          'mode' => false,
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
        'icon'            => 'screenoptions',
        'keywords'        => array( 'cards', 'blocks'),
        'render_template' => get_stylesheet_directory() . '/views/blocks/cards.php',
        'mode'            => 'edit',
        'supports'        => array(
          'mode' => false,
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
        'icon'            => 'screenoptions',
        'keywords'        => array( 'image', 'row', 'section'),
        'render_template' => get_stylesheet_directory() . '/views/blocks/image-row.php',
        'mode'            => 'edit',
        'supports'        => array(
          'mode' => false,
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
        'icon'            => 'screenoptions',
        'keywords'        => array( 'newsletter', 'blocks'),
        'render_template' => get_stylesheet_directory() . '/views/blocks/newsletter.php',
        'mode'            => 'edit',
        'supports'        => array(
          'mode' => false,
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
        'icon'            => 'screenoptions',
        'keywords'        => array( 'promo', 'section'),
        'render_template' => get_stylesheet_directory() . '/views/blocks/promo.php',
        'mode'            => 'edit',
        'supports'        => array(
          'mode' => false,
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
        'icon'            => 'screenoptions',
        'keywords'        => array( 'steps', 'blocks'),
        'render_template' => get_stylesheet_directory() . '/views/blocks/steps.php',
        'mode'            => 'edit',
        'supports'        => array(
          'mode' => false,
        ),
      )
    );
  }
}
add_action( 'init', 'register_custom_block_types' );
