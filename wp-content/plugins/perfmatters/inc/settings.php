<?php
//register settings + options
function perfmatters_settings() {

	if(get_option('perfmatters_options') == false) {	
		add_option('perfmatters_options', apply_filters('perfmatters_default_options', perfmatters_default_options()));
	}

    $perfmatters_options = get_option('perfmatters_options');

    //pptions primary section
    add_settings_section('perfmatters_options', __('Options', 'perfmatters'), 'perfmatters_options_callback', 'perfmatters_options');

    //disable emojis
    add_settings_field(
    	'disable_emojis', 
    	perfmatters_title(__('Disable Emojis', 'perfmatters'), 'disable_emojis', 'https://perfmatters.io/docs/disable-emojis-wordpress/'), 
    	'perfmatters_print_input', 
    	'perfmatters_options', 
    	'perfmatters_options', 
    	array(
            'id' => 'disable_emojis',
            'tooltip' => __('Removes WordPress Emojis JavaScript file (wp-emoji-release.min.js).', 'perfmatters')
        )
    );

    //disable embeds
    add_settings_field(
    	'disable_embeds', 
    	perfmatters_title(__('Disable Embeds', 'perfmatters'), 'disable_embeds', 'https://perfmatters.io/docs/disable-embeds-wordpress/'), 
    	'perfmatters_print_input', 
    	'perfmatters_options', 
    	'perfmatters_options', 
    	array(
    		'id' => 'disable_embeds',
    		'tooltip' => __('Removes WordPress Embed JavaScript file (wp-embed.min.js).', 'perfmatters')   		
    	)
    );

	//disable xml-rpc
    add_settings_field(
    	'disable_xmlrpc', 
    	perfmatters_title(__('Disable XML-RPC', 'perfmatters'), 'disable_xmlrpc', 'https://perfmatters.io/docs/disable-xml-rpc-wordpress/'), 
    	'perfmatters_print_input', 
    	'perfmatters_options', 
    	'perfmatters_options', 
    	array(
    		'id' => 'disable_xmlrpc',
    		'tooltip' => __('Disables WordPress XML-RPC functionality.', 'perfmatters')
    	)
    );

	//remove jquery migrate
    add_settings_field(
    	'remove_jquery_migrate', 
    	perfmatters_title(__('Remove jQuery Migrate', 'perfmatters'), 'remove_jquery_migrate', 'https://perfmatters.io/docs/remove-jquery-migrate-wordpress/'), 
    	'perfmatters_print_input', 
    	'perfmatters_options', 
    	'perfmatters_options', 
    	array(
    		'id' => 'remove_jquery_migrate',
    		'tooltip' => __('Removes jQuery Migrate JavaScript file (jquery-migrate.min.js).', 'perfmatters')
    	)
    );

    //hide wp version
    add_settings_field(
    	'hide_wp_version', 
    	perfmatters_title(__('Hide WP Version', 'perfmatters'), 'hide_wp_version', 'https://perfmatters.io/docs/remove-wordpress-version-number/'), 
    	'perfmatters_print_input', 
    	'perfmatters_options', 
    	'perfmatters_options', 
    	array(
    		'id' => 'hide_wp_version',
    		'tooltip' => __('Removes WordPress version meta tag.', 'perfmatters')
    	)
    );

    //remove wlmanifest Link
    add_settings_field(
    	'remove_wlwmanifest_link', 
    	perfmatters_title(__('Remove wlwmanifest Link', 'perfmatters'), 'remove_wlwmanifest_link', 'https://perfmatters.io/docs/remove-wlwmanifest-link-wordpress/'), 
    	'perfmatters_print_input', 
    	'perfmatters_options', 
    	'perfmatters_options',
        array(
        	'id' => 'remove_wlwmanifest_link',
        	'tooltip' => __('Remove wlwmanifest (Windows Live Writer) link tag.', 'perfmatters')
        )
    );

    //remove rsd link
    add_settings_field(
    	'remove_rsd_link', 
    	perfmatters_title(__('Remove RSD Link', 'perfmatters'), 'remove_rsd_link', 'https://perfmatters.io/docs/remove-rsd-link-wordpress/'), 
    	'perfmatters_print_input', 
    	'perfmatters_options', 
    	'perfmatters_options', 
    	array(
    		'id' => 'remove_rsd_link',
    		'tooltip' => __('Remove RSD (Real Simple Discovery) link tag.', 'perfmatters')
    	)
    );

    //remove shortlink
    add_settings_field(
    	'remove_shortlink', 
    	perfmatters_title(__('Remove Shortlink', 'perfmatters'), 'remove_shortlink', 'https://perfmatters.io/docs/remove-shortlink-wordpress/'), 
    	'perfmatters_print_input', 
    	'perfmatters_options', 
    	'perfmatters_options', 
    	array(
    		'id' => 'remove_shortlink',
    		'tooltip' => __('Remove Shortlink link tag.', 'perfmatters')
    	)
    );

    //disable rss feeds
    add_settings_field(
    	'disable_rss_feeds', 
    	perfmatters_title(__('Disable RSS Feeds', 'perfmatters'), 'disable_rss_feeds', 'https://perfmatters.io/docs/disable-rss-feeds-wordpress/'), 
    	'perfmatters_print_input', 
    	'perfmatters_options', 
    	'perfmatters_options', 
    	array(
    		'id' => 'disable_rss_feeds',
    		'tooltip' => __('Disable WordPress generated RSS feeds and 301 redirect URL to parent.', 'perfmatters')
    	)
    );

    //remove feed links
    add_settings_field(
    	'remove_feed_links', 
    	perfmatters_title(__('Remove RSS Feed Links', 'perfmatters'), 'remove_feed_links', 'https://perfmatters.io/docs/remove-rss-feed-links-wordpress/'), 
    	'perfmatters_print_input', 
    	'perfmatters_options', 
    	'perfmatters_options', 
    	array(
    		'id' => 'remove_feed_links',
    		'tooltip' => __('Disable WordPress generated RSS feed link tags.', 'perfmatters')
    	)
    );

    //disable self pingbacks
    add_settings_field(
    	'disable_self_pingbacks', 
    	perfmatters_title(__('Disable Self Pingbacks', 'perfmatters'), 'disable_self_pingbacks', 'https://perfmatters.io/docs/disable-self-pingbacks-wordpress/'), 
    	'perfmatters_print_input', 
    	'perfmatters_options', 
    	'perfmatters_options', 
    	array(
    		'id' => 'disable_self_pingbacks',
    		'tooltip' => __('Disable Self Pingbacks (generated when linking to an article on your own blog).', 'perfmatters')
    	)
    );

    //disable rest api
    add_settings_field(
    	'disable_rest_api', 
    	perfmatters_title(__('Disable REST API', 'perfmatters'), 'disable_rest_api', 'https://perfmatters.io/docs/disable-wordpress-rest-api/'), 
    	'perfmatters_print_input', 
    	'perfmatters_options', 
    	'perfmatters_options', 
    	array(
    		'id' => 'disable_rest_api',
    		'input' => 'select',
    		'options' => array(
    			''                   => __('Default (Enabled)', 'perfmatters'),
    			'disable_non_admins' => __('Disable for Non-Admins', 'perfmatters'),
    			'disable_logged_out' => __('Disable When Logged Out', 'perfmatters')
    		),
    		'tooltip' => __('Disables REST API requests and displays an error message if the requester doesn\'t have permission.', 'perfmatters')
    	)
    );

    //remove rest api links
    add_settings_field(
    	'remove_rest_api_links', 
    	perfmatters_title(__('Remove REST API Links', 'perfmatters'), 'remove_rest_api_links', 'https://perfmatters.io/docs/remove-wordpress-rest-api-links/'), 
    	'perfmatters_print_input', 
    	'perfmatters_options', 
    	'perfmatters_options', 
    	array(
    		'id' => 'remove_rest_api_links',
    		'tooltip' => __('Removes REST API link tag from the front end and the REST API header link from page requests.', 'perfmatters')
    	)
    );

    //disable dashicons
    add_settings_field(
        'disable_dashicons', 
        perfmatters_title(__('Disable Dashicons', 'perfmatters'), 'disable_dashicons', 'https://perfmatters.io/docs/remove-dashicons-wordpress/'), 
        'perfmatters_print_input', 
        'perfmatters_options', 
        'perfmatters_options', 
        array(
            'id' => 'disable_dashicons',
            'tooltip' => __('Disables dashicons on the front end when not logged in.', 'perfmatters')
        )
    );

    //disable google maps
    add_settings_field(
        'disable_google_maps', 
        perfmatters_title(__('Disable Google Maps', 'perfmatters'), 'disable_google_maps', 'https://perfmatters.io/docs/disable-google-maps-api-wordpress/'), 
        'perfmatters_print_input', 
        'perfmatters_options', 
        'perfmatters_options', 
        array(
            'id' => 'disable_google_maps',
            'class' => 'perfmatters-input-controller',
            'tooltip' => __('Removes any instances of Google Maps being loaded across your entire site.', 'perfmatters')
        )
    );

    //disable google maps exclusions
    add_settings_field(
        'disable_google_maps_exclusions', 
        perfmatters_title(__('Exclude Post IDs', 'perfmatters'), 'disable_google_maps_exclusions', 'https://perfmatters.io/docs/disable-google-maps-api-wordpress/#exclude'), 
        'perfmatters_print_input', 
        'perfmatters_options', 
        'perfmatters_options', 
        array(
            'id' => 'disable_google_maps_exclusions',
            'input' => 'text',
            'placeholder' => '23,19,blog',
            'class' => 'disable_google_maps' . (empty($perfmatters_options['disable_google_maps']) ? ' hidden' : ''),
            'tooltip' => __('Prevent Google Maps from being disabled on specific post IDs. Format: comma separated', 'perfmatters')
        )
    );

    //disable google fonts
    add_settings_field(
        'disable_google_fonts', 
        perfmatters_title(__('Disable Google Fonts', 'perfmatters'), 'disable_google_fonts', 'https://perfmatters.io/docs/disable-google-fonts/'), 
        'perfmatters_print_input', 
        'perfmatters_options', 
        'perfmatters_options', 
        array(
            'id' => 'disable_google_fonts',
            'tooltip' => __('Removes any instances of Google Fonts being loaded across your entire site.', 'perfmatters')
        )
    );

    //disable password strength meter
    add_settings_field(
        'disable_password_strength_meter', 
        perfmatters_title(__('Disable Password Strength Meter', 'perfmatters'), 'disable_password_strength_meter', 'https://perfmatters.io/docs/disable-password-meter-strength/'),
        'perfmatters_print_input', 
        'perfmatters_options', 
        'perfmatters_options', 
        array(
            'id' => 'disable_password_strength_meter',
            'tooltip' => __('Removes WordPress and WooCommerce Password Strength Meter scripts from non essential pages.', 'perfmatters')
        )
    );

    //disable comments
    add_settings_field(
        'disable_comments', 
        perfmatters_title(__('Disable Comments', 'perfmatters'), 'disable_comments', 'https://perfmatters.io/docs/wordpress-disable-comments/'),
        'perfmatters_print_input', 
        'perfmatters_options', 
        'perfmatters_options', 
        array(
            'id' => 'disable_comments',
            'tooltip' => __('Disables WordPress comments across your entire site.', 'perfmatters')
        )
    );

    //remove comment urls
    add_settings_field(
        'remove_comment_urls', 
        perfmatters_title(__('Remove Comment URLs', 'perfmatters'), 'remove_comment_urls', 'https://perfmatters.io/docs/remove-wordpress-comment-author-link'),
        'perfmatters_print_input', 
        'perfmatters_options', 
        'perfmatters_options', 
        array(
            'id' => 'remove_comment_urls',
            'tooltip' => __('Removes the WordPress comment author link and website field from blog posts.', 'perfmatters')
        )
    );

    //lazy loading options section
    add_settings_section('perfmatters_lazy_loading', __('Lazy Loading', 'perfmatters'), 'perfmatters_lazy_loading_callback', 'perfmatters_options');

    //images
    add_settings_field(
        'lazy_loading', 
        perfmatters_title(__('Images', 'perfmatters'), 'lazy_loading', 'https://perfmatters.io/docs/lazy-load-wordpress/#images'), 
        'perfmatters_print_input', 
        'perfmatters_options', 
        'perfmatters_lazy_loading', 
        array(
            'id' => 'lazy_loading',
            'tooltip' => __('Enable lazy loading on images.', 'perfmatters')
        )
    );

    //iframes and videos
    add_settings_field(
        'lazy_loading_iframes', 
        perfmatters_title(__('iFrames and Videos', 'perfmatters'), 'lazy_loading_iframes', 'https://perfmatters.io/docs/lazy-load-wordpress/#iframes-videos'), 
        'perfmatters_print_input', 
        'perfmatters_options', 
        'perfmatters_lazy_loading', 
        array(
            'id' => 'lazy_loading_iframes',
            'tooltip' => __('Enable lazy loading on iframes and videos.', 'perfmatters')
        )
    );

    //youtube preview thumbnails
    add_settings_field(
        'youtube_preview_thumbnails', 
        perfmatters_title(__('YouTube Preview Thumbnails', 'perfmatters'), 'youtube_preview_thumbnails', 'https://perfmatters.io/docs/lazy-load-wordpress/#youtube-preview-thumbnails'), 
        'perfmatters_print_input', 
        'perfmatters_options', 
        'perfmatters_lazy_loading', 
        array(
            'id' => 'youtube_preview_thumbnails',
            'tooltip' => __('Swap out YouTube iFrames with preview thumbnails. The original iFrame is loaded when the thumbnail is clicked.', 'perfmatters')
        )
    );

    //lazy load exclusions
    add_settings_field(
        'lazy_loading_exclusions', 
        perfmatters_title(__('Exclude from Lazy Loading', 'perfmatters'), 'lazy_loading_exclusions', 'https://perfmatters.io/docs/lazy-load-wordpress/#exclude'), 
        'perfmatters_print_input', 
        'perfmatters_options', 
        'perfmatters_lazy_loading', 
        array(
            'id' => 'lazy_loading_exclusions',
            'input' => 'textarea',
            'textareatype' => 'oneperline',
            'placeholder' => 'example.png',
            'tooltip' => __('Exclude specific elements from lazy loading. Exclude an element by adding the source URL (example.png) or by adding any unique portion of its attribute string (class="example"). Format: one per line', 'perfmatters')
        )
    );

    //DOM monitoring
    add_settings_field(
        'lazy_loading_dom_monitoring', 
        perfmatters_title(__('DOM Monitoring', 'perfmatters'), 'lazy_loading_dom_monitoring', 'https://perfmatters.io/docs/lazy-load-wordpress/#dom-monitoring'), 
        'perfmatters_print_input', 
        'perfmatters_options', 
        'perfmatters_lazy_loading', 
        array(
            'id' => 'lazy_loading_dom_monitoring',
            'tooltip' => __('Watch for changes in the DOM and dynamically lazy load newly added elements.', 'perfmatters')
        )
    );

    //disable heartbeat
    add_settings_field(
    	'disable_heartbeat', 
    	perfmatters_title(__('Disable Heartbeat', 'perfmatters'), 'disable_heartbeat', 'https://perfmatters.io/docs/disable-wordpress-heartbeat-api/'), 
    	'perfmatters_print_input', 
    	'perfmatters_options', 
    	'perfmatters_options', 
    	array(
    		'id' => 'disable_heartbeat',
    		'input' => 'select',
    		'options' => array(
    			''                   => __('Default', 'perfmatters'),
    			'disable_everywhere' => __('Disable Everywhere', 'perfmatters'),
    			'allow_posts'        => __('Only Allow When Editing Posts/Pages', 'perfmatters')
    		),
    		'tooltip' => __('Disable WordPress Heartbeat everywhere or in certain areas (used for auto saving and revision tracking).', 'perfmatters')
    	)
    );

    //heartbeat frequency
    add_settings_field(
    	'heartbeat_frequency', 
    	perfmatters_title(__('Heartbeat Frequency', 'perfmatters'), 'heartbeat_frequency', 'https://perfmatters.io/docs/change-heartbeat-frequency-wordpress/'), 
    	'perfmatters_print_input', 
    	'perfmatters_options', 
    	'perfmatters_options', 
    	array(
    		'id' => 'heartbeat_frequency',
    		'input' => 'select',
    		'options' => array(
    			''   => sprintf(__('%s Seconds', 'perfmatters'), '15') . ' (' . __('Default', 'perfmatters') . ')',
                '30' => sprintf(__('%s Seconds', 'perfmatters'), '30'),
                '45' => sprintf(__('%s Seconds', 'perfmatters'), '45'),
                '60' => sprintf(__('%s Seconds', 'perfmatters'), '60')
    		),
    		'tooltip' => __('Controls how often the WordPress Heartbeat API is allowed to run.', 'perfmatters')
    	)
    );

    //limit post revisions
    add_settings_field(
    	'limit_post_revisions', 
    	perfmatters_title(__('Limit Post Revisions', 'perfmatters'), 'limit_post_revisions', 'https://perfmatters.io/docs/disable-limit-post-revisions-wordpress/'), 
    	'perfmatters_print_input', 
    	'perfmatters_options', 
    	'perfmatters_options', 
    	array(
    		'id' => 'limit_post_revisions',
    		'input' => 'select',
    		'options' => array(
    			''      => __('Default', 'perfmatters'),
    			'false' => __('Disable Post Revisions', 'perfmatters'),
    			'1'     => '1',
    			'2'     => '2',
    			'3'     => '3',
    			'4'     => '4',
    			'5'     => '5',
    			'10'    => '10',
    			'15'    => '15',
    			'20'    => '20',
    			'25'    => '25',
    			'30'    => '30'
    		),
    		'tooltip' => __('Limits the maximum amount of revisions that are allowed for posts and pages.', 'perfmatters')
    	)
    );

    //autosave interval
    add_settings_field(
    	'autosave_interval', 
    	perfmatters_title(__('Autosave Interval', 'perfmatters'), 'autosave_interval', 'https://perfmatters.io/docs/change-autosave-interval-wordpress/'), 
    	'perfmatters_print_input', 
    	'perfmatters_options', 
    	'perfmatters_options', 
    	array(
    		'id' => 'autosave_interval',
    		'input' => 'select',
    		'options' => array(
    			''    => __('1 Minute', 'perfmatters') . ' (' . __('Default', 'perfmatters') . ')',
                '120' => sprintf(__('%s Minutes', 'perfmatters'), '2'),
                '180' => sprintf(__('%s Minutes', 'perfmatters'), '3'),
                '240' => sprintf(__('%s Minutes', 'perfmatters'), '4'),
                '300' => sprintf(__('%s Minutes', 'perfmatters'), '5')
    		),
    		'tooltip' => __('Controls how often WordPress will auto save posts and pages while editing.', 'perfmatters')
    	)
    );

    //change login url
    add_settings_field(
        'login_url', 
        perfmatters_title(__('Change Login URL', 'perfmatters'), 'login_url', 'https://perfmatters.io/docs/change-wordpress-login-url/'), 
        'perfmatters_print_input', 
        'perfmatters_options', 
        'perfmatters_options', 
        array(
            'id' => 'login_url',
            'input' => 'text',
            'placeholder' => 'hideme',
            'tooltip' => __('When set, this will change your WordPress login URL (slug) to the provided string and will block wp-admin and wp-login endpoints from being directly accessed.', 'perfmatters')
        )
    );

    //woocommerce options section
    add_settings_section('perfmatters_woocommerce', 'WooCommerce', 'perfmatters_woocommerce_callback', 'perfmatters_options');

    //disable woocommerce scripts
    add_settings_field(
        'disable_woocommerce_scripts', 
        perfmatters_title(__('Disable Scripts', 'perfmatters'), 'disable_woocommerce_scripts', 'https://perfmatters.io/docs/disable-woocommerce-scripts-and-styles/'), 
        'perfmatters_print_input', 
        'perfmatters_options', 
        'perfmatters_woocommerce', 
        array(
            'id' => 'disable_woocommerce_scripts',
            'tooltip' => __('Disables WooCommerce scripts and styles except on product, cart, and checkout pages.', 'perfmatters')
        )
    );

    //disable woocommerce cart fragmentation
    add_settings_field(
        'disable_woocommerce_cart_fragmentation', 
        perfmatters_title(__('Disable Cart Fragmentation', 'perfmatters'), 'disable_woocommerce_cart_fragmentation', 'https://perfmatters.io/docs/disable-woocommerce-cart-fragments-ajax/'), 
        'perfmatters_print_input', 
        'perfmatters_options', 
        'perfmatters_woocommerce', 
        array(
            'id' => 'disable_woocommerce_cart_fragmentation',
            'tooltip' => __('Completely disables WooCommerce cart fragmentation script.', 'perfmatters')
        )
    );

    //disable woocommerce status meta box
    add_settings_field(
        'disable_woocommerce_status', 
        perfmatters_title(__('Disable Status Meta Box', 'perfmatters'), 'disable_woocommerce_status', 'https://perfmatters.io/docs/disable-woocommerce-status-meta-box/'), 
        'perfmatters_print_input', 
        'perfmatters_options', 
        'perfmatters_woocommerce', 
        array(
            'id' => 'disable_woocommerce_status',
            'tooltip' => __('Disables WooCommerce status meta box from the WP Admin Dashboard.', 'perfmatters')
        )
    );

    //disable woocommerce widgets
    add_settings_field(
        'disable_woocommerce_widgets', 
        perfmatters_title(__('Disable Widgets', 'perfmatters'), 'disable_woocommerce_widgets', 'https://perfmatters.io/docs/disable-woocommerce-widgets/'), 
        'perfmatters_print_input', 
        'perfmatters_options', 
        'perfmatters_woocommerce', 
        array(
            'id' => 'disable_woocommerce_widgets',
            'tooltip' => __('Disables all WooCommerce widgets.', 'perfmatters')
        )
    );

    register_setting('perfmatters_options', 'perfmatters_options', 'perfmatters_sanitize_options');

    //cdn option
    if(get_option('perfmatters_cdn') == false) {    
        add_option('perfmatters_cdn', apply_filters('perfmatters_default_cdn', perfmatters_default_cdn()));
    }

    //cdn section
    add_settings_section('perfmatters_cdn', 'CDN', 'perfmatters_cdn_callback', 'perfmatters_cdn');

    //enable cdn rewrite
    add_settings_field(
        'enable_cdn', 
        perfmatters_title(__('Enable CDN Rewrite', 'perfmatters'), 'enable_cdn', 'https://perfmatters.io/docs/cdn-rewrite/'), 
        'perfmatters_print_input', 
        'perfmatters_cdn', 
        'perfmatters_cdn', 
        array(
            'id' => 'enable_cdn',
            'option' => 'perfmatters_cdn',
            'tooltip' => __('Enables rewriting of your site URLs with your CDN URLs which can be configured below.', 'perfmatters')
        )
    );

    //cdn url
    add_settings_field(
        'cdn_url', 
        perfmatters_title(__('CDN URL', 'perfmatters'), 'cdn_url', 'https://perfmatters.io/docs/cdn-url/'), 
        'perfmatters_print_input', 
        'perfmatters_cdn', 
        'perfmatters_cdn', 
        array(
            'id' => 'cdn_url',
            'option' => 'perfmatters_cdn',
            'input' => 'text',
            'placeholder' => 'https://cdn.example.com',
            'tooltip' => __('Enter your CDN URL without the trailing backslash. Example: https://cdn.example.com', 'perfmatters')
        )
    );

    //cdn included directories
    add_settings_field(
        'cdn_directories', 
        perfmatters_title(__('Included Directories', 'perfmatters'), 'cdn_directories', 'https://perfmatters.io/docs/cdn-included-directories/'), 
        'perfmatters_print_input', 
        'perfmatters_cdn', 
        'perfmatters_cdn', 
        array(
            'id' => 'cdn_directories',
            'option' => 'perfmatters_cdn',
            'input' => 'text',
            'placeholder' => 'wp-content,wp-includes',
            'tooltip' => __('Enter any directories you would like to be included in CDN rewriting, separated by commas (,). Default: wp-content,wp-includes', 'perfmatters')
        )
    );

    //cdn exclusions
    add_settings_field(
        'cdn_exclusions', 
        perfmatters_title(__('CDN Exclusions', 'perfmatters'), 'cdn_exclusions', 'https://perfmatters.io/docs/cdn-exclusions/'), 
        'perfmatters_print_input', 
        'perfmatters_cdn', 
        'perfmatters_cdn', 
        array(
            'id' => 'cdn_exclusions',
            'option' => 'perfmatters_cdn',
            'input' => 'text',
            'placeholder' => '.php',
            'tooltip' => __('Enter any directories or file extensions you would like to be excluded from CDN rewriting, separated by commas (,). Default: .php', 'perfmatters')
        )
    );

    register_setting('perfmatters_cdn', 'perfmatters_cdn');

    //google analytics option
    if(get_option('perfmatters_ga') == false) {    
        add_option('perfmatters_ga', apply_filters('perfmatters_default_ga', perfmatters_default_ga()));
    }

    $perfmatters_ga = get_option('perfmatters_ga');

    //google analytics section
    add_settings_section('perfmatters_ga', __('Google Analytics', 'perfmatters'), 'perfmatters_ga_callback', 'perfmatters_ga');

    //enable local ga
    add_settings_field(
        'enable_local_ga', 
        perfmatters_title(__('Enable Local Analytics', 'perfmatters'), 'enable_local_ga', 'https://perfmatters.io/docs/local-analytics/'),
        'perfmatters_print_input', 
        'perfmatters_ga', 
        'perfmatters_ga', 
        array(
            'id' => 'enable_local_ga',
            'option' => 'perfmatters_ga',
            'tooltip' => __('Enable syncing of the Google Analytics script to your own server.', 'perfmatters')
        )
    );

    //google analytics id
    add_settings_field(
        'tracking_id', 
        perfmatters_title(__('Tracking ID', 'perfmatters'), 'tracking_id', 'https://perfmatters.io/docs/local-analytics/#trackingid'), 
        'perfmatters_print_input', 
        'perfmatters_ga', 
        'perfmatters_ga', 
        array(
            'id' => 'tracking_id',
            'option' => 'perfmatters_ga',
            'input' => 'text',
            'tooltip' => __('Input your Google Analytics tracking or measurement ID.', 'perfmatters')
        )
    );

    //tracking code position
    add_settings_field(
        'tracking_code_position', 
        perfmatters_title(__('Tracking Code Position', 'perfmatters'), 'tracking_code_position', 'https://perfmatters.io/docs/local-analytics/#trackingcodeposition'), 
        'perfmatters_print_input', 
        'perfmatters_ga', 
        'perfmatters_ga', 
        array(
            'id' => 'tracking_code_position',
            'option' => 'perfmatters_ga',
            'input' => 'select',
            'options' => array(
            	"" => __('Header', 'perfmatters') . ' (' . __('Default', 'perfmatters') . ')',
            	"footer" => __('Footer', 'perfmatters')
            	),
            'tooltip' => __('Load your analytics script in the header (default) or footer of your site. Default: Header', 'perfmatters')
        )
    );

    //script type
    add_settings_field(
        'script_type', 
        perfmatters_title(__('Script Type', 'perfmatters'), 'tracking_code_position', 'https://perfmatters.io/docs/local-analytics/#script-type'), 
        'perfmatters_print_input', 
        'perfmatters_ga', 
        'perfmatters_ga', 
        array(
            'id' => 'script_type',
            'option' => 'perfmatters_ga',
            'input' => 'select',
            'options' => array(
            	'' => 'analytics.js' . ' (' . __('Default', 'perfmatters') . ')',
                'gtagv4' => 'gtag.js v4',
                'gtag' => 'gtag.js',
            	'minimal' => __('Minimal', 'perfmatters'),
            	'minimal_inline' => __('Minimal Inline', 'perfmatters')
            ),
            'class' => 'perfmatters-input-controller',
            'tooltip' => __('Choose which script method you would like to use.', 'perfmatters')
        )
    );

    //disable display features
    add_settings_field(
        'disable_display_features', 
        perfmatters_title(__('Disable Display Features', 'perfmatters'), 'disable_display_features', 'https://perfmatters.io/docs/local-analytics/#disabledisplayfeatures'), 
        'perfmatters_print_input', 
        'perfmatters_ga', 
        'perfmatters_ga', 
        array(
            'id' => 'disable_display_features',
            'option' => 'perfmatters_ga',
            'class' => 'script_type perfmatters-select-control-' . (!empty($perfmatters_ga['script_type']) ? ' hidden' : ''),
            'tooltip' => __('Disable remarketing and advertising which generates a 2nd HTTP request.', 'perfmatters')
        )
    );

    //anonymize ip
    add_settings_field(
        'anonymize_ip', 
        perfmatters_title(__('Anonymize IP', 'perfmatters'), 'anonymize_ip', 'https://perfmatters.io/docs/local-analytics/#anonymize-ip'), 
        'perfmatters_print_input', 
        'perfmatters_ga', 
        'perfmatters_ga', 
        array(
            'id' => 'anonymize_ip',
            'option' => 'perfmatters_ga',
            'class' => 'script_type perfmatters-select-control-gtagv4 perfmatters-control-reverse' . (!empty($perfmatters_ga['script_type']) && $perfmatters_ga['script_type'] == 'gtagv4' ? ' hidden' : ''),
            'tooltip' => __('Shorten visitor IP to comply with privacy restrictions in some countries.', 'perfmatters')
        )
    );

    //track logged in admins
    add_settings_field(
        'track_admins', 
        perfmatters_title(__('Track Logged In Admins', 'perfmatters'), 'track_admins', 'https://perfmatters.io/docs/local-analytics/#track-logged-in-admins'), 
        'perfmatters_print_input', 
        'perfmatters_ga', 
        'perfmatters_ga', 
        array(
            'id' => 'track_admins',
            'option' => 'perfmatters_ga',
            'tooltip' => __('Include logged-in WordPress admins in your Google Analytics reports.', 'perfmatters')
        )
    );

    //adjusted bounce rate
    add_settings_field(
        'adjusted_bounce_rate', 
        perfmatters_title(__('Adjusted Bounce Rate', 'perfmatters'), 'adjusted_bounce_rate', 'https://perfmatters.io/docs/local-analytics/#adjusted-bounce-rate'), 
        'perfmatters_print_input', 
        'perfmatters_ga', 
        'perfmatters_ga', 
        array(
            'id' => 'adjusted_bounce_rate',
            'option' => 'perfmatters_ga',
            'input' => 'text',
            'class' => 'script_type perfmatters-select-control-' . (!empty($perfmatters_ga['script_type']) ? ' hidden' : ''),
            'tooltip' => __('Set a timeout limit in seconds to better evaluate the quality of your traffic. (1-100)', 'perfmatters')
        )
    );

    //cdn url
    add_settings_field(
        'cdn_url', 
        perfmatters_title(__('CDN URL', 'perfmatters'), 'cdn_url', 'https://perfmatters.io/docs/local-analytics/#gtag-cdn'), 
        'perfmatters_print_input', 
        'perfmatters_ga', 
        'perfmatters_ga', 
        array(
            'id' => 'cdn_url',
            'option' => 'perfmatters_ga',
            'input' => 'text',
            'placeholder' => 'https://cdn.example.com',
            'class' => 'script_type perfmatters-select-control- perfmatters-select-control-gtag' . (!empty($perfmatters_ga['script_type']) && $perfmatters_ga['script_type'] != 'gtag' ? ' hidden' : ''),
            'tooltip' => __('Use your CDN URL when referencing analytics.js from inside gtag.js. Example: https://cdn.example.com', 'perfmatters')
        )
    );

    //use monsterinsights
    add_settings_field(
        'use_monster_insights', 
        perfmatters_title(__('Use MonsterInsights', 'perfmatters'), 'use_monster_insights', 'https://perfmatters.io/docs/local-analytics/#monster-insights'), 
        'perfmatters_print_input', 
        'perfmatters_ga', 
        'perfmatters_ga', 
        array(
            'id' => 'use_monster_insights',
            'option' => 'perfmatters_ga',
            'class' => 'script_type perfmatters-select-control- perfmatters-select-control-gtag' . (!empty($perfmatters_ga['script_type']) && $perfmatters_ga['script_type'] != 'gtag' ? ' hidden' : ''),
            'tooltip' => __('Allows MonsterInsights to manage your Google Analytics while still using the locally hosted gtag.js file generated by Perfmatters.', 'perfmatters')
        )
    );

    //enable amp support
    add_settings_field(
        'enable_amp', 
        perfmatters_title(__('Enable AMP Support', 'perfmatters'), 'enable_amp', 'https://perfmatters.io/docs/local-analytics/#amp'), 
        'perfmatters_print_input', 
        'perfmatters_ga', 
        'perfmatters_ga', 
        array(
            'id' => 'enable_amp',
            'option' => 'perfmatters_ga',
            'class' => 'script_type perfmatters-select-control-gtagv4 perfmatters-control-reverse' . (!empty($perfmatters_ga['script_type']) && $perfmatters_ga['script_type'] == 'gtagv4' ? ' hidden' : ''),
            'tooltip' => __('Enable support for analytics tracking on AMP sites. This is not a local script, but a native AMP script.', 'perfmatters')
        )
    );

    //google analytics section
    register_setting('perfmatters_ga', 'perfmatters_ga');

    if(get_option('perfmatters_extras') == false) {    
        add_option('perfmatters_extras', apply_filters('perfmatters_default_extras', perfmatters_default_extras()));
    }
    add_settings_section('general', __('General', 'perfmatters'), 'perfmatters_extras_general_callback', 'perfmatters_extras');

    //blank favicon
    add_settings_field(
        'blank_favicon', 
        perfmatters_title(__('Add Blank Favicon', 'perfmatters'), 'blank_favicon', 'https://perfmatters.io/docs/blank-favicon/'), 
        'perfmatters_print_input', 
        'perfmatters_extras', 
        'general', 
        array(
            'id' => 'blank_favicon',
            'option' => 'perfmatters_extras',
            'tooltip' => __('Adds a blank favicon to your WordPress header which will prevent a Missing Favicon or 404 error from showing up on certain website speed testing tools.', 'perfmatters')
        )
    );

    //header code
    add_settings_field(
        'header_code', 
        perfmatters_title(__('Add Header Code', 'perfmatters'), 'header_code', 'https://perfmatters.io/docs/wordpress-add-code-to-header-footer/'), 
        'perfmatters_print_input', 
        'perfmatters_extras', 
        'general', 
        array(
            'id' => 'header_code',
            'option' => 'perfmatters_extras',
            'input' => 'textarea',
            'tooltip' => __('Code added here will be printed in the head section on every page of your website.', 'perfmatters')
        )
    );

    //body code
    if(function_exists('wp_body_open') && version_compare(get_bloginfo('version'), '5.2' , '>=')) {

        add_settings_field(
            'body_code', 
            perfmatters_title(__('Add Body Code', 'perfmatters'), 'body_code', 'https://perfmatters.io/docs/wordpress-add-code-to-header-footer/'), 
            'perfmatters_print_input', 
            'perfmatters_extras', 
            'general', 
            array(
                'id' => 'body_code',
                'option' => 'perfmatters_extras',
                'input' => 'textarea',
                'tooltip' => __('Code added here will be printed below the opening body tag on every page of your website.', 'perfmatters')
            )
        );
    }

    //footer code
    add_settings_field(
        'footer_code', 
        perfmatters_title(__('Add Footer Code', 'perfmatters'), 'footer_code', 'https://perfmatters.io/docs/wordpress-add-code-to-header-footer/'), 
        'perfmatters_print_input', 
        'perfmatters_extras', 
        'general', 
        array(
            'id' => 'footer_code',
            'option' => 'perfmatters_extras',
            'input' => 'textarea',
            'tooltip' => __('Code added here will be printed above the closing body tag on every page of your website.', 'perfmatters')
        )
    );

    //assets section
    add_settings_section('assets', __('Assets', 'perfmatters'), 'perfmatters_extras_assets_callback', 'perfmatters_extras');

    //script manager
    add_settings_field(
        'script_manager', 
        perfmatters_title(__('Script Manager', 'perfmatters'), 'script_manager', 'https://perfmatters.io/docs/disable-scripts-per-post-page/'), 
        'perfmatters_print_input', 
        'perfmatters_extras', 
        'assets', 
        array(
            'id' => 'script_manager',
            'option' => 'perfmatters_extras',
            'tooltip' => __('Enables the Perfmatters Script Manager, which gives you the ability to disable CSS and JS files on a page by page basis.', 'perfmatters')
        )
    );

    //assets section
    add_settings_section('assets_js', __('JavaScript', 'perfmatters'), 'perfmatters_extras_assets_js_callback', 'perfmatters_extras');

    //defer js
    add_settings_field(
        'defer_js', 
        perfmatters_title(__('Defer Javascript', 'perfmatters'), 'defer_js', 'https://perfmatters.io/docs/defer-javascript-wordpress/#defer'), 
        'perfmatters_print_input', 
        'perfmatters_extras', 
        'assets_js', 
        array(
            'id' => 'defer_js',
            'option' => 'perfmatters_extras',
            'tooltip' => __('Add the defer attribute to your JavaScript files.', 'perfmatters')
        )
    );

    //defer jquery
    add_settings_field(
        'defer_jquery', 
        perfmatters_title(__('Include jQuery', 'perfmatters'), 'defer_jquery', 'https://perfmatters.io/docs/defer-javascript-wordpress/#include-jquery'), 
        'perfmatters_print_input', 
        'perfmatters_extras', 
        'assets_js', 
        array(
            'id' => 'defer_jquery',
            'option' => 'perfmatters_extras',
            'confirmation' => __('Many plugins and themes require jQuery. We recommend either testing jQuery deferral separately or leaving this option turned off.', 'perfmatters'),
            'tooltip' => __('Allow jQuery core to be deferred. We recommend testing this separately or leaving it off.', 'perfmatters')
        )
    );

    //js exlusions
    add_settings_field(
        'js_exclusions', 
        perfmatters_title(__('Exclude from Deferral', 'perfmatters'), 'js_exclusions', 'https://perfmatters.io/docs/defer-javascript-wordpress/#exclude'), 
        'perfmatters_print_input', 
        'perfmatters_extras', 
        'assets_js', 
        array(
            'id' => 'js_exclusions',
            'option' => 'perfmatters_extras',
            'input' => 'textarea',
            'textareatype' => 'oneperline',
            'placeholder' => 'example.js',
            'tooltip' => __('Exclude specific JavaScript files from deferral. Exclude a file by adding the source URL (example.js). Format: one per line', 'perfmatters')
        )
    );

    //delay js
    add_settings_field(
        'delay_js', 
        perfmatters_title(__('Delay Javascript', 'perfmatters'), 'delay_js', 'https://perfmatters.io/docs/delay-javascript/'), 
        'perfmatters_print_input', 
        'perfmatters_extras', 
        'assets_js', 
        array(
            'id' => 'delay_js',
            'option' => 'perfmatters_extras',
            'input' => 'textarea',
            'textareatype' => 'oneperline',
            'placeholder' => 'example.js',
            'tooltip' => __('Delay JavaScript from loading until user interaction. Format: one per line', 'perfmatters')
        )
    );

    //delay timeout
    add_settings_field(
        'delay_timeout', 
        perfmatters_title(__('Delay Timeout', 'perfmatters'), 'delay_timeout', 'https://perfmatters.io/docs/delay-javascript/#timeout'), 
        'perfmatters_print_input', 
        'perfmatters_extras', 
        'assets_js', 
        array(
            'id' => 'delay_timeout',
            'option' => 'perfmatters_extras',
            'input' => 'select',
            'options' => array(
                "" => __('None', 'perfmatters'),
                "1" => '1 ' . __('second', 'perfmatters'),
                "2" => '2 ' . __('seconds', 'perfmatters'),
                "3" => '3 ' . __('seconds', 'perfmatters'),
                "4" => '4 ' . __('seconds', 'perfmatters'),
                "5" => '5 ' . __('seconds', 'perfmatters'),
                "6" => '6 ' . __('seconds', 'perfmatters'),
                "7" => '7 ' . __('seconds', 'perfmatters'),
                "8" => '8 ' . __('seconds', 'perfmatters'),
                "9" => '9 ' . __('seconds', 'perfmatters'),
                "10" => '10 ' . __('seconds', 'perfmatters')
                ),
            'tooltip' => __('Load delayed scripts after a set amount of time if no user interaction has been detected.', 'perfmatters')
        )
    );

    //preloading section
    add_settings_section('preloading', __('Preloading', 'perfmatters'), 'perfmatters_extras_preloading_callback', 'perfmatters_extras');

    //enable instant page
    add_settings_field(
        'instant_page', 
        perfmatters_title(__('Enable Instant Page', 'perfmatters'), 'instant_page', 'https://perfmatters.io/docs/link-prefetch/'), 
        'perfmatters_print_input', 
        'perfmatters_extras', 
        'preloading', 
        array(
            'id' => 'instant_page',
            'option' => 'perfmatters_extras',
            'tooltip' => __('Automatically prefetch URLs in the background after a user hovers over a link. This results in almost instantaneous load times and improves the user experience.', 'perfmatters')
        )
    );

    //preload
    add_settings_field(
        'preload', 
        perfmatters_title(__('Preload', 'perfmatters'), 'preload', 'https://perfmatters.io/docs/preload/'), 
        'perfmatters_print_preload', 
        'perfmatters_extras', 
        'preloading', 
        array(
            'id' => 'preload',
            'option' => 'perfmatters_extras',
            'tooltip' => __('Preload allows you to specify resources (such as fonts or CSS) needed right away during a page load. This helps fix render-blocking resource warnings. Format: https://example.com/font.woff2', 'perfmatters')
        )
    );

    //preconnect
    add_settings_field(
        'preconnect', 
        perfmatters_title(__('Preconnect', 'perfmatters'), 'preconnect', 'https://perfmatters.io/docs/preconnect/'), 
        'perfmatters_print_preconnect', 
        'perfmatters_extras', 
        'preloading', 
        array(
            'id' => 'preconnect',
            'option' => 'perfmatters_extras',
            'tooltip' => __('Preconnect allows the browser to set up early connections before an HTTP request, eliminating roundtrip latency and saving time for users. Format: https://example.com', 'perfmatters')
        )
    );

    //dns prefetch
    add_settings_field(
        'dns_prefetch', 
        perfmatters_title(__('DNS Prefetch', 'perfmatters'), 'dns_prefetch', 'https://perfmatters.io/docs/dns-prefetching/'), 
        'perfmatters_print_input', 
        'perfmatters_extras', 
        'preloading', 
        array(
            'id' => 'dns_prefetch',
            'option' => 'perfmatters_extras',
            'input' => 'textarea',
            'textareatype' => 'oneperline',
            'placeholder' => '//example.com',
            'tooltip' => __('Resolve domain names before a user clicks. Format: //example.com (one per line)', 'perfmatters')
        )
    );

    //tools section
    add_settings_section('tools', __('Tools', 'perfmatters'), 'perfmatters_extras_tools_callback', 'perfmatters_extras');

    if(!is_multisite()) {

        //clean uninstall
        add_settings_field(
            'clean_uninstall', 
            perfmatters_title(__('Clean Uninstall', 'perfmatters'), 'clean_uninstall', 'https://perfmatters.io/docs/clean-uninstall/'), 
            'perfmatters_print_input', 
            'perfmatters_extras', 
            'tools', 
            array(
                'id' => 'clean_uninstall',
                'option' => 'perfmatters_extras',
                'tooltip' => __('When enabled, this will cause all Perfmatters options data to be removed from your database when the plugin is uninstalled.', 'perfmatters')
            )
        );

    }

    //accessibility mode
    add_settings_field(
        'accessibility_mode', 
        perfmatters_title(__('Accessibility Mode', 'perfmatters'), 'accessibility_mode', 'https://perfmatters.io/docs/accessibility-mode/'), 
        'perfmatters_print_input',
        'perfmatters_extras', 
        'tools', 
        array(
        	'id' => 'accessibility_mode',
        	'input' => 'checkbox',
        	'option' => 'perfmatters_extras',
        	'tooltip' => __('Disable the use of visual UI elements in the plugin settings such as checkbox toggles and hovering tooltips.', 'perfmatters')
        )
    );

    //purge meta options
    add_settings_field(
        'purge_meta', 
        perfmatters_title(__('Purge Meta Options', 'perfmatters'), false, 'https://perfmatters.io/docs/purge-meta-options/'), 
        'perfmatters_print_purge_meta', 
        'perfmatters_extras', 
        'tools', 
        array(
            'id'           => 'purge_meta',
            'input'        => 'button',
            'option'       => 'perfmatters_extras',
            'title'        => __('Purge Meta Options', 'perfmatters'),
            'confirmation' => __('Are you sure? This will delete all existing Perfmatters meta options for all posts from the database.', 'perfmatters'),
            'tooltip'      => __('Permanently delete all existing Perfmatters meta options from your database.', 'perfmatters')
        )
    );

    //export settings
    add_settings_field(
        'export_settings', 
        perfmatters_title(__('Export Settings', 'perfmatters'), 'export_settings', 'https://perfmatters.io/docs/import-export/'), 
        'perfmatters_print_input',
        'perfmatters_extras', 
        'tools', 
        array(
            'id' => 'export_settings',
            'input' => 'button',
            'title' => __('Export Plugin Settings', 'perfmatters'),
            'option' => 'perfmatters_extras',
            'tooltip' => __('Export your Perfmatters settings for this site as a .json file. This lets you easily import the configuration into another site.', 'perfmatters')
        )
    );

    //import settings
    add_settings_field(
        'import_settings', 
        perfmatters_title(__('Import Settings', 'perfmatters'), 'import_settings', 'https://perfmatters.io/docs/import-export/'), 
        'perfmatters_print_import_settings',
        'perfmatters_extras', 
        'tools', 
        array(
            'tooltip' => __('Import Perfmatters settings from an exported .json file.', 'perfmatters')
        )
    );

    //database section
    add_settings_section('database', __('Database', 'perfmatters'), 'perfmatters_extras_database_callback', 'perfmatters_extras');

    //post revisions
    add_settings_field(
        'post_revisions', 
        perfmatters_title(__('Post Revisions', 'perfmatters'), 'post_revisions', 'https://perfmatters.io/docs/wordpress-post-revisions/'), 
        'perfmatters_print_input', 
        'perfmatters_extras', 
        'database', 
        array(
            'id' => 'post_revisions',
            'option' => 'perfmatters_extras',
            'tooltip' => __('Include post revisions in your database optimization. This also includes revisions for pages and custom post types.', 'perfmatters')
        )
    );

    //post auto-drafts
    add_settings_field(
        'post_auto_drafts', 
        perfmatters_title(__('Post Auto-Drafts', 'perfmatters'), 'post_auto_drafts', 'https://perfmatters.io/docs/wordpress-auto-drafts/'), 
        'perfmatters_print_input', 
        'perfmatters_extras', 
        'database', 
        array(
            'id' => 'post_auto_drafts',
            'option' => 'perfmatters_extras',
            'tooltip' => __('Include post auto-drafts in your database optimization. This also includes auto-drafts for pages and custom post types.', 'perfmatters'),
        )
    );

    //trashed posts
    add_settings_field(
        'trashed_posts', 
        perfmatters_title(__('Trashed Posts', 'perfmatters'), 'trashed_posts', 'https://perfmatters.io/docs/wordpress-trash/#posts'), 
        'perfmatters_print_input', 
        'perfmatters_extras', 
        'database', 
        array(
            'id' => 'trashed_posts',
            'option' => 'perfmatters_extras',
            'tooltip' => __('Include trashed posts in your database optimization. This also includes trashed pages and custom post types.', 'perfmatters')
        )
    );

    //spam comments
    add_settings_field(
        'spam_comments', 
        perfmatters_title(__('Spam Comments', 'perfmatters'), 'spam_comments', 'https://perfmatters.io/docs/wordpress-spam-comments/'), 
        'perfmatters_print_input', 
        'perfmatters_extras', 
        'database', 
        array(
            'id' => 'spam_comments',
            'option' => 'perfmatters_extras',
            'tooltip' => __('Include spam comments in your database optimization.', 'perfmatters')
        )
    );

    //trashed comments
    add_settings_field(
        'trashed_comments', 
        perfmatters_title(__('Trashed Comments', 'perfmatters'), 'trashed_comments', 'https://perfmatters.io/docs/wordpress-trash/#comments'), 
        'perfmatters_print_input', 
        'perfmatters_extras', 
        'database', 
        array(
            'id' => 'trashed_comments',
            'option' => 'perfmatters_extras',
            'tooltip' => __('Include trashed comments in your database optimization.', 'perfmatters')
        )
    );

    //expired transients
    add_settings_field(
        'expired_transients', 
        perfmatters_title(__('Expired Transients', 'perfmatters'), 'expired_transients', 'https://perfmatters.io/docs/wordpress-transients/#expired'), 
        'perfmatters_print_input', 
        'perfmatters_extras', 
        'database', 
        array(
            'id' => 'expired_transients',
            'option' => 'perfmatters_extras',
            'tooltip' => __('Include expired transients in your database optimization.', 'perfmatters')
        )
    );

    //all transients
    add_settings_field(
        'all_transients', 
        perfmatters_title(__('All Transients', 'perfmatters'), 'all_transients', 'https://perfmatters.io/docs/wordpress-transients/#all'), 
        'perfmatters_print_input', 
        'perfmatters_extras', 
        'database', 
        array(
            'id' => 'all_transients',
            'option' => 'perfmatters_extras',
            'tooltip' => __('Include all transients in your database optimization.', 'perfmatters')
        )
    );

    //tables
    add_settings_field(
        'tables', 
        perfmatters_title(__('Tables', 'perfmatters'), 'tables', 'https://perfmatters.io/docs/wordpress-database-tables/'), 
        'perfmatters_print_input', 
        'perfmatters_extras', 
        'database', 
        array(
            'id' => 'tables',
            'option' => 'perfmatters_extras',
            'tooltip' => __('Include tables in your database optimization.', 'perfmatters')
        )
    );

    //optimize database
    add_settings_field(
        'optimize_database', 
        perfmatters_title(__('Optimize Database', 'perfmatters'), 'optimize_database', 'https://perfmatters.io/docs/optimize-wordpress-database/'), 
        'perfmatters_print_input',
        'perfmatters_extras', 
        'database', 
        array(
            'id' => 'optimize_database',
            'input' => 'button',
            'title' => __('Optimize Now', 'perfmatters'),
            'option' => 'perfmatters_extras',
            'tooltip' => __('Run a one-time optimization of your WordPress database based on the selected options above. This process runs in the background.', 'perfmatters')
        )
    );

    //Scheduled Optimization
    add_settings_field(
        'optimize_schedule', 
        perfmatters_title(__('Scheduled Optimization', 'perfmatters'), 'optimize_schedule', 'https://perfmatters.io/docs/optimize-wordpress-database/#schedule'), 
        'perfmatters_print_input', 
        'perfmatters_extras', 
        'database', 
        array(
            'id' => 'optimize_schedule',
            'option' => 'perfmatters_extras',
            'input' => 'select',
            'options' => array(
                "" => __('Disabled', 'perfmatters'),
                "daily" => __('Daily', 'perfmatters'),
                "weekly" => __('Weekly', 'perfmatters'),
                "monthly" => __('Monthly', 'perfmatters')
                ),
            'tooltip' => __('Schedule a routine optimization of your WordPress database based on the selected options above. This process runs in the background and starts immediately after saving.', 'perfmatters')
        )
    );

    register_setting('perfmatters_extras', 'perfmatters_extras', 'perfmatters_sanitize_extras');

    //edd license option
	register_setting('perfmatters_edd_license', 'perfmatters_edd_license_key', 'perfmatters_edd_sanitize_license');
}
add_action('admin_init', 'perfmatters_settings');

//options default values
function perfmatters_default_options() {
	$defaults = array(
		'disable_emojis' => "0",
		'disable_embeds' => "0",
		'disable_xmlrpc' => "0",
		'remove_jquery_migrate' => "0",
		'hide_wp_version' => "0",
		'remove_wlwmanifest_link' => "0",
		'remove_rsd_link' => "0",
		'remove_shortlink' => "0",
		'disable_rss_feeds' => "0",
		'remove_feed_links' => "0",
		'disable_self_pingbacks' => "0",
		'disable_rest_api' => "",
		'remove_rest_api_links' => "0",
        'disable_dashicons' => "0",
        'disable_google_maps' => "0",
        'disable_password_strength_meter' => "0",
        'disable_comments' => "0",
        'remove_comment_urls' => "0",
        'lazy_loading' => "",
        'lazy_loading_native' => "",
        'lazy_loading_dom_monitoring' => "",
		'disable_heartbeat' => "",
		'heartbeat_frequency' => "",
		'limit_post_revisions' => "",
		'autosave_interval' => "",
        'login_url' => "",
        'disable_woocommerce_scripts' => "0",
        'disable_woocommerce_cart_fragmentation' => "0",
        'disable_woocommerce_status' => "0",
        'disable_woocommerce_widgets' => "0"
	);
    perfmatters_network_defaults($defaults, 'perfmatters_options');
	return apply_filters('perfmatters_default_options', $defaults);
}

//cdn default values
function perfmatters_default_cdn() {
    $defaults = array(
        'cdn_directories' => "wp-content,wp-includes",
        'cdn_exclusions' => ".php"
    );
    perfmatters_network_defaults($defaults, 'perfmatters_cdn');
    return apply_filters( 'perfmatters_default_cdn', $defaults );
}

//google analytics default values
function perfmatters_default_ga() {
    $defaults = array(
    	'enable_local_ga' => "0",
        'tracking_id' => "",
        'tracking_code_position' => "",
        'disable_display_features' => "0",
        'anonymize_ip' => "0",
        'track_admins' => "0",
        'adjusted_bounce_rate' => "",
        'use_monster_insights' => "0"
    );
    perfmatters_network_defaults($defaults, 'perfmatters_ga');
    return apply_filters('perfmatters_default_ga', $defaults);
}

//extras default values
function perfmatters_default_extras() {
    $defaults = array(
        'script_manager' => "0",
        'dns_prefetch' => "",
        'preconnect' => "",
        'blank_favicon' => "0",
        'header_code' => "",
        'footer_code' => "",
        'post_revisions' => "0",
        'post_auto_drafts' => "0",
        'trashed_posts' => "0",
        'spam_comments' => "0",
        'trashed_comments' => "0",
        'expired_transients' => "0",
        'all_transients' => "0",
        'tables' => "0",
        'optimize_schedule' => "",
        'accessibility_mode' => "0"
    );
    perfmatters_network_defaults($defaults, 'perfmatters_extras');
    return apply_filters( 'perfmatters_default_extras', $defaults );
}

//network defaults
function perfmatters_network_defaults(&$defaults, $option) {
    if(is_multisite() && is_plugin_active_for_network('perfmatters/perfmatters.php')) {
        $perfmatters_network = get_site_option('perfmatters_network');
        if(!empty($perfmatters_network['default'])) {
            $networkDefaultOptions = get_blog_option($perfmatters_network['default'], $option);
            if($option == 'perfmatters_cdn') {
                unset($networkDefaultOptions['cdn_url']);
            }
            if(!empty($networkDefaultOptions)) {
                foreach($networkDefaultOptions as $key => $val) {
                    $defaults[$key] = $val;
                }
            }
        }
    }
}

//main options group callback
function perfmatters_options_callback() {
	echo '<p class="perfmatters-subheading">' . __('Select which performance options you would like to enable.', 'perfmatters') . '</p>';
}

//woocommerce options group callback
function perfmatters_woocommerce_callback() {
    echo '<p class="perfmatters-subheading">' . __('Disable specific elements of WooCommerce.', 'perfmatters') . '</p>';
}

//lazy loading options group callback
function perfmatters_lazy_loading_callback() {
    echo '<p class="perfmatters-subheading">' . __('Lazy Load images across your site.', 'perfmatters') . '</p>';
}

//cdn group callback
function perfmatters_cdn_callback() {
    echo '<p class="perfmatters-subheading">' . __('CDN options that allow you to rewrite your site URLs with your CDN URLs.', 'perfmatters') . '</p>';
}

//google analytics group callback
function perfmatters_ga_callback() {
    echo '<p class="perfmatters-subheading">' . __('Optimization options for Google Analytics.', 'perfmatters') . '</p>';
}

//extras general callback
function perfmatters_extras_general_callback() {
    echo '<p class="perfmatters-subheading">' . __('Extra options that pertain to Perfmatters plugin functionality.', 'perfmatters') . '</p>';
}

//extras assets group callback
function perfmatters_extras_assets_callback() {
    echo '<p class="perfmatters-subheading">' . __("Manage the assets loading on your site.", 'perfmatters') . '</p>';
}

//extras assets js group callback
function perfmatters_extras_assets_js_callback() {
    echo '<p class="perfmatters-subheading">' . __("Manage JavaScript loading on your site.", 'perfmatters') . '</p>';
}

//extras preloading group callback
function perfmatters_extras_preloading_callback() {
    echo '<p class="perfmatters-subheading">' . __("Preload resources you'll need later in advance.", 'perfmatters') . '</p>';
}

//extras tools callback
function perfmatters_extras_tools_callback() {
    echo '<p class="perfmatters-subheading">' . __('Perfmatters plugin management tools.', 'perfmatters') . '</p>';
}

//extras database callback
function perfmatters_extras_database_callback() {
    echo '<p class="perfmatters-subheading">' . __('Optimize and clean up your WordPress database.', 'perfmatters') . '</p>';
    echo '<p class="perfmatters-warning"><span class="dashicons dashicons-warning"></span> ' . __('These functions make permanent changes that cannot be reverted! Back up your database before proceeding.', 'perfmatters') . '</p>';
}

//print settings section
function perfmatters_settings_section($page, $section) {
    global $wp_settings_sections;
    if(!empty($wp_settings_sections[$page][$section])) {
        echo '<h2>' . __($wp_settings_sections[$page][$section]['title'], 'perfmatters') . '</h2>';
        echo $wp_settings_sections[$page][$section]['callback']();

        echo "<table class='form-table'>";
            echo "<tbody>";
                do_settings_fields($page, $section);
            echo "</tbody>";
        echo "</table>";
    }
}

//print form inputs
function perfmatters_print_input($args) {

    if(!empty($args['option'])) {
        $option = $args['option'];
        if($args['option'] == 'perfmatters_network') {
            $options = get_site_option($args['option']);
        }
        else {
            $options = get_option($args['option']);
        }
    }
    else {
        $option = 'perfmatters_options';
        $options = get_option('perfmatters_options');
    }
    if(!empty($args['option']) && $args['option'] == 'perfmatters_extras') {
        $extras = $options;
    }
    else {
        $extras = get_option('perfmatters_extras');
    }


    //text
    if(!empty($args['input']) && ($args['input'] == 'text' || $args['input'] == 'color')) {
        echo "<input type='text' id='" . $args['id'] . "' name='" . $option . "[" . $args['id'] . "]' value='" . (!empty($options[$args['id']]) ? $options[$args['id']] : '') . "' placeholder='" . (!empty($args['placeholder']) ? $args['placeholder'] : '') . "' />";
    }

    //select
    elseif(!empty($args['input']) && $args['input'] == 'select') {
        echo "<select id='" . $args['id'] . "' name='" . $option . "[" . $args['id'] . "]'>";
            foreach($args['options'] as $value => $title) {
                echo "<option value='" . $value . "' "; 
                if(!empty($options[$args['id']]) && $options[$args['id']] == $value) {
                    echo "selected";
                } 
                echo ">" . $title . "</option>";
            }
        echo "</select>";
    }

    //button
    elseif(!empty($args['input']) && $args['input'] == 'button') {
        echo "<button id='" . $args['id'] . "' name='" . $option . "[" . $args['id'] . "]' value='1' class='button button-secondary'";
            if(!empty($args['confirmation'])) {
                echo " onClick=\"return confirm('" . $args['confirmation'] . "');\"";
            }
        echo ">";
            echo $args['title'];
        echo "</button>";
    }

    //text area
    elseif(!empty($args['input']) && $args['input'] == 'textarea') {
        echo "<textarea id='" . $args['id'] . "' name='" . $option . "[" . $args['id'] . "]' placeholder='" . (!empty($args['placeholder']) ? $args['placeholder'] : '') . "'>";
            if(!empty($options[$args['id']])) {
                if(!empty($args['textareatype'])) {
                    if($args['textareatype'] == 'oneperline') {
                         foreach($options[$args['id']] as $line) {
                            echo $line . "\n";
                        }
                    }
                }
                else {
                    echo $options[$args['id']];
                }
            }
        echo "</textarea>";
    }

    //checkbox + toggle
    else {
        if((empty($extras['accessibility_mode']) || $extras['accessibility_mode'] != "1") && (empty($args['input']) || $args['input'] != 'checkbox')) {
            echo "<label for='" . $args['id'] . "' class='switch'>";
        }
            echo "<input type='checkbox' id='" . $args['id'] . "' name='" . $option . "[" . $args['id'] . "]' value='1' style='display: inline-block; margin: 0px;' ";
            if(!empty($options[$args['id']]) && $options[$args['id']] == "1") {
                echo "checked";
            }
            if(!empty($args['confirmation'])) {
                echo " onChange=\"this.checked=this.checked?confirm('" . $args['confirmation'] . "'):false;\"";
            }
            echo ">";
        if((empty($extras['accessibility_mode']) || $extras['accessibility_mode'] != "1") && (empty($args['input']) || $args['input'] != 'checkbox')) {
               echo "<div class='slider'></div>";
           echo "</label>";
        }
    }

    //print option data
    perfmatters_print_option_data($option, $args['id']);

    //tooltip
	if(!empty($args['tooltip'])) {
		perfmatters_tooltip($args['tooltip']);
	}
}

//print preload
function perfmatters_print_preload($args) {
    $extras = get_option('perfmatters_extras');
 
    echo "<div class='perfmatters-input-row-wrapper'>";
        echo "<div class='perfmatters-input-row-container'>";

            $rowCount = 0;

            if(!empty($extras['preload'])) {

                foreach($extras['preload'] as $line) {

                    perfmatters_print_preload_row($rowCount, $line);

                    $rowCount++;
                }
            }
            else {

                //print empty row at the end
                perfmatters_print_preload_row($rowCount, '');
            }

        echo "</div>";

        //add new row
        echo "<a href='#' class='perfmatters-add-input-row' rel='" . ($rowCount > 0 ? $rowCount - 1 : 0) . "'>" . __('Add New', 'perfmatters') . "</a>";

    echo "</div>";

    //tooltip
    if(!empty($args['tooltip'])) {
        perfmatters_tooltip($args['tooltip']);
    }
}

function perfmatters_print_preload_row($rowCount = 0, $line = array()) {
    echo "<div class='perfmatters-input-row'>";

        echo "<div style='display: flex; width: 100%; align-items: center; margin-bottom: 5px;'>";
            echo "<input type='text' id='preload-" . $rowCount . "-url' name='perfmatters_extras[preload][" . $rowCount . "][url]' value='" . (isset($line['url']) ? $line['url'] : "") . "' placeholder='https://example.com/font.woff2' style='' />";
            echo "<a href='#' class='perfmatters-delete-input-row' title='" . __('Remove', 'perfmatters') . "'><span class='dashicons dashicons-no'></span></a>";
        echo "</div>";

        $types = array(
            'audio'    => 'Audio',
            'document' => 'Document',
            'embed'    => 'Embed',
            'fetch'    => 'Fetch',
            'font'     => 'Font',
            'image'    => 'Image',
            'object'   => 'Object',
            'script'   => 'Script',
            'style'    => 'Style',
            'track'    => 'Track',
            'worker'   => 'Worker',
            'video'    => 'Video'
        );

        echo "<select id='preload-" . $rowCount . "-as' name='perfmatters_extras[preload][" . $rowCount . "][as]' style=''>";
            echo "<option value=''>" . __('Select Type', 'perfmatters') . "</option>";
            foreach($types as $value => $label) {
                echo "<option value='" . $value . "'" . (isset($line['as']) && $line['as'] == $value ? " selected='selected'" : "") . ">" . $label . "</option>";
            }
        echo "</select>";

        echo "<select id='preload-" . $rowCount . "-device' name='perfmatters_extras[preload][" . $rowCount . "][device]' style='margin-left: 5px;'>";
            echo "<option value=''>" . __('All Devices', 'perfmatters') . "</option>";
            echo "<option value='desktop'" . (isset($line['device']) && $line['device'] == 'desktop' ? " selected='selected'" : "") . ">" . __('Desktop', 'perfmatters') . "</option>";
            echo "<option value='mobile'" . (isset($line['device']) && $line['device'] == 'mobile' ? " selected='selected'" : "") . ">" . __('Mobile', 'perfmatters') . "</option>";
        echo "</select>";

        echo "<label class='perfmatters-inline-label-input' style='flex-grow: 1;'><span>" . __('Location', 'perfmatters') . "</span>";
            echo "<input type='text' id='preload-" . $rowCount . "-locations' name='perfmatters_extras[preload][" . $rowCount . "][locations]' value='" . (isset($line['locations']) ? $line['locations'] : "") . "' placeholder='23,19,blog' style='min-width: auto; padding-left: 74px;' />";
        echo "</label>";

        echo "<label for='preload-" . $rowCount . "-crossorigin'>";
            echo "<input type='checkbox' id='preload-" . $rowCount . "-crossorigin' name='perfmatters_extras[preload][" . $rowCount . "][crossorigin]' " . (!empty($line['crossorigin']) ? "checked" : "") . " value='1' /> CrossOrigin";
        echo "</label>";
    echo "</div>";
}

//print preconnect
function perfmatters_print_preconnect($args) {
    $extras = get_option('perfmatters_extras');
 
    echo "<div id='perfmatters-preconnect-wrapper' class='perfmatters-input-row-wrapper'>";
        echo "<div class='perfmatters-input-row-container'>";

            $rowCount = 0;

            if(!empty($extras['preconnect'])) {

                foreach($extras['preconnect'] as $line) {

                    //check for previous vs new format
                    if(is_array($line)) {
                        $url = $line['url'];
                        $crossorigin = isset($line['crossorigin']) ? $line['crossorigin'] : 0;
                    }
                    else {
                        $url = $line;
                        $crossorigin = 1;
                    }

                    //print row
                    perfmatters_print_preconnect_row($rowCount, $line);

                    $rowCount++;
                }
            }
            else {

                //print empty row at the end
                perfmatters_print_preconnect_row($rowCount, array('url' => ''));
            }

        echo "</div>";

        //add new row
        echo "<a href='#' id='perfmatters-add-preconnect' class='perfmatters-add-input-row' rel='" . ($rowCount > 0 ? $rowCount - 1 : 0) . "'>" . __('Add New', 'perfmatters') . "</a>";

    echo "</div>";

    //tooltip
    if(!empty($args['tooltip'])) {
    	perfmatters_tooltip($args['tooltip']);
    }
}

function perfmatters_print_preconnect_row($rowCount = 0, $line = '') {
    if(is_array($line)) {
        $url = $line['url'];
        $crossorigin = isset($line['crossorigin']) ? $line['crossorigin'] : 0;
    }
    else {
        $url = $line;
        $crossorigin = 1;
    }

    //print row
    echo "<div class='perfmatters-input-row'>";
        echo "<input type='text' id='preconnect-" . $rowCount . "-url' name='perfmatters_extras[preconnect][" . $rowCount . "][url]' value='" . $url . "' placeholder='https://example.com' />";
        echo "<label for='preconnect-" . $rowCount . "-crossorigin'>";
            echo "<input type='checkbox' id='preconnect-" . $rowCount . "-crossorigin' name='perfmatters_extras[preconnect][" . $rowCount . "][crossorigin]' " . ($crossorigin == 1 ? "checked" : "") . " value='1' /> CrossOrigin";
        echo "</label>";
        echo "<a href='#' class='perfmatters-delete-input-row' title='" . __('Remove', 'perfmatters') . "'><span class='dashicons dashicons-no'></span></a>";
    echo "</div>";
}

//print purge meta options
function perfmatters_print_purge_meta($args) {

    //input + button
    $meta_options = array('perfmatters_exclude_defer_js' => 'Defer JavaScript', 'perfmatters_exclude_lazy_loading' => 'Lazy Loading', 'perfmatters_exclude_instant_page' => 'Instant Page');
    echo "<div style='margin-bottom: 10px;'>";
        foreach($meta_options as $key => $name) {
            echo "<label for='perfmatters-purge-meta-" . $key . "' style='margin-right: 10px;'>";
                echo "<input type='checkbox' name='perfmatters_extras_temp[purge_meta_options][]' id='perfmatters-purge-meta-" . $key . "' value='" . $key . "' />";
                echo $name;
            echo "</label>";
        }
    echo "</div>";
    echo "<button id='import_settings' name='perfmatters_extras[purge_meta]' value='1' class='button button-secondary'";
        if(!empty($args['confirmation'])) {
            echo " onClick=\"return confirm('" . $args['confirmation'] . "');\"";
        }
    echo ">" . __("Purge Meta Options", 'perfmatters') . "</button>";

    //tooltip
    if(!empty($args['tooltip'])) {
        perfmatters_tooltip($args['tooltip']);
    }
}

//print import settings
function perfmatters_print_import_settings($args) {

	//input + button
    echo "<input type='file' name='perfmatters_import_settings_file' /><br />";
    echo "<button id='import_settings' name='perfmatters_extras[import_settings]' value='1' class='button button-secondary'>" . __("Import Plugin Settings", 'perfmatters') . "</button>";

    //tooltip
    if(!empty($args['tooltip'])) {
    	perfmatters_tooltip($args['tooltip']);
    }
}

//sanitize options
function perfmatters_sanitize_options($values) {

    //textarea inputs with one per line
    $one_per_line = array(
        'lazy_loading_exclusions'
    );

    perfmatters_sanitize_one_per_line($values, $one_per_line);

    return $values;
}

//sanitize extras
function perfmatters_sanitize_extras($values) {

    //textarea inputs with one per line
    $one_per_line = array(
        'js_exclusions',
        'delay_js',
        'dns_prefetch'
    );

    perfmatters_sanitize_one_per_line($values, $one_per_line);

	if(!empty($values['preload'])) {
        foreach($values['preload'] as $key => $line) {
            if(empty(trim($line['url']))) {
                unset($values['preload'][$key]);
            }
        }
        $values['preload'] = array_values($values['preload']);
    }
    if(!empty($values['preconnect'])) {
        foreach($values['preconnect'] as $key => $line) {
            if(empty(trim($line['url']))) {
                unset($values['preconnect'][$key]);
            }
        }
        $values['preconnect'] = array_values($values['preconnect']);
    }
    
    return $values;
}

//sanitize edd license
function perfmatters_edd_sanitize_license($new) {
	$old = get_option( 'perfmatters_edd_license_key' );
	if($old && $old != $new) {
		delete_option( 'perfmatters_edd_license_status' ); // new license has been entered, so must reactivate
	}
	return $new;
}

//sanitize one per line text field
function perfmatters_sanitize_one_per_line(&$values, $one_per_line) {
    foreach($one_per_line as $id) {
        if(!empty($values[$id]) && !is_array($values[$id])) {
            $text = trim($values[$id]);
            $text_array = explode("\n", $text);
            $text_array = array_filter(array_map('trim', $text_array));
            $values[$id] = $text_array;
        }
    }
}

//print tooltip
function perfmatters_tooltip($tooltip) {
    if(!empty($tooltip)) {
        $extras = get_option('perfmatters_extras');
        echo "<span class='perfmatters-tooltip-text" . (!empty($extras['accessibility_mode']) ? "-am" : "") . "'>" . $tooltip . "<span class='perfmatters-tooltip-subtext'>" . sprintf(__("Click %s to view documentation.", 'perfmatters'), "<span class='perfmatters-tooltip-icon'>?</span>") . "</span></span>";
    }
}

//print title
function perfmatters_title($title, $id = false, $link = false) {

    if(!empty($title)) {

        $var = "<span class='perfmatters-title-wrapper'>";

            //label + title
            if(!empty($id)) {
                $var.= "<label for='" . $id . "'>" . $title . "</label>";
            }
            else {
                $var.= $title;
            }

            //tooltip icon + link
            if(!empty($link)) {
                $extras = get_option('perfmatters_extras');
                 $var.= "<a" . (!empty($link) ? " href='" . $link . "'" : "") . " class='perfmatters-tooltip'" . (!empty($extras['accessibility_mode']) ? " title='" . __("View Documentation", 'perfmatters') . "'" : "") . " target='_blank'>?</a>";
            }

        $var.= "</span>";

        return $var;
    }
}

//calculate and print out data to display along with option input
function perfmatters_print_option_data($option, $id) {

    switch($option) {

        case 'perfmatters_extras' :

            global $wpdb;

            switch($id) {

                case 'post_revisions' :
                    $data = $wpdb->get_var("SELECT COUNT(ID) FROM $wpdb->posts WHERE post_type = 'revision'") . ' ' . __('Revisions Found', 'perfmatters');
                    break;

                case 'post_auto_drafts' :
                    $data = $wpdb->get_var("SELECT COUNT(ID) FROM $wpdb->posts WHERE post_status = 'auto-draft'") . ' ' . __('Auto-Drafts Found', 'perfmatters');
                    break;

                case 'trashed_posts' :
                    $data = $wpdb->get_var("SELECT COUNT(ID) FROM $wpdb->posts WHERE post_status = 'trash'") . ' ' . __('Trashed Posts Found', 'perfmatters');
                    break;

                case 'spam_comments':
                    $data = $wpdb->get_var("SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_approved = 'spam'") . ' ' . __('Spam Comments Found', 'perfmatters');
                        break;
            
                case 'trashed_comments':
                    $data = $wpdb->get_var("SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE (comment_approved = 'trash' OR comment_approved = 'post-trashed')") . ' ' . __('Trashed Comments Found', 'perfmatters');
                    break;

                case 'expired_transients':
                    $time = isset($_SERVER['REQUEST_TIME']) ? (int) $_SERVER['REQUEST_TIME'] : time();
                    $data = $wpdb->get_var($wpdb->prepare("SELECT COUNT(option_name) FROM $wpdb->options WHERE option_name LIKE %s AND option_value < %d", $wpdb->esc_like('_transient_timeout') . '%', $time)) . ' ' . __('Expired Transients Found', 'perfmatters');
                    break;

                case 'all_transients':
                    $data = $wpdb->get_var($wpdb->prepare("SELECT COUNT(option_id) FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like('_transient_') . '%', $wpdb->esc_like('_site_transient_') . '%')) . ' ' . __('Transients Found', 'perfmatters');
                    break;

                case 'tables':
                    $data = $wpdb->get_var("SELECT COUNT(table_name) FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "' and Engine <> 'InnoDB' and data_free > 0") . ' ' . __('Unoptimized Tables Found', 'perfmatters');
                    break;

                case 'optimize_schedule' : 
                    $data = "<span id='perfmatters-optimize-schedule-warning' style='display: none;'>" . __('Setting a new schedule will run the database optimization process immediately after saving your changes.', 'perfmatters') . "</span>";
                    break;

                default :
                    break;
            }

        default :
            break;
    }

    //print data
    if(!empty($data)) {
        echo "<span class='perfmatters-option-data' style='margin-left: 5px; font-size: 12px;'>" . $data . "</span>";
    }
}