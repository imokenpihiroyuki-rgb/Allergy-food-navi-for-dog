<?php
/**
 * Plugin Name: Allergy Food Navi Core
 * Description: Allergy Food Navi related business logic migrated from theme functions.php.
 * Version: 0.2.0
 * Author: Site Maintenance Team
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: allergy-food-navi
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-afn-config.php';
require_once __DIR__ . '/includes/class-afn-request-context.php';
require_once __DIR__ . '/includes/class-afn-acf.php';
require_once __DIR__ . '/includes/class-afn-utils.php';
require_once __DIR__ . '/includes/class-afn-query-builder.php';
require_once __DIR__ . '/includes/class-afn-frontend.php';
require_once __DIR__ . '/includes/class-afn-redirect.php';
require_once __DIR__ . '/includes/class-afn-quick-edit.php';
require_once __DIR__ . '/includes/class-afn-bootstrap.php';

\AFN\Bootstrap::init();
