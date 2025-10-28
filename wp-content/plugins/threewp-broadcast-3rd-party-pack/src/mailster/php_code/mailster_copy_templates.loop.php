global $wpdb;

$wp_upload_dir = wp_upload_dir();
$target_template_directory = sprintf( "%s/mailster/templates", $wp_upload_dir[ 'basedir' ], $wpdb->prefix );

$templates = [];
if ( count( $mailster_templates_to_copy ) < 1 )
	$templates = glob( $source_template_directory . '/*' );
else
{
	foreach( $mailster_templates_to_copy as $template )
		$templates []= $source_template_directory . '/' . $template;
}

foreach( $templates as $template )
{
	if ( ! is_dir( $template ) )
	{
		$this->debug( 'Template %s is not a directory. Skipping.', $template );
		continue;
	}

	$target = sprintf( "%s/%s", $target_template_directory, basename( $template ) );

	$this->debug( 'Copying from %s to %s', $template, $target );

	// Create the basic mailster templates directory.
	if ( is_dir( $target ) )
	{
		$this->debug( 'Deleting existing directory: %s', $target );
		\threewp_broadcast\premium_pack\classes\files_trait::delete_recursive( $target );
	}

	$this->debug( 'Copying %s to %s', $template, $target );
	\threewp_broadcast\premium_pack\classes\files_trait::copy_recursive( $template, $target );
}
