<?php

/**
 * Handles Advanced Ads Inline CSS settings.
 */
class Advanced_Ads_Inline_Css {
	/**
	 * Singleton instance of the plugin
	 *
	 * @var self
	 */
	protected static $instance;

	/**
	 * Module options
	 *
	 * @var array
	 */
	protected $options;

	/**
	 *  Holds the state if inline css should be outputted or not.
	 *
	 * @var bool
	 */
	protected $add_inline_css;

	/**
	 * Initialize the module
	 */
	private function __construct() {
		$this->options();

		/**
		 * Filters the state if inline css should be outputted or not.
		 * Ajax CB container could have added inline css already.
		 *
		 * Set to false if an addon output inline css before the main plugin.
		 *
		 * @param bool Contains the state.
		 */
		$this->add_inline_css = apply_filters( 'advanced-ads-output-inline-css', true );
		if ( ! $this->add_inline_css ) {
			return;
		}

		// Add inline css to the tcf container.
		if ( ! empty( $this->options['enabled'] ) && $this->options['enabled'] === 'on' && $this->options['consent-method'] === 'iab_tcf_20' ) {
			add_filter( 'advanced-ads-output-final', array( $this, 'add_tcf_container' ), 20, 2 );
			$this->add_inline_css = false;
		}
	}

	/**
	 * Adds inline css.
	 *
	 * @param array  $wrapper Add wrapper array.
	 * @param string $css     Custom inline css.
	 *
	 * @return array
	 */
	public function add_css( $wrapper, $css ) {
		$this->add_inline_css = apply_filters( 'advanced-ads-output-inline-css', $this->add_inline_css );
		if ( ! $this->add_inline_css ) {
			return $wrapper;
		}

		$styles               = $this->get_styles_by_string( $css );
		$wrapper['style']     = empty( $wrapper['style'] ) ? $styles : array_merge( $wrapper['style'], $styles );
		$this->add_inline_css = false;

		return $wrapper;
	}

	/**
	 * Extend TCF output with a container containing inline css.
	 *
	 * @param string          $output The output string.
	 * @param Advanced_Ads_Ad $ad     The ad object.
	 *
	 * @return string
	 */
	public function add_tcf_container( $output, Advanced_Ads_Ad $ad ) {
		return sprintf(
			'<div class="tcf-container" style="' . $ad->options()['inline-css'] . '">%s</div>',
			$output
		);
	}

	/**
	 * Reformats css styles string to array.
	 *
	 * @param string $string CSS-Style.
	 *
	 * @return array
	 */
	private function get_styles_by_string( $string ) {
		$chunks = array_chunk( preg_split( '/[:;]/', $string ), 2 );

		return array_combine( array_filter( array_column( $chunks, 0 ) ), array_filter( array_column( $chunks, 1 ) ) );
	}

	/**
	 * Return TCF options.
	 *
	 * @return array
	 */
	public function options() {
		if ( isset( $this->options ) ) {
			return $this->options;
		}

		$this->options = get_option( Advanced_Ads_Privacy::OPTION_KEY, array() );
		if ( isset( $this->options['enabled'] ) && empty( $this->options['consent-method'] ) ) {
			$this->options['enabled'] = false;
		}

		return $this->options;
	}

	/**
	 * Return an instance of Advanced_Ads_Inline_Css
	 *
	 * @return self
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
