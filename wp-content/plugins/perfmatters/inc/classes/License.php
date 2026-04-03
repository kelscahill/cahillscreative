<?php
namespace Perfmatters;

class License {

    //init
    public static function init() {
        add_action('init', array('Perfmatters\License', 'constant_check'));
    }

    //activate license
    public static function activate($license_key = null, $network = false) {

        //grab existing license data
        $license = $license_key ?? (self::get_key_constant() ?? (is_multisite() || $network ? get_site_option('perfmatters_edd_license_key') : get_option('perfmatters_edd_license_key')));

        if(!empty($license)) {

            $api_params = array(
                'edd_action'=> 'activate_license',
                'license' 	=> $license,
                'item_name' => urlencode(PERFMATTERS_ITEM_NAME),
                'url'       => home_url()
            );

            //EDD API request
            $response = wp_remote_post(PERFMATTERS_STORE_URL, array('timeout' => 15, 'sslverify' => true, 'body' => $api_params));

            if(is_wp_error($response)) {
                return false;
            }

            //decode the license data
            $license_data = json_decode(wp_remote_retrieve_body($response));

            //license is valid
            if(!empty($license_data->license) && $license_data->license == 'valid') {

                //update stored option
                if(is_multisite() || $network) {
                    update_site_option('perfmatters_edd_license_status', $license_data->license);
                    return true;
                }
                else {
                    update_option('perfmatters_edd_license_status', $license_data->license, false);
                    return true;
                }
            }
        }

        return false;
    }

    //deactivate license
    public static function deactivate($license_key = null, $network = false) {

        //grab existing license data
        $license = $license_key ?? (self::get_key_constant() ?? (is_multisite() || $network ? get_site_option('perfmatters_edd_license_key') : get_option('perfmatters_edd_license_key')));

        if(!empty($license)) {

            $api_params = array(
                'edd_action'=> 'deactivate_license',
                'license' 	=> $license,
                'item_name' => urlencode(PERFMATTERS_ITEM_NAME),
                'url'       => home_url()
            );

            //EDD API request
            $response = wp_remote_post(PERFMATTERS_STORE_URL, array('timeout' => 15, 'sslverify' => true, 'body' => $api_params));

            if(is_wp_error($response)) {
                return false;
            }

            //decode the license data
            $license_data = json_decode(wp_remote_retrieve_body($response));

            //license is deactivated
            if($license_data->license == 'deactivated') {

                //update license option
                if(is_multisite() || $network) {
                    delete_site_option('perfmatters_edd_license_status');
                    return true;
                }
                else {
                    delete_option('perfmatters_edd_license_status');
                    return true;
                }
            }
        }

        return false;
    }

    //check license
    public static function check($license_key = null, $network = false) {

        //grab existing license data
        $license = $license_key ?? (self::get_key_constant() ?? (is_multisite() || $network ? get_site_option('perfmatters_edd_license_key') : get_option('perfmatters_edd_license_key')));

        if(!empty($license)) {

            $api_params = array(
                'edd_action' => 'check_license',
                'license' => $license,
                'item_name' => urlencode(PERFMATTERS_ITEM_NAME),
                'url'       => home_url()
            );

            // Call the custom API.
            $response = wp_remote_post(PERFMATTERS_STORE_URL, array('timeout' => 15, 'sslverify' => true, 'body' => $api_params));

            //make sure the response came back okay
            if(is_wp_error($response)) {
                return false;
            }

            //decode the license data
            $license_data = json_decode(wp_remote_retrieve_body($response));

            //update license option
            if(is_multisite() || $network) {
                update_site_option('perfmatters_edd_license_status', $license_data->license);
            }
            else {
                update_option('perfmatters_edd_license_status', $license_data->license, false);
            }
            
            //return license data for use
            return($license_data);
        }

        return false;
    }

    //check for license key constant
    public static function constant_check() {

        //only check in admin
        if(is_admin()) {

            $license_key_constant = self::get_key_constant();
            $license_key_constant_stored = is_multisite() ? get_site_option('perfmatters_edd_license_key_constant') : get_option('perfmatters_edd_license_key_constant');

            //license constant defined
            if($license_key_constant !== false) {

                //mismatch on stored constant
                if($license_key_constant != $license_key_constant_stored) {

                    //deactivate previous stored key
                    if(!empty($license_key_constant_stored )) {
                        self::deactivate($license_key_constant_stored);
                    }

                    //store new key
                    if(is_multisite()) {
                        update_site_option('perfmatters_edd_license_key_constant', $license_key_constant);
                    }
                    else {
                        update_option('perfmatters_edd_license_key_constant', $license_key_constant, false);
                    }

                    //active new key
                    self::activate($license_key_constant);
                }

            }
            else {

                //constant still stored but no definition
                if($license_key_constant_stored != '') {

                    //deactivate previous stored key
                    self::deactivate($license_key_constant_stored);

                    //remove stored key
                    if(is_multisite()) {
                        delete_site_option('perfmatters_edd_license_key_constant');
                    }
                    else {
                        delete_option('perfmatters_edd_license_key_constant');
                    }
                }
            }
        }
    }

    //get license key constant
    public static function get_key_constant() {
        if(defined('PERFMATTERS_LICENSE_KEY')) {
            return trim(PERFMATTERS_LICENSE_KEY);
        }
    }
}