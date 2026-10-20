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
