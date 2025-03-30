<?php
return array(
    'plugins' => array(
    	'atarim' => array(
    		'id' => 'atarim-visual-collaboration/atarim-visual-collaboration.php',
    		'title' => 'Atarim',
    		'exclusions' => array(
    			'jquery.min.js',
    			'/atarim-client-interface/',
				'jQuery_WPF',
				'upgrade_url'
    		)
    	),
    	'borlabs' => array(
    		'id' => 'borlabs-cookie/borlabs-cookie.php',
    		'title' => 'Borlabs Cookie',
    		'exclusions' => array(
    			'/plugins/borlabs-cookie/',
				'borlabs-cookie',
				'BorlabsCookie',
				'jquery.min.js'
    		)
    	),
    	'complianz' => array(
    		'id' => 'complianz-gdpr/complianz-gpdr.php',
    		'title' => 'Complianz',
    		'exclusions' => array(
    			'complianz'
    		)
    	),
    	'cookie-notice' => array(
    		'id' => 'cookie-notice/cookie-notice.php',
    		'title' => 'Cookie Notice & Compliance for GDPR',
    		'exclusions' => array(
    			'/plugins/cookie-notice/js/front.min.js',
				'cnArgs',
				'cdn.hu-manity.co',
				'huOptions'
    		)
    	),
    	'cookieyes' => array(
    		'id' => 'cookie-law-info/cookie-law-info.php',
    		'title' => 'CookieYes',
    		'exclusions' => array(
    			'jquery.min.js',
    			'/plugins/cookie-law-info/legacy/public/js/cookie-law-info-public.js',
				'cookie-law-info-js-extra'
    		)
    	),
    	'gdpr-cookie-compliance' => array(
    		'id' => 'gdpr-cookie-compliance/moove-gdpr.php',
    		'title' => 'GDPR Cookie Compliance',
    		'exclusions' => array(
    			'jquery.min.js',
    			'/plugins/gdpr-cookie-compliance/',
				'moove_gdpr'
    		)
    	),
    	'gravityforms' => array(
    		'id' => 'gravityforms/gravityforms.php',
    		'title' => 'Gravity Forms',
    		'exclusions' => array(
    			'jquery.min.js',
    			'gravityforms',
    			'gform',
				'moxiejs-js',
				'plupload-js'
    		)
    	),
    	'grow-for-wp' => array(
    		'id' => 'grow-for-wp/grow-for-wp.php',
    		'title' => 'Grow for WordPress',
    		'exclusions' => array(
    			'mv-script-wrapper-js'
    		)
    	),
    	'grow-social' => array(
    		'id' => 'social-pug/index.php',
    		'title' => 'Grow Social',
    		'exclusions' => array(
    			'dpsp-frontend-js-pro-js'
    		)
    	),
    	'jet-menu' => array(
    		'id' => 'jet-menu/jet-menu.php',
    		'title' => 'JetMenu',
    		'exclusions' => array(
    			'jquery.min.js',
				'jquery-migrate.min.js',
				'/elementor-pro/',
				'/elementor/',
				'/jet-blog/assets/js/lib/slick/slick.min.js',
				'/jet-elements/',
				'/jet-menu/',
				'elementorFrontendConfig',
				'ElementorProFrontendConfig',
				'hasJetBlogPlaylist',
				'JetEngineSettings',
				'jetMenuPublicSettings'
    		)
    	),
    	'kadence-blocks' => array(
    		'id' => 'kadence-blocks/kadence-blocks.php',
    		'title' => 'Kadence Blocks',
    		'exclusions' => array(
    			'kadence-blocks-tabs-js',
				'kad-splide-js',
				'kadence-slide-init-js',
				'kadence-blocks-splide-init-js',
				'kadence-blocks-pro-splide-init-js',
				'--scrollbar-offset'
    		)
    	),
    	'lightweight-cookie-notice' => array(
    		'id' => 'lightweight-cookie-notice-free/init.php',
    		'title' => 'Lightweight Cookie Notice',
    		'exclusions' => array(
    			'/lightweight-cookie-notice-free/public/assets/js/production/general.js',
				'daextlwcnf-general-js-after',
				'daextlwcnf-general-js-extra'
    		)
    	),
    	'mediavine' => array(
    		'id' => 'mediavine-control-panel/mediavine-control-panel.php',
    		'title' => 'Mediavine',
    		'exclusions' => array(
    			'mediavine'
    		)
    	),
    	'modula-slider' => array(
    		'id' => 'modula-slider/modula-slider.php',
    		'title' => 'Modula Slider',
    		'exclusions' => array(
    			'jquery.min.js',
    			'/modula-slider/'
    		)
    	),
    	'monumetric-ads' => array(
    		'id' => 'monumetric-ads/monumetric-ads.php',
    		'title' => 'Monumetric Ads',
    		'exclusions' => array(
    			'$MMT',
    			'monu.delivery'
    		)
    	),
    	'ninja-forms' => array(
    		'id' => 'ninja-forms/ninja-forms.php',
    		'title' => 'Ninja Forms',
    		'exclusions' => array(
    			'jquery.min.js',
    			'/wp-includes/js/underscore.min.js',
				'/wp-includes/js/backbone.min.js',
				'/ninja-forms/assets/js/min/front-end.js',
				'/ninja-forms/assets/js/min/front-end-deps.js',
				'nfForms',
				'nf-'
    		)
    	),
    	'presto-player' => array(
    		'id' => 'presto-player/presto-player.php',
    		'title' => 'Presto Player',
    		'exclusions' => array(
    			'presto'
    		)
    	),
    	'raptive-ads' => array(
    		'id' => 'adthrive-ads/adthrive-ads.php',
    		'title' => 'Raptive Ads',
    		'exclusions' => array(
    			'adthrive',
    			'raptive',
    			'adManagementConfig'
    		)
    	),
    	'real-bookie-banner-pro' => array(
    		'id' => 'real-cookie-banner-pro/index.php',
    		'title' => 'Real Cookie Banner Pro',
    		'exclusions' => array(
    			'vendor-banner.pro.js',
				'banner.pro.js',
				'realCookieBanner',
				'real-cookie-banner-pro-banner-js-before'
    		)
    	),
    	'revslider' => array(
    		'id' => 'revslider/revslider.php',
    		'title' => 'Revolution Slider',
    		'exclusions' => array(
    			'jquery.min.js',
				'jquery-migrate.min.js',
				'revslider',
				'rev_slider',
				'setREVStartSize',
				'window.RS_MODULES'
    		)
    	),
    	'sheknows-infuse' => array(
    		'id' => 'sheknows-infuse/sheknows-infuse.php',
    		'title' => 'SHE Media Infuse',
    		'exclusions' => array(
    			'blogherads'
    		)
    	),
    	'shortpixel' => array(
    		'id' => 'shortpixel-adaptive-images/short-pixel-ai.php',
    		'title' => 'ShortPixel Adaptive Images',
    		'exclusions' => array(
    			'shortpixel.ai/assets/js/bundles/spai-lib'
    		)
    	),
    	'slick-engagement' => array(
    		'id' => 'slick-engagement/slick-engagement.php',
    		'title' => 'Slickstream',
    		'exclusions' => array(
    			'slickstream'
    		)
    	),
    	'smart-slider-3' => array(
    		'id' => 'smart-slider-3/smart-slider-3.php',
    		'title' => 'Smart Slider 3',
    		'exclusions' => array(
    			'/smart-slider-3/',
				'_N2'
    		)
    	),
    	'smart-slider-3-pro' => array(
    		'id' => 'nextend-smart-slider3-pro/nextend-smart-slider3-pro.php',
    		'title' => 'Smart Slider 3 Pro',
    		'exclusions' => array(
    			'/nextend-smart-slider3-pro/',
				'_N2'
    		)
    	),
        'elementor' => array(
            'id' => 'elementor/elementor.php',
            'title' => 'Elementor',
            'exclusions' => array(
                'jquery.min.js',
                'jquery.smartmenus.min.js',
                'webpack.runtime.min.js',
                'webpack-pro.runtime.min.js',
				'/elementor/assets/js/frontend.min.js',
				'/elementor-pro/assets/js/frontend.min.js',
                'frontend-modules.min.js',
                'elements-handlers.min.js',
                'elementorFrontendConfig',
                'ElementorProFrontendConfig',
                'imagesloaded.min.js'
            )   
        ),
        'elementor-search' => array(
            'id' => 'elementor/elementor.php',
            'title' => 'Elementor Search',
            'exclusions' => array(
                'webpack-pro.runtime.min.js',
				'webpack.runtime.min.js',
				'elements-handlers.min.js',
				'jquery.smartmenus.min.js'
            )   
        ),
        'termageddon-usercentrics' => array(
            'id' => 'termageddon-usercentrics/termageddon-usercentrics.php',
            'title' => 'Termageddon + Usercentrics',
            'exclusions' => array(
                'jquery.min.js',
                'termageddon',
				'usercentrics',
				'UC_UI'
            )   
        ),
        'thrive-leads'  => array(
            'id' => 'thrive-leads/thrive-leads.php',
            'title' => 'Thrive Leads',
            'exclusions' => array(
                'jquery.min.js',
                'tve_frontend-js'
            )
        ),
        'woocommerce-product-gallery' => array(
            'id' => 'woocommerce/woocommerce.php',
            'title' => 'WooCommerce Single Product Gallery',
            'exclusions' => array(
                'jquery.min.js',
                'flexslider',
                'single-product.min.js',
                'slick',
                'functions.min.js',
                'waypoint',
                'photoswipe',
                'jquery.zoom.min.js'
            )
        ),
        'wp-armour' => array(
        	'id' => 'honeypot/wp-armour.php',
        	'title' => 'WP Armour',
        	'exclusions' => array(
        		'wpa_field_info'
        	)
        ),
        'wpforms-lite' => array(
    		'id' => 'wpforms-lite/wpforms.php',
    		'title' => 'WPForms Lite',
    		'exclusions' => array(
    			'jquery.min.js',
				'wpforms'
    		)
    	),
    	'wpforms' => array(
    		'id' => 'wpforms/wpforms.php',
    		'title' => 'WPForms',
    		'exclusions' => array(
    			'jquery.min.js',
				'wpforms'
    		)
    	),
    	'wp-recipe-maker' => array(
    		'id' => 'wp-recipe-maker/wp-recipe-maker.php',
    		'title' => 'WP Recipe Maker',
    		'exclusions' => array(
    			'wprm-public-js',
    			'wprm-public-js-extra',
    			'wprmp-public-js',
    			'wprmp-public-js-extra',
    			'wprm-shared-js',
				'wprmp-admin-js',
				'wprm-admin-js',
				'wprm-admin-modal-js'
    		)
    	),
    	'ws-form' => array(
    		'id' => 'ws-form/ws-form.php',
    		'title' => 'WS Form Lite',
    		'exclusions' => array(
    			'jquery.min.js',
    			'jquery/ui',
				'ws-form',
				'wsf-wp-footer',
				'quicktags-js-extra'
    		)
    	),
    	'ws-form-pro' => array(
    		'id' => 'ws-form-pro/ws-form.php',
    		'title' => 'WS Form Pro',
    		'exclusions' => array(
    			'jquery.min.js',
    			'jquery/ui',
				'ws-form-pro',
				'wsf-wp-footer',
				'quicktags-js-extra'
    		)
    	)
    ),
    'themes' => array(
    	'astra' => array(
    		'id' => 'astra',
    		'title' => 'Astra',
    		'exclusions' => array(
    			'jquery.min.js',
				'astra'
    		)
    	),
    	'avada' => array(
    		'id' => 'avada',
    		'title' => 'Avada',
    		'exclusions' => array(
    			'jquery.min.js',
    			'avada-header.js',
				'modernizr.js',
				'jquery.easing.js',
				'avadaHeaderVars'
    		)
    	),
    	'bricks' => array(
    		'id' => 'bricks',
    		'title' => 'Bricks',
    		'exclusions' => array(
    			'/themes/bricks/assets/js/bricks.min.js',
    			'/themes/bricks/assets/js/libs/swiper.min.js',
    			'bricks-scripts-js-extra'
    		)
    	),
    	'divi' => array(
    		'id' => 'divi',
    		'title' => 'Divi',
    		'exclusions' => array(
    			'jquery.min.js',
				'/Divi/js/scripts.min.js',
				'et_pb_custom',
				'elm.style.display'
    		)
    	),
    	'divi-animations' => array(
    		'id' => 'divi',
    		'title' => 'Divi with Animations',
    		'exclusions' => array(
				'jquery.min.js',
				'jquery-migrate.min.js',
				'.divi_preloader_wrapper_outer',
				'/Divi/js/scripts.min.js',
				'/Divi/js/custom.unified.js',
				'/js/magnific-popup.js',
				'et_pb_custom',
				'et_animation_data',
				'var DIVI',
				'elm.style.display',
				'easypiechart.js'
    		)
    	),
        'generatepress-masonry-blog' => array(
            'id' => 'generatepress',
            'title' => 'GeneratePress Masonry Blog',
            'exclusions' => array(
                'generateBlog',
                'scripts.min.js',
                'masonry.min.js',
                'imagesloaded.min.js'
            )
        ),
        'generatepress-mobile-menu' => array(
            'id' => 'generatepress',
            'title' => 'GeneratePress Mobile Menu',
            'exclusions' => array(
                '/generatepress/assets/js/menu.min.js',
                'generatepressMenu'
            )
        ),
        'generatepress-offside-menu' => array(
            'id' => 'generatepress',
            'title' => 'GeneratePress Offside Menu',
            'exclusions' => array(
                '/generatepress/assets/js/menu.min.js',
                'generatepressMenu',
                'offside.min.js',
                'offSide'
            )
        ),
        'kadence-menu' => array(
            'id' => 'kadence',
            'title' => 'Kadence Menu',
            'exclusions' => array(
                'kadence-navigation-js'
            )
        ),
        'mediavine-trellis' => array(
    		'id' => 'mediavine-trellis',
    		'title' => 'Mediavine Trellis',
    		'exclusions' => array(
    			'mv-trellis-localModel'
    		)
    	),
        'newspaper' => array(
    		'id' => 'newspaper',
    		'title' => 'Newspaper',
    		'exclusions' => array(
    			'jquery.min.js',
				'jquery-migrate.min.js',
				'tagdiv_theme.min.js',
				'tdBlocksArray'
    		)
    	),
    	'oceanwp' => array(
    		'id' => 'oceanwp',
    		'title' => 'OceanWP Mobile Menu',
    		'exclusions' => array(
    			'drop-down-mobile-menu.min.js',
				'oceanwpLocalize'
    		)
    	),
    	'salient' => array(
    		'id' => 'salient',
    		'title' => 'Salient',
    		'exclusions' => array(
    			'jquery.min.js',
				'jquery-migrate.min.js',
				'/salient/',
				'/salient-nectar-slider/js/nectar-slider.js'
    		)
    	)
    )
);