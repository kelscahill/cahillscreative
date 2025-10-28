/**
	Specify, in an array, which templates to copy.
	If no templates are specified, the default, then all templates are copied.
**/
$mailster_templates_to_copy = [
	// 'mymail',		// The default MyMail template.
];
global $wpdb;

$wp_upload_dir = wp_upload_dir();
$source_template_directory = sprintf( "%s/mailster/templates", $wp_upload_dir[ 'basedir' ], $wpdb->prefix );

$this->debug( 'Source : %s', $source_template_directory );
