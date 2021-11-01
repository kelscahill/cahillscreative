<?php
namespace Ezoic_Namespace;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/admin
 */
class Ezoic_AdsTxtManager_Settings {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 *
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}


	/**
	 * Register and add settings
	 */
	public function initialize_adstxtmanager_settings()
	{
		add_settings_section(
				'ezoic_adstxtmanager_settings_section',
				__('Ezoic Ads.txt Manager', 'ezoic'),
				array($this, 'ezoic_adstxtmanager_settings_section_callback'),
				'ezoic_adstxtmanager'
		);

		add_settings_field(
				'ezoic_adstxtmanager_id',
				'Ads.txt Manager ID',
				array($this, 'ezoic_adstxtmanager_id_field'),
				'ezoic_adstxtmanager',
				'ezoic_adstxtmanager_settings_section'
		);

		register_setting(
				'ezoic_adstxtmanager',
				'ezoic_adstxtmanager_status',
				array('default' => array('status' => false, 'message' => ''))
		);

		register_setting(
				'ezoic_adstxtmanager',
				'ezoic_adstxtmanager_id',
				array('default' => 0, 'type' => 'integer', 'sanitize_callback' => array($this, 'sanitize_adstxtmanager_id'))
		);
	}

	/**
	 * Empty Callback for WordPress Settings
	 *
	 * @return void
	 * @since 1.0.0
	 */
	function ezoic_adstxtmanager_settings_section_callback() {
		?>
		<?php if(empty(Ezoic_AdsTxtManager::ezoic_adstxtmanager_id(true))) : ?>
			<div class="notice notice-info" >
				<p>In order to use Ads.txt Manager, you must enter your Ads.txt Manager ID number below.</p>
				<p>To find your ID on adstxtmanager.com, <a href="<?php esc_attr_e( EZOIC_ADSTXT_MANAGER__SITE_LOGIN, 'ezoic' );?>" target="_blank"> click here</a > to login, or create a <a href="<?php esc_attr_e( ADSTXT_MANAGER__SITE, 'adstxtmanager' );?>" target = "_blank"> new account</a>.</p>
			</div >
		<?php endif; ?>
		<hr/>
		<?php
	}

	function ezoic_adstxtmanager_id_field() {
		?>
		<input type="text" name="ezoic_adstxtmanager_id" class="regular-text code"
			   value="<?php echo(Ezoic_AdsTxtManager::ezoic_adstxtmanager_id(true)); ?>"/>
		<p class="description">
			You can find your <a href="https://svc.adstxtmanager.com/settings" target="_blank">Ads.txt Manager ID here</a>.<br/><em>*Required</em>
		</p>
		<?php
	}

	public function sanitize_adstxtmanager_id($input) {
		$new_input = 0;
		if(isset($input)) {
			$new_input = absint($input);
		}
		return $new_input;
	}
}

?>
