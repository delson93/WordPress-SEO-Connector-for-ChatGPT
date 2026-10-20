<?php
namespace CodeIXE\SEO;

defined( 'ABSPATH' ) || exit;

final class Changes {
	const POST_FIELDS = array( 'post_title', 'post_content', 'post_excerpt' );

	public static function snapshot( $id, $keys ) {
		$post = get_post( $id );
		$data = array();
		foreach ( $keys as $key ) {
			$data[ $key ] = in_array( $key, self::POST_FIELDS, true ) ? $post->$key : (string) get_post_meta( $id, 'alt' === $key ? '_wp_attachment_image_alt' : '_codeixe_' . $key, true );
		}
		return $data;
	}

	public static function validate( $post, $changes ) {
		if ( ! is_array( $changes ) || ! $changes || count( $changes ) > 9 ) { return new \WP_Error( 'codeixe_fields', 'Provide a nonempty object of supported fields.', array( 'status' => 400 ) ); }
		$clean = array();
		foreach ( $changes as $key => $value ) {
			if ( ! in_array( $key, array_merge( self::POST_FIELDS, Metadata::FIELDS, array( 'alt' ) ), true ) || ! is_string( $value ) || strlen( $value ) > ( 'post_content' === $key ? 200000 : 2000 ) ) {
				return new \WP_Error( 'codeixe_field', 'Unsupported field, type or size.', array( 'status' => 400 ) );
			}
			if ( 'attachment' === $post->post_type && 'alt' !== $key ) { return new \WP_Error( 'codeixe_attachment', 'Only ALT changes are supported for attachments.', array( 'status' => 400 ) ); }
			if ( 'alt' === $key && ( 'attachment' !== $post->post_type || ! wp_attachment_is_image( $post ) ) ) { return new \WP_Error( 'codeixe_image', 'ALT requires an image attachment.', array( 'status' => 400 ) ); }
			if ( in_array( $key, Metadata::FIELDS, true ) && ! Metadata::enabled() ) { return new \WP_Error( 'codeixe_provider', 'Enable fallback metadata only when no other SEO provider manages it. Third-party metadata writes are not supported.', array( 'status' => 409 ) ); }
			if ( in_array( $key, array( 'noindex', 'nofollow' ), true ) && ! in_array( $value, array( '', '1' ), true ) ) { return new \WP_Error( 'codeixe_robots', 'Robots values must be empty or 1.', array( 'status' => 400 ) ); }
			if ( 'canonical' === $key && '' !== $value && ( 'https' !== wp_parse_url( $value, PHP_URL_SCHEME ) || wp_parse_url( $value, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) || wp_parse_url( $value, PHP_URL_USER ) || wp_parse_url( $value, PHP_URL_PASS ) ) ) { return new \WP_Error( 'codeixe_canonical', 'Canonical must be an HTTPS URL on this site.', array( 'status' => 400 ) ); }
			$clean[ $key ] = in_array( $key, array( 'post_content', 'post_excerpt' ), true ) ? wp_kses_post( $value ) : sanitize_text_field( $value );
			if ( 'canonical' === $key ) { $clean[ $key ] = esc_url_raw( $value, array( 'https' ) ); }
		}
		return $clean;
	}

	public static function propose( $id, $changes ) {
		$permission = Plugin::permission();
		if ( is_wp_error( $permission ) ) { return $permission; }
		if ( Plugin::settings()['readonly'] ) { return new \WP_Error( 'codeixe_readonly', 'Read-only mode prevents proposals and changes.', array( 'status' => 403 ) ); }
		$limit = Plugin::throttle( 'propose', 20 );
		if ( is_wp_error( $limit ) ) { return $limit; }
		$post = Plugin::target( $id );
		if ( is_wp_error( $post ) ) { return $post; }
		$after = self::validate( $post, $changes );
		if ( is_wp_error( $after ) ) { return $after; }
		$before = self::snapshot( $id, array_keys( $after ) );
		if ( $before === $after ) { return new \WP_Error( 'codeixe_noop', 'No changes were proposed.', array( 'status' => 400 ) ); }
		$record = array( 'target' => $id, 'before' => $before, 'after' => $after, 'state' => 'pending', 'created_by' => get_current_user_id(), 'created_at' => gmdate( 'c' ) );
		$change_id = wp_insert_post( wp_slash( array( 'post_type' => 'codeixe_change', 'post_status' => 'private', 'post_title' => 'SEO change for #' . $id, 'post_author' => get_current_user_id() ) ), true );
		if ( is_wp_error( $change_id ) ) { return $change_id; }
		$record['id'] = $change_id;
		$saved = self::save( $record );
		if ( is_wp_error( $saved ) ) { wp_delete_post( $change_id, true ); return $saved; }
		return $record;
	}

	public static function get( $id ) {
		$post = get_post( $id );
		if ( ! $post || 'codeixe_change' !== $post->post_type ) { return new \WP_Error( 'codeixe_change', 'Change not found.', array( 'status' => 404 ) ); }
		$record = json_decode( (string) get_post_meta( $id, '_codeixe_record', true ), true );
		if ( ! is_array( $record ) || empty( $record['target'] ) ) { return new \WP_Error( 'codeixe_record', 'Invalid change record.', array( 'status' => 500 ) ); }
		$target = Plugin::target( $record['target'] );
		if ( is_wp_error( $target ) ) { return $target; }
		$record['id'] = $id;
		return $record;
	}

	public static function save( $record ) {
		$json = wp_json_encode( $record );
		if ( ! is_string( $json ) ) { return new \WP_Error( 'codeixe_json', 'Could not encode change history.' ); }
		update_post_meta( $record['id'], '_codeixe_record', wp_slash( $json ) );
		return get_post_meta( $record['id'], '_codeixe_record', true ) === $json ? true : new \WP_Error( 'codeixe_storage', 'Could not save change history.' );
	}

	public static function apply( $id, $rollback = false ) {
		// Only the nonce-protected, interactive administrator form calls this method.
		if ( ! current_user_can( 'manage_options' ) || ! is_ssl() || Plugin::settings()['readonly'] || ! Plugin::settings()['enabled'] ) {
			return new \WP_Error( 'codeixe_approval', 'Administrator approval, HTTPS and write mode are required.', array( 'status' => 403 ) );
		}
		$record = self::get( $id );
		if ( is_wp_error( $record ) ) { return $record; }
		$lock = 'codeixe_lock_' . $record['target'];
		if ( ! add_option( $lock, time(), '', false ) ) { return new \WP_Error( 'codeixe_locked', 'Another operation holds this object lock. See recovery documentation.', array( 'status' => 409 ) ); }
		try {
			$record = self::get( $id );
			if ( is_wp_error( $record ) ) { return $record; }
			if ( ( $rollback ? 'applied' : 'pending' ) !== $record['state'] ) { return new \WP_Error( 'codeixe_state', 'This change is not in an applicable state.', array( 'status' => 409 ) ); }
			$expected = $rollback ? $record['after'] : $record['before'];
			$desired = $rollback ? $record['before'] : $record['after'];
			if ( self::snapshot( $record['target'], array_keys( $expected ) ) !== $expected ) { return new \WP_Error( 'codeixe_conflict', 'Content changed after the snapshot. Create a new proposal.', array( 'status' => 409 ) ); }
			$valid = self::validate( get_post( $record['target'] ), $desired );
			if ( is_wp_error( $valid ) ) { return $valid; }
			// Keep the exact original snapshot for rollback; it originated in WordPress.
			$record['state'] = $rollback ? 'rolling_back' : 'applying';
			$record['approved_by'] = get_current_user_id();
			$record['approved_at'] = gmdate( 'c' );
			$saved = self::save( $record );
			if ( is_wp_error( $saved ) ) { return $saved; }
			$post_data = array( 'ID' => $record['target'] );
			foreach ( $desired as $key => $value ) {
				if ( in_array( $key, self::POST_FIELDS, true ) ) { $post_data[ $key ] = $value; }
			}
			if ( count( $post_data ) > 1 ) {
				$result = wp_update_post( wp_slash( $post_data ), true );
				if ( is_wp_error( $result ) ) { $record['state'] = 'failed'; self::save( $record ); return $result; }
			}
			foreach ( $desired as $key => $value ) {
				if ( ! in_array( $key, self::POST_FIELDS, true ) ) { update_post_meta( $record['target'], 'alt' === $key ? '_wp_attachment_image_alt' : '_codeixe_' . $key, wp_slash( $value ) ); }
			}
			$actual = self::snapshot( $record['target'], array_keys( $desired ) );
			$record['state'] = $actual === $desired ? ( $rollback ? 'rolled_back' : 'applied' ) : 'failed';
			$record['completed_at'] = gmdate( 'c' );
			$record['actual'] = $actual;
			$saved = self::save( $record );
			if ( is_wp_error( $saved ) ) { return new \WP_Error( 'codeixe_history', 'Content may have changed but history could not be finalized. Inspect the record before retrying.' ); }
			clean_post_cache( $record['target'] );
			return $record;
		} finally {
			delete_option( $lock );
		}
	}

	public static function history( $page = 1 ) {
		$records = get_posts( array( 'post_type' => 'codeixe_change', 'post_status' => 'private', 'posts_per_page' => 20, 'paged' => max( 1, $page ), 'orderby' => 'ID', 'order' => 'DESC' ) );
		$result = array();
		foreach ( $records as $post ) {
			$record = self::get( $post->ID );
			if ( ! is_wp_error( $record ) ) { $result[] = $record; }
		}
		return $result;
	}
}
