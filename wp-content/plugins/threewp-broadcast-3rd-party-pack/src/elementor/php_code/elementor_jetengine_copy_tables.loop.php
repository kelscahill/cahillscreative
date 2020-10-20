global $wpdb;

// CPT
// Delete existing rows
$new_cpt_table = sprintf( "%sjet_post_types", $wpdb->prefix );
$query = sprintf( "DELETE FROM `%s` WHERE `slug` IN ( SELECT `slug` FROM `%s` )", $new_cpt_table, $old_cpt_table );
$this->debug( $query );
$wpdb->query( $query );

// Reinsert the rows
$query = sprintf( "INSERT INTO `%s` (%s) ( SELECT %s FROM `%s` )", $new_cpt_table, $cpt_table_columns, $cpt_table_columns, $old_cpt_table );
$this->debug( $query );
$wpdb->query( $query );

// Tax
// Delete existing rows
$new_taxonomy_table = sprintf( "%sjet_taxonomies", $wpdb->prefix );
$query = sprintf( "DELETE FROM `%s` WHERE `slug` IN ( SELECT `slug` FROM `%s` )", $new_taxonomy_table, $old_taxonomies_table );
$this->debug( $query );
$wpdb->query( $query );

// Reinsert the rows
$query = sprintf( "INSERT INTO `%s` (%s) ( SELECT %s FROM `%s` )", $new_taxonomy_table, $taxonomies_table_table_columns, $taxonomies_table_table_columns, $old_taxonomies_table );
$this->debug( $query );
$wpdb->query( $query );
