<?php

namespace threewp_broadcast\premium_pack;

class ThreeWP_Broadcast_3rd_Party_Pack
	extends \threewp_broadcast\premium_pack\Plugin_Pack
{
	public $plugin_version = BROADCAST_3RD_PARTY_PACK_VERSION;

	public function edd_get_item_name()
	{
		return 'ThreeWP Broadcast 3rd Party Pack';
	}

	public function get_plugin_classes()
	{
		return
		[
			__NAMESPACE__ . '\\acf\\ACF',
			__NAMESPACE__ . '\\activity_monitor\\Activity_Monitor',
			__NAMESPACE__ . '\\all_in_one_event_calendar\\All_In_One_Event_Calendar',
			__NAMESPACE__ . '\\aqua_page_builder\\Aqua_Page_Builder',
			__NAMESPACE__ . '\\avia_layout_builder\\Avia_Layout_Builder',
			__NAMESPACE__ . '\\bbpress\\BBPress',
			__NAMESPACE__ . '\\beaver_builder\\Beaver_Builder',
			__NAMESPACE__ . '\\caldera_forms\\Caldera_Forms',
			__NAMESPACE__ . '\\calendarize_it\\Calendarize_It',
			__NAMESPACE__ . '\\category_order_and_taxonomy_terms_order\\Category_Order_and_Taxonomy_Terms_Order',
			__NAMESPACE__ . '\\cm_tooltip_glossary\\CM_Tooltip_Glossary',
			__NAMESPACE__ . '\\contact_form_7\\Contact_Form_7',
			__NAMESPACE__ . '\\create\\Create',
			__NAMESPACE__ . '\\divi_builder\\Divi_Builder',
			__NAMESPACE__ . '\\download_manager\\Download_Manager',
			__NAMESPACE__ . '\\download_monitor\\Download_Monitor',
			__NAMESPACE__ . '\\elementor\\Elementor',
			__NAMESPACE__ . '\\event_organiser\\Event_Organiser',
			__NAMESPACE__ . '\\eventon\\EventON',
			__NAMESPACE__ . '\\events_manager\\Events_Manager',
			__NAMESPACE__ . '\\fliphtml5\\FlipHTML5',
			__NAMESPACE__ . '\\foogallery\\FooGallery',
			__NAMESPACE__ . '\\formidable\\Formidable',
			__NAMESPACE__ . '\\geo_my_wordpress\\GEO_my_WordPress',
			__NAMESPACE__ . '\\geodirectory\\Geodirectory',
			__NAMESPACE__ . '\\global_blocks_for_cornerstone\\Global_Blocks_For_Cornerstone',
			__NAMESPACE__ . '\\global_content_blocks\\Global_Content_Blocks',
			__NAMESPACE__ . '\\goodlayers\\GoodLayers',
			__NAMESPACE__ . '\\google_maps_pro\\Google_Maps_Pro',
			__NAMESPACE__ . '\\gravity_forms\\Gravity_Forms',
			__NAMESPACE__ . '\\h5p\\H5P',
			__NAMESPACE__ . '\\image_map_pro\\Image_Map_Pro',
			__NAMESPACE__ . '\\inboundnow\\Inboundnow',
			__NAMESPACE__ . '\\intagrate\\Intagrate',
			__NAMESPACE__ . '\\jetpack\\Jetpack',
			__NAMESPACE__ . '\\learndash\\LearnDash',
			__NAMESPACE__ . '\\mailster\\Mailster',
			__NAMESPACE__ . '\\metaslider\\Metaslider',
			__NAMESPACE__ . '\\modern_events\\Modern_Events',
			__NAMESPACE__ . '\\ninja_forms\\Ninja_Forms',
			__NAMESPACE__ . '\\ns_cloner\\NS_Cloner',
			__NAMESPACE__ . '\\onesignal\\OneSignal',
			__NAMESPACE__ . '\\pods\\Pods',
			__NAMESPACE__ . '\\polylang\\Polylang',
			__NAMESPACE__ . '\\post_expirator\\Post_Expirator',
			__NAMESPACE__ . '\\qode_carousels\\Qode_Carousels',
			__NAMESPACE__ . '\\sensei\\Sensei',
			__NAMESPACE__ . '\\simple_custom_post_order\\Simple_Custom_Post_Order',
			__NAMESPACE__ . '\\siteorigin_page_builder\\SiteOrigin_Page_Builder',
			__NAMESPACE__ . '\\slider_revolution\\Slider_Revolution',
			__NAMESPACE__ . '\\smartslider3\\SmartSlider3',
			__NAMESPACE__ . '\\social_networks_auto_poster\\Social_Networks_Auto_Poster',
			__NAMESPACE__ . '\\soliloquy\\Soliloquy',
			__NAMESPACE__ . '\\tablepress\\TablePress',
			__NAMESPACE__ . '\\tao_schedule_update\\Tao_Schedule_Update',
			__NAMESPACE__ . '\\the_events_calendar\\The_Events_Calendar',
			__NAMESPACE__ . '\\toolset\\Toolset',
			__NAMESPACE__ . '\\translatepress\\TranslatePress',
			__NAMESPACE__ . '\\ubermenu\\UberMenu',
			__NAMESPACE__ . '\\ultimate_member\\Ultimate_Member',
			__NAMESPACE__ . '\\unyson\\Unyson',
			__NAMESPACE__ . '\\user_access_manager\\User_Access_Manager',
			__NAMESPACE__ . '\\vimeography\\Vimeography',
			__NAMESPACE__ . '\\woocommerce\\WooCommerce',
			__NAMESPACE__ . '\\wp_all_import_pro\\WP_All_Import_Pro',
			__NAMESPACE__ . '\\wp_rocket\\WP_Rocket',
			__NAMESPACE__ . '\\wp_ultimate_recipe\\WP_Ultimate_Recipe',
			__NAMESPACE__ . '\\wp_ultimo\\WP_Ultimo',
			__NAMESPACE__ . '\\wpcustom_category_image\\WPCustom_Category_Image',
			__NAMESPACE__ . '\\wpforms\\WPForms',
			__NAMESPACE__ . '\\wplms\\WPLMS',
			__NAMESPACE__ . '\\wpml\\WPML',
			__NAMESPACE__ . '\\yoast_seo\\Yoast_SEO',
		];
	}

	/**
		@brief		Show our license in the tabs.
		@since		2015-10-28 15:10:14
	**/
	public function threewp_broadcast_plugin_pack_tabs( $action )
	{
		$action->tabs->tab( '3rd_party_pack' )
			->callback( [ $this, 'edd_admin_license_tab' ] )		// this, because the tabs object comes from plugin pack, not from here.
			->name_( '3rd party pack license' );
	}
}
