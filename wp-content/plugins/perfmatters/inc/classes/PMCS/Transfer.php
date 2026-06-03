<?php
namespace Perfmatters\PMCS;

class Transfer
{
    private const EXPORT_VERSION = 2; //added base64 encoding to code fields

    //return export content for requested snippets
    public static function get_export_content($file_names) {

        $snippet_data = [];

        foreach((array)$file_names as $file_name) {

            $file_name = PMCS::normalize_snippet_file_name($file_name);

            if(empty($file_name)) {
                continue;
            }

            $snippet = Snippet::get($file_name);

            if(!is_array($snippet) || empty($snippet['meta']) || empty($snippet['code'])) {
                continue;
            }

            $snippet_data[$file_name] = $snippet;
        }

        return $snippet_data;
    }

    //format and encode snippet export data for download
    public static function encode(array $snippet_data) {

        $encoded_snippets = [];

        foreach($snippet_data as $file_name => $snippet) {

            if(empty($snippet['meta']) || empty($snippet['code'])) {
                continue;
            }

            $snippet['code'] = base64_encode((string) $snippet['code']);
            $encoded_snippets[$file_name] = $snippet;
        }

        if(empty($encoded_snippets)) {
            return new \WP_Error('encoding_failed', __('No valid snippets found.', 'perfmatters'));
        }

        $json_output = wp_json_encode([
            'pmcs_export' => self::EXPORT_VERSION,
            'snippets' => $encoded_snippets,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if($json_output === false) {
            return new \WP_Error('encoding_failed', __('Failed to encode snippet export data.', 'perfmatters'));
        }

        return $json_output;
    }

    //normalize snippet import payload from legacy or versioned export files
    public static function parse_import($payload) {

        if(!is_array($payload)) {
            return new \WP_Error('invalid_json', __('Invalid JSON file.', 'perfmatters'));
        }

        $decode_code = false;
        $snippet_data = $payload;

        //export version
        if(isset($payload['pmcs_export'])) {
            $version = (int) $payload['pmcs_export'];

            if($version !== self::EXPORT_VERSION) {
                return new \WP_Error('unsupported_export', __('Unsupported export file version.', 'perfmatters'));
            }

            if(empty($payload['snippets']) || !is_array($payload['snippets'])) {
                return new \WP_Error('invalid_export', __('Invalid export file structure.', 'perfmatters'));
            }

            $decode_code = true; //v2
            $snippet_data = $payload['snippets'];
        }

        $snippets = [];

        foreach($snippet_data as $file_name => $snippet) {
            if(!is_string($file_name) || !is_array($snippet)) {
                continue;
            }

            $file_name = PMCS::normalize_snippet_file_name($file_name);

            if(empty($file_name)) {
                continue;
            }

            if($decode_code) {
                if(!array_key_exists('code', $snippet)) {
                    continue;
                }

                $decoded = base64_decode((string) $snippet['code'], true);

                if($decoded === false) {
                    continue;
                }

                $snippet['code'] = $decoded;
            }

            $snippets[$file_name] = $snippet;
        }

        if($decode_code && empty($snippets) && !empty($snippet_data)) {
            return new \WP_Error('invalid_export', __('No valid snippets were found in the export file.', 'perfmatters'));
        }

        return $snippets;
    }

    //export snippet, supports multiple
    public static function export($file_names) {

        $snippet_data = self::get_export_content($file_names);

        if(empty($snippet_data)) {
            PMCS::admin_notice_redirect('export', 'file_not_found');
        }

        if(count($snippet_data) > 1) {
            $download_name = 'perfmatters-snippets-bulk-export-' . $_SERVER['HTTP_HOST'] . '-' . date('Y-m-d') . '.json';
        }
        else {
            $download_name = 'perfmatters-snippet-' . preg_replace('/\.php$/', '', key($snippet_data)) . '-' . date('Y-m-d') . '.json';
        }

        $json_output = self::encode($snippet_data);

        if(is_wp_error($json_output)) {
            wp_die(esc_html($json_output->get_error_message()));
        }

        self::force_download($download_name, $json_output);
    }

    //force JSON file to download in the browser on the current request
    private static function force_download($filename, $content, $temp_file_path = null) {
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Type: application/json; charset=utf-8');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($content));

        echo $content;

        if($temp_file_path && is_file($temp_file_path)) {
            unlink($temp_file_path);
        }

        exit;
    }
}