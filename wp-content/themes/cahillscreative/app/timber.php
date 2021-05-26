<?php

/**
 * If you are installing Timber as a Composer dependency in your theme, you'll need this block
 * to load your dependencies and initialize Timber. If you are using Timber via the WordPress.org
 * plug-in, you can safely delete this block.
 */
$composer_autoload = dirname(__DIR__) . '/vendor/autoload.php';
if ( file_exists( $composer_autoload ) ) {
	require_once $composer_autoload;
	$timber = new Timber\Timber();
}

// Check if Timber is not activated
if ( ! class_exists( 'Timber' ) ) {

	add_action( 'admin_notices', function() {
		echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin.</p></div>';
	} );
	return;

}

// Add the directory of patterns in include path
Timber::$dirname = array('_patterns');

/**
 * Extend TimberSite with site wide properties
 */
class SageTimberTheme extends TimberSite {

	function __construct() {
		add_filter('timber/context', array( $this, 'add_to_context' ));
		add_filter('timber/twig', array($this, 'add_to_twig'));
		parent::__construct();
	}

	function add_to_context( $context ) {

		/* Navigation */
		$context['primary_nav_left'] = new TimberMenu('primary_nav_left');
		$context['primary_nav_right'] = new TimberMenu('primary_nav_right');
		$context['footer_nav_col_1'] = new TimberMenu('footer_nav_col_1');
		$context['footer_nav_col_2'] = new TimberMenu('footer_nav_col_2');

		/* Site info */
		$context['site'] = $this;

		/* Site info */
		$context['sidebar_primary'] = Timber::get_widgets('sidebar-primary');

		return $context;
	}

	/**
   * BEM function to pass in bem style classes.
   */
  public function bem_classes($context, $block_class, $element_class = '', $modifiers = array(), $extra = array()) {
    $base = $block_class;
    $mods = null;
    $xtra = null;
    if (isset($element_class) && $element_class != null) {
      $base = $base . '__' . $element_class;
    }
    if (isset($modifiers) && !empty($modifiers)) {
      if (!is_array($modifiers)) {
        $mods .= ' ' . $base . '--' . $modifiers;
      } else {
        foreach ($modifiers as $mod) {
          if ($mod != null) {
            $mods .= ' ' . $base . '--' . $mod;
          }
        };
      }
    }
    if (isset($extra) && !empty($extra)) {
      if (!is_array($extra)) {
        $xtra .= ' ' . $extra;
      } else {
        foreach ($extra as $xtra_item) {
          if ($xtra_item != null) {
            $xtra .= ' ' . $xtra_item;
          }
        };
      }
    }
    return $base . $mods . $xtra;
  }

  /**
   * Add Attributes function to pass in multiple attributes including bem style classes.
   */
  public function add_attributes($context, $additional_attributes = array()) {
    $attribute = null;
    if (isset($additional_attributes) && !empty($additional_attributes)) {
      foreach ($additional_attributes as $key => $value) {
        $attribute .= ' ' . $key . '=' . $value;
      };
    }
    return $attribute;
  }

	public function add_to_twig($twig) {
    $twig->addExtension(new Twig\ Extension\ StringLoaderExtension());
    $twig->addFunction(new Twig_SimpleFunction('bem_classes', array($this, 'bem_classes'), array('needs_context' => true), array('is_safe' => array('html'))) );
    $twig->addFunction(new Twig_SimpleFunction('add_attributes', array($this, 'add_attributes'), array('needs_context' => true), array('is_safe' => array('html'))) );
    return $twig;
  }
}
new SageTimberTheme();