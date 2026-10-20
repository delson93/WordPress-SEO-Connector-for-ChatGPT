<?php
namespace CodeIXE\SEO;

defined( 'ABSPATH' ) || exit;

final class API {
	public static function register() {
		register_rest_route( 'codeixe-seo/v1', '/openapi', array( 'methods' => 'GET', 'callback' => array( Actions::class, 'schema' ), 'permission_callback' => '__return_true' ) );
		$routes = array(
			'/status' => array( 'GET', 'status' ),
			'/posts' => array( 'GET', 'posts' ),
			'/posts/(?P<id>\d+)' => array( 'GET', 'post' ),
			'/posts/(?P<id>\d+)/audit' => array( 'GET', 'audit' ),
			'/audit' => array( 'GET', 'batch' ),
			'/proposals' => array( 'POST', 'propose' ),
			'/proposals/(?P<id>\d+)/apply' => array( 'POST', 'apply' ),
			'/proposals/(?P<id>\d+)/rollback' => array( 'POST', 'rollback' ),
			'/history' => array( 'GET', 'history' ),
		);
		foreach ( $routes as $path => $route ) {
			register_rest_route( 'codeixe-seo/v1', $path, array( 'methods' => $route[0], 'callback' => function ( $request ) use ( $route ) {
				try {
					$result = call_user_func( array( self::class, $route[1] ), $request );
				} finally { Actions::restore(); }
				if ( is_wp_error( $result ) ) { return $result; }
				$response = rest_ensure_response( $result );
				$response->header( 'Cache-Control', 'private, no-store' );
				return $response;
			}, 'permission_callback' => array( Actions::class, 'permission' ) ) );
		}
	}

	public static function status() {
		return array( 'version' => '0.2.0', 'site_url' => home_url(), 'site_name' => get_bloginfo( 'name' ), 'settings' => Plugin::settings(), 'providers' => Metadata::providers(), 'fallback_active' => Metadata::enabled(), 'abilities_api' => function_exists( 'wp_register_ability' ), 'mcp_adapter' => class_exists( '\WP\MCP\Core\McpAdapter' ), 'application_passwords' => wp_is_application_passwords_available(), 'authorization_header_present' => ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) || ! empty( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ), 'https' => is_ssl(), 'cron_disabled' => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON, 'approval' => 'Administrator UI or explicitly scoped Actions policy', 'yoast_overrides_active' => Metadata::yoast_enabled(), 'third_party_metadata_writes' => false );
	}

	public static function posts( $request ) {
		$page = min( 100000, max( 1, (int) ( $request['page'] ?? 1 ) ) );
		$type = $request['type'] ?? 'post';
		if ( ! in_array( $type, array( 'post', 'page', 'attachment' ), true ) ) { return new \WP_Error( 'codeixe_type', 'Unsupported post type.', array( 'status' => 400 ) ); }
		$query = new \WP_Query( array( 'post_type' => $type, 'post_status' => 'attachment' === $type ? 'inherit' : 'publish', 'posts_per_page' => 10, 'paged' => $page, 'orderby' => 'ID', 'order' => 'ASC', 'has_password' => false ) );
		$items = array();
		foreach ( $query->posts as $post ) {
			if ( current_user_can( 'edit_post', $post->ID ) ) { $items[] = array( 'id' => $post->ID, 'title' => $post->post_title, 'url' => get_permalink( $post->ID ), 'type' => $post->post_type ); }
		}
		return array( 'items' => $items, 'page' => $page, 'has_more' => $page < $query->max_num_pages );
	}

	public static function post( $request ) {
		$id = absint( $request['id'] );
		$post = Plugin::target( $id );
		if ( is_wp_error( $post ) ) { return $post; }
		return array( 'id' => $id, 'title' => $post->post_title, 'content' => $post->post_content, 'excerpt' => $post->post_excerpt, 'status' => $post->post_status, 'url' => get_permalink( $id ), 'metadata' => Metadata::get( $id ), 'effective_metadata' => Metadata::effective( $id ), 'providers' => Metadata::providers(), 'alt' => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ), 'untrusted_content' => true );
	}

	public static function audit( $request ) {
		$limit = Plugin::throttle( 'audit', 30 );
		if ( is_wp_error( $limit ) ) { return $limit; }
		return Audit::post( absint( $request['id'] ), rest_sanitize_boolean( $request['rendered'] ?? false ) );
	}

	public static function batch( $request ) {
		$limit = Plugin::throttle( 'batch', 6 );
		if ( is_wp_error( $limit ) ) { return $limit; }
		$posts = self::posts( $request );
		if ( is_wp_error( $posts ) ) { return $posts; }
		$items = array();
		foreach ( $posts['items'] as $post ) { $items[] = Audit::post( $post['id'] ); }
		return array( 'items' => $items, 'page' => $posts['page'], 'has_more' => $posts['has_more'], 'scope' => 'bounded_stored_content_batch' );
	}

	public static function propose( $request ) {
		return Changes::propose( absint( $request['id'] ), $request['changes'] ?? null );
	}

	public static function apply( $request ) {
		$limit = Plugin::throttle( 'apply', 20 );
		if ( is_wp_error( $limit ) ) { return $limit; }
		return Changes::apply( absint( $request['id'] ), false, $request['digest'] ?? '' );
	}

	public static function rollback( $request ) {
		$limit = Plugin::throttle( 'apply', 20 );
		if ( is_wp_error( $limit ) ) { return $limit; }
		return Changes::apply( absint( $request['id'] ), true, $request['digest'] ?? '' );
	}

	public static function history( $request ) {
		return Changes::history( max( 1, (int) ( $request['page'] ?? 1 ) ) );
	}
}
