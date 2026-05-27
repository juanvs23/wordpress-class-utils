<?php
/**
 * PHPUnit bootstrap for Coltman Framework unit tests.
 *
 * Load order:
 *   1. Composer autoload
 *   2. WordPress constants
 *   3. WordPress class stubs (WP_Term, WP_User)
 *   4. WordPress function stubs (Stubs/wordpress.php)
 *   5. Source files (file-level WP calls are safe because stubs are already defined)
 */

require dirname(__DIR__) . '/vendor/autoload.php';

// ── Constants ─────────────────────────────────────────────────────────────────
define('ABSPATH',            '/var/www/html/aniillosapedido/');
define('WPINC',              'wp-includes');
define('WP_PLUGIN_DIR',      '/var/www/html/aniillosapedido/wp-content/plugins');
define('WPMU_PLUGIN_DIR',    '/var/www/html/aniillosapedido/wp-content/mu-plugins');
define('COLTMAN_TEXT_DOMAIN', 'coltman');
define('COLTMAN_ASSETS_URL',  'https://example.com/wp-content/themes/anillosdepedida/classes/assets');
define('COLTMAN_DIR',         dirname(__DIR__));

// ── WP globals ───────────────────────────────────────────────────────────────
$GLOBALS['wpdb'] = null; // input-fields.php declares `global $wpdb` even though it's unused there

// ── WP class stubs ────────────────────────────────────────────────────────────
if (!class_exists('WP_Term')) {
    class WP_Term {
        public int    $term_id  = 0;
        public string $name     = '';
        public string $slug     = '';
        public function __construct(array $data = []) {
            foreach ($data as $k => $v) {
                $this->$k = $v;
            }
        }
    }
}

if (!class_exists('WP_User')) {
    class WP_User {
        public int    $ID    = 0;
        public string $data  = '';
        public function __construct(int $id = 0) {
            $this->ID = $id;
        }
    }
}

// ── WordPress function stubs ──────────────────────────────────────────────────
require __DIR__ . '/Stubs/wordpress.php';

// ── Source files ──────────────────────────────────────────────────────────────
// Load individually (not via class.php, which has add_action at file scope inside a namespace)
$classes = dirname(__DIR__);

require $classes . '/input-fields.php';
require $classes . '/class-post-types.php';
require $classes . '/class-taxonomy.php';
require $classes . '/class-metabox.php';
require $classes . '/class-termeta.php';
require $classes . '/class-usermetabox.php';

// utils/utils.php itself requires read-time.php and navigations_archors.php
require $classes . '/utils/utils.php';
