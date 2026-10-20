<?php
namespace CodeIXE\SEO;

defined( 'ABSPATH' ) || exit;

final class Audit {
	public static function issue( $code, $severity, $evidence, $solution, $scope = 'stored_content' ) {
		return compact( 'code', 'severity', 'evidence', 'solution', 'scope' );
	}

	public static function analyze( $html, $title, $description ) {
		$issues = array();
		foreach ( array( 'title' => array( $title, 30, 60 ), 'description' => array( $description, 50, 160 ) ) as $field => $rule ) {
			$length = function_exists( 'mb_strlen' ) ? mb_strlen( $rule[0] ) : strlen( $rule[0] );
			if ( 0 === $length || $length < $rule[1] || $length > $rule[2] ) {
				$issues[] = self::issue( $field . '_length', 0 === $length ? 'warning' : 'info', array( 'length' => $length ), 'Write an accurate, useful ' . $field . '. Length ranges are editorial guidance, not ranking rules.' );
			}
		}
		$parser = new \WP_HTML_Tag_Processor( $html );
		$heading = 0;
		$h1 = 0;
		$images = array();
		$links = array();
		while ( $parser->next_tag() ) {
			$tag = $parser->get_tag();
			if ( preg_match( '/^H([1-6])$/', $tag, $match ) ) {
				$level = (int) $match[1];
				if ( 1 === $level ) { ++$h1; }
				if ( $heading && $level > $heading + 1 ) { $issues[] = self::issue( 'heading_skip', 'info', $heading . ' to ' . $level, 'Review heading nesting for clarity and accessibility.' ); }
				$heading = $level;
			}
			if ( 'IMG' === $tag && null === $parser->get_attribute( 'alt' ) ) { $images[] = (string) $parser->get_attribute( 'src' ); }
			if ( 'A' === $tag && count( $links ) < 100 ) { $links[] = (string) $parser->get_attribute( 'href' ); }
		}
		if ( $images ) { $issues[] = self::issue( 'missing_alt', 'warning', array_slice( $images, 0, 50 ), 'Describe meaningful images; use empty ALT for decorative images. Do not invent image descriptions.' ); }
		if ( $h1 > 1 ) { $issues[] = self::issue( 'multiple_content_h1', 'info', $h1, 'Review the rendered page and use a clear main heading.' ); }
		$words = str_word_count( wp_strip_all_tags( $html ) );
		if ( $words < 200 ) { $issues[] = self::issue( 'short_content', 'info', array( 'approximate_latin_word_count' => $words ), 'Review whether the content satisfies its purpose. Short content is not automatically poor content.' ); }
		return array( 'issues' => $issues, 'links' => array_values( array_unique( $links ) ), 'content_h1_count' => $h1 );
	}

	public static function post( $id, $rendered = false ) {
		$post = Plugin::target( $id );
		if ( is_wp_error( $post ) ) { return $post; }
		$data = Metadata::get( $id );
		$result = self::analyze( $post->post_content, $data['seo_title'] ?: $post->post_title, $data['description'] );
		$result['id'] = $id;
		$result['url'] = get_permalink( $id );
		$result['metadata_source'] = Metadata::enabled() ? 'codeixe_fallback' : 'stored_codeixe_values_not_active';
		$result['providers'] = Metadata::providers();
		$result['limitations'] = array( 'Stored content excludes theme output, shortcodes and dynamic blocks.', 'No Google rankings, indexing status or traffic data are available.', 'Metadata findings must be checked against rendered output when another provider is active.', 'Attachment ALT updates do not rewrite ALT already embedded in post content.' );
		if ( ! get_option( 'blog_public' ) ) { $result['issues'][] = self::issue( 'site_discourages_indexing', 'warning', 'blog_public=0', 'Confirm whether this site should be public before changing Reading settings.', 'site_settings' ); }
		if ( ! get_option( 'permalink_structure' ) ) { $result['issues'][] = self::issue( 'plain_permalinks', 'info', 'Plain permalink structure', 'Plan redirects before changing permalink settings.', 'site_settings' ); }
		if ( $rendered ) {
			if ( 'publish' !== $post->post_status || $post->post_password || 'attachment' === $post->post_type ) {
				return new \WP_Error( 'codeixe_fetch', 'Only public, unprotected posts and pages can be fetched.', array( 'status' => 400 ) );
			}
			$fetch = self::fetch( $result['url'] );
			$result['rendered'] = is_wp_error( $fetch ) ? array( 'error' => $fetch->get_error_message() ) : self::rendered( $fetch );
		}
		foreach ( $result['issues'] as &$issue ) { $issue['url'] = $result['url']; }
		return $result;
	}

	public static function fetch( $url ) {
		if ( wp_parse_url( $url, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) || 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) ) {
			return new \WP_Error( 'codeixe_url', 'Only HTTPS URLs on this site can be fetched.' );
		}
		$limit = Plugin::throttle( 'fetch', 10 );
		if ( is_wp_error( $limit ) ) { return $limit; }
		$response = wp_safe_remote_get( $url, array( 'timeout' => 5, 'redirection' => 0, 'limit_response_size' => 1048576, 'cookies' => array(), 'headers' => array( 'Accept' => 'text/html' ) ) );
		if ( is_wp_error( $response ) ) { return $response; }
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) { return new \WP_Error( 'codeixe_http', 'Page did not return HTTP 200. Redirects are deliberately not followed.' ); }
		return wp_remote_retrieve_body( $response );
	}

	public static function rendered( $html ) {
		$tags = new \WP_HTML_Tag_Processor( $html );
		$meta = array();
		$canonicals = array();
		$h1 = 0;
		while ( $tags->next_tag() ) {
			if ( 'H1' === $tags->get_tag() ) { ++$h1; }
			if ( 'META' === $tags->get_tag() ) {
				$name = strtolower( (string) ( $tags->get_attribute( 'name' ) ?: $tags->get_attribute( 'property' ) ) );
				if ( in_array( $name, array( 'description', 'robots', 'og:title', 'og:description', 'twitter:card' ), true ) ) { $meta[ $name ][] = (string) $tags->get_attribute( 'content' ); }
			}
			if ( 'LINK' === $tags->get_tag() && 'canonical' === strtolower( (string) $tags->get_attribute( 'rel' ) ) ) { $canonicals[] = (string) $tags->get_attribute( 'href' ); }
		}
		$issues = array();
		if ( 1 !== $h1 ) { $issues[] = self::issue( 'rendered_h1', 'info', $h1, 'Review the main page heading.', 'rendered_html' ); }
		foreach ( array( 'description', 'og:title', 'og:description', 'twitter:card' ) as $name ) {
			if ( empty( $meta[ $name ] ) || count( $meta[ $name ] ) > 1 ) { $issues[] = self::issue( 'rendered_' . $name, 'warning', $meta[ $name ] ?? array(), 'Configure one metadata provider and verify its rendered output.', 'rendered_html' ); }
		}
		if ( 1 !== count( $canonicals ) ) { $issues[] = self::issue( 'canonical_count', 'warning', $canonicals, 'Review canonical output and duplicate providers.', 'rendered_html' ); }
		return array( 'scope' => 'rendered_html', 'issues' => $issues, 'metadata' => $meta, 'canonicals' => $canonicals, 'h1_count' => $h1, 'truncated_possible' => strlen( $html ) >= 1048576 );
	}
}
