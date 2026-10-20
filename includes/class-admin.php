<?php
namespace CodeIXE\SEO;

defined( 'ABSPATH' ) || exit;

final class Admin {
	public static function menu() {
		add_menu_page( 'CodeIXE SEO', 'CodeIXE SEO', 'manage_options', 'codeixe-seo', array( self::class, 'page' ), 'dashicons-search' );
	}

	public static function assets( $hook ) {
		if ( 'toplevel_page_codeixe-seo' !== $hook ) { return; }
		wp_enqueue_script( 'codeixe-seo-admin', plugins_url( 'assets/admin.js', CODEIXE_SEO_FILE ), array(), '0.1.0', true );
		wp_localize_script( 'codeixe-seo-admin', 'codeixeSEO', array( 'root' => rest_url( 'codeixe-seo/v1/' ), 'nonce' => wp_create_nonce( 'wp_rest' ) ) );
		wp_enqueue_style( 'codeixe-seo-admin', plugins_url( 'assets/admin.css', CODEIXE_SEO_FILE ), array(), '0.1.0' );
	}

	public static function form_start( $operation ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'codeixe_seo_action' );
		echo '<input type="hidden" name="action" value="codeixe_seo_action"><input type="hidden" name="operation" value="' . esc_attr( $operation ) . '">';
	}

	public static function action() {
		if ( ! current_user_can( 'manage_options' ) || ! wp_get_session_token() ) { wp_die( 'An interactive administrator session is required.', '', array( 'response' => 403 ) ); }
		check_admin_referer( 'codeixe_seo_action' );
		$operation = isset( $_POST['operation'] ) ? sanitize_key( wp_unslash( $_POST['operation'] ) ) : '';
		if ( 'settings' === $operation ) {
			$settings = array();
			foreach ( array( 'enabled', 'readonly', 'fallback', 'cleanup' ) as $key ) { $settings[ $key ] = isset( $_POST[ $key ] ); }
			update_option( 'codeixe_seo_settings', $settings, false );
		} elseif ( in_array( $operation, array( 'apply', 'rollback' ), true ) ) {
			$result = Changes::apply( absint( $_POST['id'] ?? 0 ), 'rollback' === $operation );
			if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ), '', array( 'back_link' => true ) ); }
			if ( 'failed' === $result['state'] ) { wp_die( 'Verification failed. Review the saved snapshots and actual values in Change History before making further changes.', '', array( 'back_link' => true ) ); }
		} else { wp_die( 'Unsupported action.', '', array( 'response' => 400 ) ); }
		wp_safe_redirect( admin_url( 'admin.php?page=codeixe-seo' ) );
		exit;
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$settings = Plugin::settings();
		echo '<div class="wrap codeixe-seo"><h1>CodeIXE WordPress SEO Connector</h1><p>Audit, review, approve, and verify. Version 0.1.0 is an MVP: see README for supported checks and limitations.</p>';
		echo '<nav aria-label="Sections"><a href="#codeixe-settings">Settings</a> | <a href="#codeixe-audit">SEO Audit</a> | <a href="#codeixe-propose">Optimization</a> | <a href="#codeixe-history">Change History</a> | <a href="#codeixe-connections">AI Connections</a></nav>';
		echo '<h2 id="codeixe-settings">Settings</h2>';
		self::form_start( 'settings' );
		foreach ( array( 'enabled' => 'Enable authenticated REST and MCP services', 'readonly' => 'Read-only mode (blocks proposals, apply and rollback)', 'fallback' => 'Enable minimal metadata output when no SEO provider is active. Check theme output first.', 'cleanup' => 'Delete connector settings, metadata and history when uninstalling. Content edits remain.' ) as $key => $label ) {
			echo '<p><label><input type="checkbox" name="' . esc_attr( $key ) . '" value="1" ' . checked( $settings[ $key ], true, false ) . '> ' . esc_html( $label ) . '</label></p>';
		}
		submit_button( 'Save settings' );
		echo '</form><h2 id="codeixe-audit">SEO Audit</h2><p>Scan ten published objects per page. Findings from stored content are provisional until rendered output is checked.</p><p><label>Type <select id="codeixe-type"><option value="post">Posts</option><option value="page">Pages</option><option value="attachment">Images</option></select></label> <label>Page <input id="codeixe-page" type="number" min="1" value="1"></label> <button class="button" id="codeixe-scan">Audit batch</button> <button class="button" id="codeixe-export" disabled>Export JSON</button> <button class="button" id="codeixe-csv" disabled>Export CSV</button></p><p id="codeixe-summary" role="status" aria-live="polite"></p><div id="codeixe-results"></div>';
		echo '<h2 id="codeixe-propose">Posts, images and internal links</h2><p>Submit exact replacement fields as JSON. Content changes can improve headings and add relevant internal links. For image ALT use an attachment ID. Review image meaning before writing ALT.</p><label>Object ID <input id="codeixe-id" type="number" min="1"></label> <button class="button" id="codeixe-read">Read object</button> <button class="button" id="codeixe-rendered">Audit public HTML</button><pre id="codeixe-object"></pre><label for="codeixe-changes">Proposed fields</label><textarea id="codeixe-changes" rows="6" class="large-text code" placeholder="{&quot;description&quot;:&quot;An accurate summary of this page.&quot;}"></textarea><button class="button button-primary" id="codeixe-submit">Create proposal for review</button><p id="codeixe-proposal-status" role="status"></p>';
		echo '<h2 id="codeixe-history">Change History</h2><p>Approve only after reviewing every field. Rollback refuses to overwrite later edits. Purge your page cache after applying changes, then audit the rendered page.</p>';
		$page = max( 1, absint( $_GET['history_page'] ?? 1 ) ); // Read-only pagination.
		foreach ( Changes::history( $page ) as $record ) {
			echo '<article><h3>Change #' . esc_html( $record['id'] ) . ' for object #' . esc_html( $record['target'] ) . ': ' . esc_html( $record['state'] ) . '</h3><table class="widefat striped"><thead><tr><th>Field</th><th>Before</th><th>After</th></tr></thead><tbody>';
			foreach ( $record['before'] as $key => $value ) { echo '<tr><th scope="row">' . esc_html( $key ) . '</th><td><pre>' . esc_html( $value ) . '</pre></td><td><pre>' . esc_html( $record['after'][ $key ] ) . '</pre></td></tr>'; }
			echo '</tbody></table>';
			if ( in_array( $record['state'], array( 'pending', 'applied' ), true ) && ! $settings['readonly'] && $settings['enabled'] ) {
				$operation = 'pending' === $record['state'] ? 'apply' : 'rollback';
				self::form_start( $operation );
				echo '<input type="hidden" name="id" value="' . esc_attr( $record['id'] ) . '">';
				submit_button( 'apply' === $operation ? 'Approve and apply these exact changes' : 'Approve rollback to the before values', 'secondary' );
				echo '</form>';
			}
			if ( isset( $record['actual'] ) && 'failed' === $record['state'] ) { echo '<pre>' . esc_html( wp_json_encode( $record['actual'], JSON_PRETTY_PRINT ) ) . '</pre>'; }
			echo '</article>';
		}
		echo '<p><a href="' . esc_url( add_query_arg( array( 'page' => 'codeixe-seo', 'history_page' => max( 1, $page - 1 ) ), admin_url( 'admin.php' ) ) ) . '#codeixe-history">Previous history page</a> | <a href="' . esc_url( add_query_arg( array( 'page' => 'codeixe-seo', 'history_page' => $page + 1 ), admin_url( 'admin.php' ) ) ) . '#codeixe-history">Next history page</a></p>';
		echo '<h2 id="codeixe-connections">AI Connections and diagnostics</h2><p>Create a dedicated editor account and an Application Password under its WordPress profile. Store credentials in your client secret store, never in a chat. Revoke the password to revoke that credential, or disable services above to block this connector.</p><p>REST endpoint: <code>' . esc_html( rest_url( 'codeixe-seo/v1/' ) ) . '</code></p><p>Optional MCP requires the official WordPress MCP Adapter plugin and a compatible authenticated client. ChatGPT remote integration additionally requires compatible OAuth infrastructure; this plugin does not provide an OAuth server. See docs/connections.md.</p><pre>' . esc_html( wp_json_encode( API::status(), JSON_PRETTY_PRINT ) ) . '</pre><p>Developed by <a href="https://codeixe.com/">CodeIXE AI Solutions</a>. No tracking or mandatory subscription.</p></div>';
	}
}
