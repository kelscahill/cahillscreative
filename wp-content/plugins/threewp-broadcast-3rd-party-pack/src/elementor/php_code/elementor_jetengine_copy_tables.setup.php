global $wpdb;

$old_cpt_table = sprintf( "%sjet_post_types", $wpdb->prefix );
$old_taxonomies_table = sprintf( "%sjet_taxonomies", $wpdb->prefix );

$cpt_table_columns = $this->get_database_table_columns_string( $old_cpt_table, [ 'except' => [ 'id' ] ] );
$taxonomies_table_table_columns = $this->get_database_table_columns_string( $old_cpt_table, [ 'except' => [ 'id' ] ] );

$this->debug( 'Tables: %s, %s', $old_cpt_table, $old_taxonomies_table );
$this->debug( 'Columns: %s ; %s', $cpt_table_columns, $taxonomies_table_table_columns );
