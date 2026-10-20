<?php
/**
 * Plugin Name: CodeIXE WordPress SEO Connector
 * Plugin URI: https://codeixe.com/
 * Description: Authenticated SEO audits and approval-based changes for compatible AI clients.
 * Version: 0.2.0
 * Requires at least: 6.9
 * Requires PHP: 8.2
 * Author: CodeIXE AI Solutions
 * Author URI: https://codeixe.com/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: codeixe-wp-seo-connector
 */

namespace CodeIXE\SEO;

defined( 'ABSPATH' ) || exit;
define( 'CODEIXE_SEO_FILE', __FILE__ );
foreach ( array( 'class-plugin', 'class-metadata', 'class-audit', 'class-changes', 'class-api', 'class-admin', 'class-abilities', 'class-actions' ) as $file ) {
	require_once __DIR__ . '/includes/' . $file . '.php';
}
Plugin::boot();
