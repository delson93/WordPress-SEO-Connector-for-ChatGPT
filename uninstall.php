<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
// Multisite removal is deliberately manual, avoiding unbounded network-wide deletion.
if ( is_multisite() || empty( get_option( 'codeixe_seo_settings', array() )['cleanup'] ) ) { return; }
global $wpdb;
$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", 'codeixe_change' ) );
foreach ( $ids as $id ) { wp_delete_post( (int) $id, true ); }
foreach ( array( 'seo_title', 'description', 'canonical', 'noindex', 'nofollow' ) as $key ) { delete_post_meta_by_key( '_codeixe_' . $key ); }
delete_option( 'codeixe_seo_settings' );
delete_option( 'codeixe_seo_action_key' );
// Public article content and attachment ALT are never deleted by uninstall.
