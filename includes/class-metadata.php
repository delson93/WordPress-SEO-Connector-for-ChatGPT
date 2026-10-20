<?php
namespace CodeIXE\SEO;

defined( 'ABSPATH' ) || exit;

final class Metadata {
	const FIELDS = array( 'seo_title', 'description', 'canonical', 'noindex', 'nofollow' );

	public static function providers() {
		$found = array();
		foreach ( array( 'Yoast SEO' => defined( 'WPSEO_VERSION' ), 'Rank Math' => defined( 'RANK_MATH_VERSION' ), 'All in One SEO' => defined( 'AIOSEO_VERSION' ), 'SEOPress' => defined( 'SEOPRESS_VERSION' ) ) as $name => $active ) {
			if ( $active ) { $found[] = $name; }
		}
		return $found;
	}

	public static function enabled() {
		return Plugin::settings()['fallback'] && ! self::providers() && ! apply_filters( 'codeixe_seo_external_provider', false );
	}

	public static function yoast_enabled() {
		return Plugin::settings()['yoast_overrides'] && array( 'Yoast SEO' ) === self::providers() && ! apply_filters( 'codeixe_seo_external_provider', false );
	}

	public static function writable( $field ) {
		return self::enabled() || ( self::yoast_enabled() && in_array( $field, array( 'seo_title', 'description' ), true ) );
	}

	public static function register_yoast() {
		foreach ( array( 'wpseo_title' => 'seo_title', 'wpseo_metadesc' => 'description', 'wpseo_opengraph_title' => 'seo_title', 'wpseo_opengraph_desc' => 'description', 'wpseo_twitter_title' => 'seo_title', 'wpseo_twitter_description' => 'description' ) as $hook => $field ) {
			add_filter( $hook, function ( $value, $presentation = null ) use ( $field ) {
				return self::yoast_value( $value, $field, $presentation );
			}, 20, 2 );
		}
	}

	public static function yoast_value( $value, $field, $presentation = null ) {
		if ( ! self::yoast_enabled() ) { return $value; }
		$id = 0;
		if ( isset( $presentation->model->object_type ) ) {
			if ( 'post' !== $presentation->model->object_type ) { return $value; }
			$id = (int) $presentation->model->object_id;
		} elseif ( is_singular() ) { $id = get_queried_object_id(); }
		if ( ! $id || post_password_required( $id ) ) { return $value; }
		$override = (string) get_post_meta( $id, '_codeixe_' . $field, true );
		return '' !== $override ? $override : $value;
	}

	public static function effective( $id ) {
		$data = self::get( $id );
		if ( function_exists( 'YoastSEO' ) && in_array( 'Yoast SEO', self::providers(), true ) ) {
			$surface = YoastSEO()->meta->for_post( $id );
			if ( $surface ) {
				$title = (string) $surface->title;
				$description = (string) $surface->description;
				return array( 'seo_title' => self::yoast_enabled() && $data['seo_title'] ? $data['seo_title'] : $title, 'description' => self::yoast_enabled() && $data['description'] ? $data['description'] : $description, 'source' => self::yoast_enabled() ? 'yoast_with_codeixe_overrides' : 'yoast' );
			}
		}
		return array( 'seo_title' => $data['seo_title'] ?: get_the_title( $id ), 'description' => $data['description'], 'source' => self::enabled() ? 'codeixe_fallback' : 'stored_values_not_active' );
	}

	public static function get( $id ) {
		$result = array();
		foreach ( self::FIELDS as $key ) {
			$result[ $key ] = (string) get_post_meta( $id, '_codeixe_' . $key, true );
		}
		return $result;
	}

	public static function title( $title ) {
		if ( self::enabled() && is_singular() ) {
			$value = get_post_meta( get_queried_object_id(), '_codeixe_seo_title', true );
			if ( $value ) { return $value; }
		}
		return $title;
	}

	public static function canonical( $url, $post ) {
		if ( self::enabled() ) {
			$value = get_post_meta( $post->ID, '_codeixe_canonical', true );
			if ( $value ) { return $value; }
		}
		return $url;
	}

	public static function robots( $robots ) {
		if ( self::enabled() && is_singular() ) {
			$data = self::get( get_queried_object_id() );
			foreach ( array( 'noindex', 'nofollow' ) as $key ) {
				if ( '1' === $data[ $key ] ) { $robots[ $key ] = true; }
			}
		}
		return $robots;
	}

	public static function head() {
		if ( ! self::enabled() || ! is_singular() || post_password_required() ) { return; }
		$id = get_queried_object_id();
		$data = self::get( $id );
		$title = $data['seo_title'] ?: get_the_title( $id );
		if ( $data['description'] ) {
			echo '<meta name="description" content="' . esc_attr( $data['description'] ) . '">' . "\n";
		}
		$tags = array( 'og:title' => $title, 'og:description' => $data['description'], 'og:url' => get_permalink( $id ), 'og:type' => 'article' );
		$image = get_the_post_thumbnail_url( $id, 'large' );
		if ( $image ) { $tags['og:image'] = $image; }
		foreach ( $tags as $key => $value ) {
			if ( $value ) { echo '<meta property="' . esc_attr( $key ) . '" content="' . esc_attr( $value ) . '">' . "\n"; }
		}
		echo '<meta name="twitter:card" content="summary">' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
	}
}
