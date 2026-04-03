<?php
namespace Perfmatters;

class Analytics {

    //initialize analytics
    public static function init() {
        $options = Config::$options;

        //google analytics
        if(!empty($options['analytics']['enable_local_ga'])) {
            $print_analytics = true;

            if(empty($options['analytics']['script_type'])) {
                if(!wp_next_scheduled('perfmatters_update_ga')) {
                    wp_schedule_event(time(), 'daily', 'perfmatters_update_ga');
                }
                if(!empty($options['analytics']['use_monster_insights'])) {
                    $print_analytics = false;
                    add_filter('monsterinsights_frontend_output_gtag_src', [__CLASS__, 'monster_ga_gtag'], 1000);
                }
            }
            else {
                if(wp_next_scheduled('perfmatters_update_ga')) {
                    wp_clear_scheduled_hook('perfmatters_update_ga');
                }
            }

            if($print_analytics) {
                $tracking_code_position = (!empty($options['analytics']['tracking_code_position']) && $options['analytics']['tracking_code_position'] === 'footer') ? 'wp_footer' : 'wp_head';
                add_action($tracking_code_position, [__CLASS__, 'print_ga']);
            }

            //add notice if tracking id isnt set
            if(empty($options['analytics']['tracking_id'])) {
                add_action('admin_notices', [__CLASS__, 'admin_notice_ga_tracking_id']);
            }
        }
        else {
            if(wp_next_scheduled('perfmatters_update_ga')) {
                wp_clear_scheduled_hook('perfmatters_update_ga');
            }
        }
        add_action('perfmatters_update_ga', [__CLASS__, 'update_ga']);
    }

    //update analytics local files
    public static function update_ga() {

        $options = get_option('perfmatters_options');

        $queue = [];

        $upload_dir = wp_get_upload_dir();
        
        //add gtagv4 to queue
        if(empty($options['analytics']['script_type'])) {
            if(!empty($options['analytics']['tracking_id'])) {
                $queue['gtagv4']= [
                    'remote' => 'https://www.googletagmanager.com/gtag/js?id=' . $options['analytics']['tracking_id'],
                    'local' => $upload_dir['basedir'] . '/perfmatters/gtagv4.js'
                ];
            }
        }

        if(!empty($queue)) {
            foreach($queue as $type => $files) {
                if(!empty($files['remote']) && !empty($files['local'])) {

                    $file = wp_remote_get($files['remote']);

                    if(is_wp_error($file)) {
                        return $file->get_error_code() . ': ' . $file->get_error_message();
                    }

                    if(!is_dir($upload_dir['basedir']  . '/perfmatters/')) {
                        wp_mkdir_p($upload_dir['basedir']  . '/perfmatters/');
                    }
                
                    file_put_contents($files['local'], $file['body']);
                }
            }
        }
    }

    //print analytics script
    public static function print_ga() {
        $options = Config::$options;

        //dont print for logged in admins
        if(current_user_can('manage_options') && empty($options['analytics']['track_admins'])) {
            return;
        }

        //make sure we have a tracking id
        if(empty($options['analytics']['tracking_id'])) {
            return;
        }

        $upload_dir = wp_get_upload_dir();

        $output = '';

        if(empty($options['analytics']['script_type'])) {
            $output.= '<script async src="' . $upload_dir['baseurl'] . '/perfmatters/gtagv4.js?id=' . $options['analytics']['tracking_id'] . '"></script>'; 
            $output.= '<script>window.dataLayer = window.dataLayer || [];function gtag(){dataLayer.push(arguments);}gtag("js", new Date());gtag("config", "' . $options['analytics']['tracking_id'] . '");</script>';
        }
        elseif($options['analytics']['script_type'] == 'minimalv4') {
            $output.= '<script>window.pmGAID="' . $options['analytics']['tracking_id'] . '";</script>';
            $output.= '<script async src="' . str_replace('http:', 'https:', plugins_url()) . '/perfmatters/js/analytics-minimal-v4.js"></script>';
        }

        //amp analytics
        if(!empty($options['analytics']['enable_amp'])) {
            if(function_exists('is_amp_endpoint') && is_amp_endpoint()) {
                $output.= '<script async custom-element="amp-analytics" src="https://cdn.ampproject.org/v0/amp-analytics-0.1.js"></script>';
                $output.= '<amp-analytics type="gtag" data-credentials="include"><script type="application/json">{"vars" : {"gtag_id": "' . $options['analytics']['tracking_id'] . '", "config" : {"' . $options['analytics']['tracking_id'] . '": { "groups": "default" }}}}</script></amp-analytics>';
            }
        }

        if(!empty($output)) {
            echo $output;
            do_action('perfmatters_after_local_analytics');
        }
    }

    //filter monster insights gtag source
    public static function monster_ga_gtag($url) {
        $options = Config::$options;

        if(!empty($options['analytics']['tracking_id'])) {
            $upload_dir = wp_get_upload_dir();
            return $upload_dir['baseurl'] . '/perfmatters/gtagv4.js?id=' . $options['analytics']['tracking_id'];
        }

        return $url;
    }

    //admin notice missing tracking id
    public static function admin_notice_ga_tracking_id() {
        self::admin_notice_error(
            __('Perfmatters Warning', 'perfmatters'),
            __('Local Analytics is enabled but no Tracking ID is set.', 'perfmatters')
        );
    }

    //admin notice helper
    private static function admin_notice_error(string $title, string $message): void {
        echo '<div class="notice notice-error"><p><strong>' . esc_html($title) . ':</strong> ' . esc_html($message) . '</p></div>';
    }
}
