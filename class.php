<?php
namespace Coltman\Framework;

// ── Requisitos mínimos ────────────────────────────────────────────────────────
//
//   PHP       >= 8.0   union types (array|bool) y str_starts_with()
//   WordPress >= 5.0   determine_locale() · show_in_rest · wp_normalize_path()
//   ext-iconv          formaturltext() en utils/utils.php usa iconv()
//
//   Para ejecutar las pruebas unitarias:
//   PHP       >= 8.1   (requisito de PHPUnit 10)
//   ext-dom, ext-mbstring, ext-xml, ext-xmlwriter
//
// ── Auto-detección de contexto ────────────────────────────────────────────────
//
// El framework detecta automáticamente si está siendo cargado desde un tema
// o desde un plugin inspeccionando la ruta del filesystem de este archivo.
// No es necesario configurar nada manualmente.
//
// Constantes resultantes (ambas de solo lectura, no requieren define previo):
//
//   COLTMAN_CONTEXT     → 'theme' | 'plugin' | 'mu-plugin' | 'unknown'
//   COLTMAN_ASSETS_URL  → URL absoluta a la carpeta assets/ de este módulo
//   COLTMAN_DIR         → Ruta absoluta del filesystem a la carpeta classes/
//
// Sobreescribir si es necesario (CDN, rutas no estándar) — ANTES del require:
//   define('COLTMAN_ASSETS_URL', 'https://cdn.ejemplo.com/coltman/assets');

if ( ! defined('COLTMAN_DIR') ) {
    define( 'COLTMAN_DIR', __DIR__ );
}

if ( ! defined('COLTMAN_CONTEXT') ) {
    $coltman_this_file  = wp_normalize_path( __FILE__ );
    $coltman_themes_dir = wp_normalize_path( trailingslashit( get_theme_root() ) );
    $coltman_plugin_dir = wp_normalize_path( trailingslashit( WP_PLUGIN_DIR ) );
    $coltman_mu_dir     = wp_normalize_path( trailingslashit( WPMU_PLUGIN_DIR ) );

    if ( str_starts_with( $coltman_this_file, $coltman_themes_dir ) ) {
        define( 'COLTMAN_CONTEXT', 'theme' );
    } elseif ( str_starts_with( $coltman_this_file, $coltman_plugin_dir ) ) {
        define( 'COLTMAN_CONTEXT', 'plugin' );
    } elseif ( str_starts_with( $coltman_this_file, $coltman_mu_dir ) ) {
        define( 'COLTMAN_CONTEXT', 'mu-plugin' );
    } else {
        define( 'COLTMAN_CONTEXT', 'unknown' );
    }

    unset( $coltman_this_file, $coltman_themes_dir, $coltman_plugin_dir, $coltman_mu_dir );
}

if ( ! defined('COLTMAN_ASSETS_URL') ) {
    // Calcula la URL relativa a WP_CONTENT_DIR para que funcione en temas,
    // plugins y mu-plugins sin depender de plugins_url() (que solo es fiable
    // dentro de WP_PLUGIN_DIR).
    $coltman_relative = str_replace(
        wp_normalize_path( WP_CONTENT_DIR ),
        '',
        wp_normalize_path( __DIR__ . '/assets' )
    );
    define( 'COLTMAN_ASSETS_URL', content_url( $coltman_relative ) );
    unset( $coltman_relative );
}

// ── Sistema de traducción ─────────────────────────────────────────────────────
//
// Text domain propio del framework. Todas las cadenas internas del módulo
// usan este dominio — independiente del text domain del tema o plugin que
// lo cargue.
//
// Los archivos de traducción viven en classes/languages/ con el formato:
//   coltman-{locale}.mo   (compilado, requerido en producción)
//   coltman-{locale}.po   (fuente editable)
//   coltman.pot           (plantilla para traductores)
//
// Para agregar un idioma: copiar coltman.pot → coltman-{locale}.po,
// traducir las cadenas y compilar con: msgfmt coltman-{locale}.po -o coltman-{locale}.mo

if ( ! defined('COLTMAN_TEXT_DOMAIN') ) {
    define( 'COLTMAN_TEXT_DOMAIN', 'coltman' );
}

// Closure en lugar de función con nombre para evitar el problema de namespace:
// una función definida aquí quedaría como Coltman\Framework\coltman_load_textdomain
// y WordPress no la encontraría al invocar el hook por su nombre simple.
add_action( 'init', static function() {
    $locale  = determine_locale();
    $mo_file = COLTMAN_DIR . '/languages/coltman-' . $locale . '.mo';
    if ( file_exists( $mo_file ) ) {
        load_textdomain( COLTMAN_TEXT_DOMAIN, $mo_file );
    }
}, 1 );
// ─────────────────────────────────────────────────────────────────────────────

require __DIR__ . '/input-fields.php';
require __DIR__ .'/class-post-types.php';
require __DIR__ .'/class-taxonomy.php';
require __DIR__ . '/class-metabox.php';
require __DIR__ . '/class-termeta.php';
require __DIR__ . '/utils/utils.php';
require __DIR__ . '/class-usermetabox.php';
require __DIR__ . '/ajax.php';