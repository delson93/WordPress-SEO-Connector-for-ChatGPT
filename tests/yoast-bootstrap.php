<?php
// Exercise Yoast's REST presentation context, as used by the connector endpoint.
$_SERVER['HTTPS'] = 'on';
$_SERVER['HTTP_HOST'] = 'example.org';
$_SERVER['REQUEST_URI'] = '/wp-json/codeixe-seo/v1/status';
if (!defined('REST_REQUEST')) { define('REST_REQUEST', true); }
$wordpress_load = getenv('CODEIXE_WP_LOAD');
if (!$wordpress_load || !is_file($wordpress_load)) { throw new RuntimeException('Set CODEIXE_WP_LOAD to a disposable WordPress wp-load.php'); }
require $wordpress_load;
