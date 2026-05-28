<?php
/**
 * WordPress function stubs for unit testing.
 *
 * Loaded once in bootstrap BEFORE source files. Uses a global spy/stub registry
 * so tests can capture calls and override return values without needing Patchwork.
 *
 * Usage in tests:
 *   $this->spyCalls('update_post_meta')  → array of recorded calls
 *   $this->setStub('get_post_meta', 'v') → force return value
 *   $this->setFlag('is_admin', true)     → toggle context flags
 */

// ── Spy / stub registry ────────────────────────────────────────────────────────
$GLOBALS['_coltman_spy']   = [];
$GLOBALS['_coltman_stubs'] = [];
$GLOBALS['_coltman_flags'] = [
    'is_admin'         => false,
    'metadata_exists'  => false,
    'current_user_can' => true,
    'wp_verify_nonce'  => true,
];

function _coltman_spy(string $fn, array $args): void {
    $GLOBALS['_coltman_spy'][$fn][] = $args;
}
function _coltman_stub(string $fn, $default = null) {
    return array_key_exists($fn, $GLOBALS['_coltman_stubs'])
        ? $GLOBALS['_coltman_stubs'][$fn]
        : $default;
}

// ── Hook registration ─────────────────────────────────────────────────────────
function add_action(string $hook, $callback, int $priority = 10, int $accepted_args = 1): void {
    _coltman_spy('add_action', compact('hook', 'callback', 'priority'));
}
function add_filter(string $hook, $callback, int $priority = 10, int $accepted_args = 1): void {
    _coltman_spy('add_filter', compact('hook', 'callback', 'priority'));
}
function remove_filter(): void {}
function remove_action(): void {}

// ── i18n ──────────────────────────────────────────────────────────────────────
function __(string $text, string $domain = ''): string { return $text; }
function _x(string $text, string $context = '', string $domain = ''): string { return $text; }
function _e(string $text, string $domain = ''): void { echo $text; }
function _n(string $single, string $plural, int $number, string $domain = ''): string {
    return $number === 1 ? $single : $plural;
}
function esc_html__(string $text, string $domain = ''): string { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }

// ── Escaping ──────────────────────────────────────────────────────────────────
function esc_attr($text): string      { return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'); }
function esc_attr_e(string $text, string $domain = ''): void { echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function esc_html_e(string $text, string $domain = ''): void { echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function esc_attr__(string $text, string $domain = ''): string { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function esc_html($text): string      { return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'); }
function esc_url($url): string   { return filter_var((string) $url, FILTER_SANITIZE_URL) ?: ''; }
function esc_url_raw($url): string { return (string) $url; }
function esc_textarea($text): string { return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'); }

// ── Sanitization ──────────────────────────────────────────────────────────────
function sanitize_text_field(string $str): string { return strip_tags(trim($str)); }
function sanitize_email(string $email): string {
    return filter_var($email, FILTER_SANITIZE_EMAIL) ?: '';
}
function sanitize_textarea_field(string $str): string { return $str; }
function sanitize_title(string $title): string {
    return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-'));
}
function wp_filter_post_kses(string $content): string { return $content; }

// ── Context flags ─────────────────────────────────────────────────────────────
function is_admin(): bool { return $GLOBALS['_coltman_flags']['is_admin'] ?? false; }
function current_user_can(string $cap, ...$args): bool {
    return $GLOBALS['_coltman_flags']['current_user_can'] ?? true;
}

// ── Post meta ─────────────────────────────────────────────────────────────────
function metadata_exists(string $meta_type, int $object_id, string $meta_key): bool {
    return $GLOBALS['_coltman_flags']['metadata_exists'] ?? false;
}
function get_post_meta(int $post_id, string $key = '', bool $single = false) {
    _coltman_spy('get_post_meta', compact('post_id', 'key', 'single'));
    return _coltman_stub('get_post_meta', '');
}
function update_post_meta(int $post_id, string $key, $value): void {
    _coltman_spy('update_post_meta', compact('post_id', 'key', 'value'));
}
function delete_post_meta(int $post_id, string $key): void {}

// ── Term meta ─────────────────────────────────────────────────────────────────
function get_term_meta(int $term_id, string $key = '', bool $single = false) {
    _coltman_spy('get_term_meta', compact('term_id', 'key', 'single'));
    return _coltman_stub('get_term_meta', '');
}
function update_term_meta(int $term_id, string $key, $value): void {
    _coltman_spy('update_term_meta', compact('term_id', 'key', 'value'));
}

// ── User meta ─────────────────────────────────────────────────────────────────
function get_user_meta(int $user_id, string $key = '', bool $single = false) {
    _coltman_spy('get_user_meta', compact('user_id', 'key', 'single'));
    return _coltman_stub('get_user_meta', '');
}
function update_user_meta(int $user_id, string $key, $value): void {
    _coltman_spy('update_user_meta', compact('user_id', 'key', 'value'));
}

// ── Post / taxonomy registration ──────────────────────────────────────────────
function register_post_type(string $post_type, array $args = []): void {
    _coltman_spy('register_post_type', compact('post_type', 'args'));
}
function register_taxonomy(string $taxonomy, $post_types, array $args = []): void {
    _coltman_spy('register_taxonomy', compact('taxonomy', 'post_types', 'args'));
}

// ── Nonce ─────────────────────────────────────────────────────────────────────
function wp_create_nonce($action = -1): string {
    return 'test_nonce_' . $action;
}
function wp_verify_nonce($nonce, $action = -1): bool {
    _coltman_spy('wp_verify_nonce', compact('nonce', 'action'));
    return $GLOBALS['_coltman_flags']['wp_verify_nonce'] ?? true;
}
function wp_nonce_field($action = -1, $name = '_wpnonce', $referer = true, $echo = true): string {
    $nonce = wp_create_nonce($action);
    $html  = '<input type="hidden" id="' . esc_attr((string) $name) . '" name="' . esc_attr((string) $name) . '" value="' . esc_attr($nonce) . '">';
    if ($echo) echo $html;
    return $html;
}

// ── Metabox ───────────────────────────────────────────────────────────────────
function add_meta_box(
    string $id, string $title, callable $callback,
    $screen = null, string $context = 'advanced', string $priority = 'default'
): void {
    _coltman_spy('add_meta_box', compact('id', 'title', 'callback', 'screen', 'context', 'priority'));
}

// ── Post queries ──────────────────────────────────────────────────────────────
function get_posts(array $args = []): array {
    _coltman_spy('get_posts', $args);
    return _coltman_stub('get_posts', []);
}
function get_terms($taxonomy, array $args = []): array {
    _coltman_spy('get_terms', compact('taxonomy', 'args'));
    return _coltman_stub('get_terms', []);
}
function get_post($post = null, string $output = 'OBJECT', string $filter = 'raw') {
    _coltman_spy('get_post', compact('post'));
    return _coltman_stub('get_post', null);
}
function get_term($term, string $taxonomy = '', string $output = 'OBJECT', string $filter = 'raw') {
    _coltman_spy('get_term', compact('term'));
    return _coltman_stub('get_term', null);
}
function is_wp_error($thing): bool { return false; }
function get_the_content($more_link_text = null, bool $strip_teaser = false): string {
    return _coltman_stub('get_the_content', '');
}

// ── Editor / media ────────────────────────────────────────────────────────────
function wp_editor(string $content, string $editor_id, array $settings = []): void {
    _coltman_spy('wp_editor', compact('content', 'editor_id', 'settings'));
}
function wp_enqueue_media(array $args = []): void { _coltman_spy('wp_enqueue_media', $args); }
function wp_enqueue_script(string $handle, $src = '', array $deps = [], $ver = false, bool $in_footer = false): void {}
function wp_enqueue_style(string $handle, $src = '', array $deps = [], $ver = false, string $media = 'all'): void {}
function wp_register_script(string $handle, $src, array $deps = [], $ver = false, bool $in_footer = false): void {}
function wp_register_style(string $handle, $src, array $deps = [], $ver = false, string $media = 'all'): void {}
function wp_color_picker_scripts(): void {}

// ── String / content utils ────────────────────────────────────────────────────
function wp_trim_words(string $text, int $num_words = 55, ?string $more = null): string {
    if ($more === null) {
        $more = '&hellip;';
    }
    $words = preg_split('/[\s\n\t]+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
    if (count($words) <= $num_words) {
        return trim($text);
    }
    return implode(' ', array_slice($words, 0, $num_words)) . $more;
}
function wp_strip_all_tags(string $string, bool $remove_breaks = false): string {
    return strip_tags($string);
}
function strip_shortcodes(string $content): string { return $content; }
function wp_filter_content_tags(string $content): string { return $content; }
function do_shortcode(string $content): string { return $content; }
function plugins_url(string $path = '', string $plugin = ''): string {
    return 'https://example.com/wp-content/themes/test/classes/' . ltrim($path, '/');
}
function trailingslashit(string $str): string { return rtrim($str, '/\\') . '/'; }
function wp_normalize_path(string $path): string { return str_replace('\\', '/', $path); }
function get_theme_root(): string { return '/var/www/html/themes'; }
function determine_locale(): string { return 'en_US'; }
function load_textdomain(string $domain, string $mofile): bool { return true; }
function wp_unslash(mixed $value): mixed { return is_string($value) ? stripslashes($value) : $value; }
function wp_kses_post(string $data): string { return $data; }

// Gutenberg / REST API stubs
$_coltman_registered_meta = [];
function register_post_meta(string $post_type, string $meta_key, array $args = []): bool {
    global $_coltman_registered_meta;
    $_coltman_registered_meta[] = [ 'post_type' => $post_type, 'meta_key' => $meta_key, 'args' => $args ];
    return true;
}
function get_current_screen(): ?object {
    return null;
}
function wp_localize_script(string $handle, string $object_name, array $l10n): bool { return true; }
function wp_json_encode(mixed $data, int $flags = 0, int $depth = 512): string|false { return json_encode($data, $flags, $depth); }

// Options API stubs used by get_group_schema()
$_coltman_options = [];
function get_option(string $key, $default = false): mixed {
    global $_coltman_options;
    return $_coltman_options[$key] ?? $default;
}
function update_option(string $key, mixed $value, bool|string $autoload = true): bool {
    global $_coltman_options;
    $_coltman_options[$key] = $value;
    return true;
}
