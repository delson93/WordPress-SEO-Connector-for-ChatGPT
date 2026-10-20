<?php
namespace CodeIXE\SEO;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	public static function boot() {
		add_action( 'init', array( self::class, 'register' ) );
		add_action( 'rest_api_init', array( API::class, 'register' ) );
		add_action( 'admin_menu', array( Admin::class, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( Admin::class, 'assets' ) );
		add_action( 'admin_post_codeixe_seo_action', array( Admin::class, 'action' ) );
		Metadata::register_yoast();
		add_action( 'wp_head', array( Metadata::class, 'head' ), 5 );
		add_filter( 'pre_get_document_title', array( Metadata::class, 'title' ), 20 );
		add_filter( 'get_canonical_url', array( Metadata::class, 'canonical' ), 20, 2 );
		add_filter( 'wp_robots', array( Metadata::class, 'robots' ) );
		add_action( 'wp_abilities_api_categories_init', array( Abilities::class, 'category' ) );
		add_action( 'wp_abilities_api_init', array( Abilities::class, 'register' ) );
	}

	public static function register() {
		register_post_type( 'codeixe_change', array( 'public' => false, 'show_ui' => false, 'show_in_rest' => false, 'supports' => array(), 'rewrite' => false, 'can_export' => false ) );
	}

	public static function settings() {
		return wp_parse_args( get_option( 'codeixe_seo_settings', array() ), array( 'enabled' => false, 'readonly' => true, 'fallback' => false, 'cleanup' => false, 'yoast_overrides' => false, 'remote_apply' => false, 'remote_fields' => array() ) );
	}

	public static function permission() {
		if ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'codeixe_forbidden', 'An authenticated editor is required.', array( 'status' => 403 ) );
		}
		if ( ! self::settings()['enabled'] || ! is_ssl() ) {
			return new \WP_Error( 'codeixe_disabled', 'Enable the connector and use HTTPS.', array( 'status' => 403 ) );
		}
		return true;
	}

	public static function target( $id ) {
		$post = get_post( $id );
		if ( ! $post || ! in_array( $post->post_type, array( 'post', 'page', 'attachment' ), true ) || ! current_user_can( 'edit_post', $id ) || in_array( $post->post_status, array( 'trash', 'auto-draft' ), true ) ) {
			return new \WP_Error( 'codeixe_target', 'Object is unavailable or not editable.', array( 'status' => 403 ) );
		}
		return $post;
	}

	public static function throttle( $action, $limit = 30 ) {
		$key = 'codeixe_rate_' . get_current_user_id() . '_' . $action;
		$count = (int) get_transient( $key );
		if ( $count >= $limit ) {
			return new \WP_Error( 'codeixe_rate', 'Rate limit reached. Retry in one minute.', array( 'status' => 429 ) );
		}
		set_transient( $key, $count + 1, MINUTE_IN_SECONDS );
		return true;
	}
}
