<?php

/**
 * Advanced Ads Plain Ad Type
 *
 * @package   Advanced_Ads
 * @author    Thomas Maier <support@wpadvancedads.com>
 * @license   GPL-2.0+
 * @link      https://wpadvancedads.com
 * @copyright 2014 Thomas Maier, Advanced Ads GmbH
 *
 * Class containing information about the plain text/code ad type
 *
 * see ad-type-content.php for a better sample on ad type
 */
class Advanced_Ads_Ad_Type_Plain extends Advanced_Ads_Ad_Type_Abstract {

	/**
	 * ID - internal type of the ad type
	 *
	 * @var string $ID ad type id.
	 */
	public $ID = 'plain';

	/**
	 * Set basic attributes
	 */
	public function __construct() {
		$this->title       = __( 'Plain Text and Code', 'advanced-ads' );
		$this->description = __( 'Any ad network, Amazon, customized AdSense codes, shortcodes, and code like JavaScript, HTML or PHP.', 'advanced-ads' );
		$this->parameters  = array(
			'content' => '',
		);
	}

	/**
	 * Output for the ad parameters metabox
	 *
	 * This will be loaded using ajax when changing the ad type radio buttons
	 * echo the output right away here
	 * name parameters must be in the "advanced_ads" array
	 *
	 * @param object $ad Advanced_Ads_Ad.
	 */
	public function render_parameters( $ad ) {
		// Load content.
		$content = ( isset( $ad->content ) ) ? $ad->content : '';

		?><p class="description"><?php esc_html_e( 'Insert plain text or code into this field.', 'advanced-ads' ); ?></p>
		<textarea id="advads-ad-content-plain" cols="40" rows="10" name="advanced_ad[content]"
				onkeyup="Advanced_Ads_Admin.check_ad_source();"><?php echo esc_textarea( $content ); ?></textarea>
		<?php include ADVADS_BASE_PATH . 'admin/views/ad-info-after-textarea.php'; ?>
		<input type="hidden" name="advanced_ad[output][allow_php]" value="0"/>

		<?php

		$this->render_php_allow( $ad );
		$this->render_shortcodes_allow( $ad );
		?>
		<script>jQuery( function () { Advanced_Ads_Admin.check_ad_source() } )</script>
		<?php
	}

	/**
	 * Render php output field
	 *
	 * @param object $ad Advanced_Ads_Ad object.
	 */
	public function render_php_allow( $ad ) {
		if ( defined( 'ADVANCED_ADS_DISALLOW_PHP' ) ) {
			return;
		}

		$content = ( isset( $ad->content ) ) ? $ad->content : '';

		// Check if php is allowed.
		if ( isset( $ad->output['allow_php'] ) ) {
			$allow_php = absint( $ad->output['allow_php'] );
		} else {
			/**
			 * For compatibility for ads with PHP added prior to 1.3.18
			 *  check if there is php code in the content
			 */
			if ( preg_match( '/\<\?php/', $content ) ) {
				$allow_php = 1;
			} else {
				$allow_php = 0;
			}
		}
		?>
		<label class="label" for="advads-parameters-php"><?php esc_html_e( 'Allow PHP', 'advanced-ads' ); ?></label>
		<div>
			<input id="advads-parameters-php" type="checkbox" name="advanced_ad[output][allow_php]"
					value="1" <?php checked( 1, $allow_php ); ?>
					onChange="Advanced_Ads_Admin.check_ad_source();"/>
					<?php
					echo wp_kses(
						__( 'Execute PHP code (wrapped in <code>&lt;?php ?&gt;</code>)', 'advanced-ads' ),
						array(
							'code' => array(),
						)
					);
					?>
			<div class="advads-error-message" id="advads-parameters-php-warning"
					style="display:none;"><?php esc_html_e( 'No PHP tag detected in your code.', 'advanced-ads' ); ?><?php esc_html_e( 'Uncheck this checkbox for improved performance.', 'advanced-ads' ); ?></div>
		</div>
		<hr/>
		<?php

	}

	/**
	 * Render allow shortcodes field.
	 *
	 * @param object $ad Advanced_Ads_Ad object.
	 */
	public function render_shortcodes_allow( $ad ) {
		$allow_shortcodes = ! empty( $ad->output['allow_shortcodes'] );
		?>
		<label class="label"
				for="advads-parameters-shortcodes"><?php esc_html_e( 'Allow shortcodes', 'advanced-ads' ); ?></label>
		<div>
			<input id="advads-parameters-shortcodes" type="checkbox" name="advanced_ad[output][allow_shortcodes]"
					value="1"
					<?php
					checked( 1, $allow_shortcodes );
					?>
					onChange="Advanced_Ads_Admin.check_ad_source();"/>
					<?php
					esc_html_e( 'Execute shortcodes', 'advanced-ads' );
					?>
			<div class="advads-error-message" id="advads-parameters-shortcodes-warning"
					style="display:none;"><?php esc_html_e( 'No shortcode detected in your code.', 'advanced-ads' ); ?><?php esc_html_e( 'Uncheck this checkbox for improved performance.', 'advanced-ads' ); ?></div>
		</div>
		<hr/>
		<?php
	}

	/**
	 * Prepare the ads frontend output
	 *
	 * @param Advanced_Ads_Ad $ad ad object.
	 *
	 * @return string $content ad content prepared for frontend output.
	 * @since 1.0.0
	 */
	public function prepare_output( $ad ) {
		$content = $ad->content;
		// Evaluate the code as PHP if setting was never saved or is allowed.
		if ( ! defined( 'ADVANCED_ADS_DISALLOW_PHP' ) && ( ! isset( $ad->output['allow_php'] ) || $ad->output['allow_php'] ) ) {
			ob_start();
			// This code only runs if the "Allow PHP" option for plain text ads was enabled.
			// phpcs:ignore Squiz.PHP.Eval.Discouraged -- this is specifically eval'd so allow eval here.
			eval( '?>' . $ad->content );
			$content = ob_get_clean();
		}

		if ( ! is_string( $content ) ) {
			return '';
		}

		/**
		 * Apply do_blocks if the content has block code
		 * works with WP 5.0.0 and later
		 */
		if ( function_exists( 'has_blocks' ) && has_blocks( $content ) ) {
			$content = do_blocks( $content );
		}

		if ( ! empty( $ad->output['allow_shortcodes'] ) ) {
			$content = $this->do_shortcode( $content, $ad );
		}

		// Add 'loading' attribute if applicable, available from WP 5.5.
		if (
			function_exists( 'wp_lazy_loading_enabled' )
			&& wp_lazy_loading_enabled( 'img', 'the_content' )
			&& preg_match_all( '/<img\s[^>]+>/', $content, $matches )
		) {
			// iterate images.
			foreach ( $matches[0] as $image ) {
				// skip if it already has the loading attribute.
				if ( strpos( $image, 'loading=' ) !== false ) {
					continue;
				}

				// replace the image string.
				$content = str_replace( $image, wp_img_tag_add_loading_attr( $image, 'the_content' ), $content );
			}
		}

		return $content;
	}

}
