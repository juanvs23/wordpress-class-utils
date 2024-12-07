<?php
/**
 * Remove jquery migrate
 */
if(!function_exists('adctn_remove_jquery_migrate')) {
    function adctn_remove_jquery_migrate( $scripts ) {
        if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
            $script = $scripts->registered['jquery'];
            if ( $script->deps ) {
                // Check whether the script has any dependencies.
                $script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
            }
        }
    }
    add_action( 'wp_default_scripts', 'adctn_remove_jquery_migrate',999999 );
}
/**
 * Remove jquery
 */
/* if (!function_exists('adctn_remove_jquery')){
    function adctn_remove_jquery( $scripts ) {
        if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
            unset( $scripts->registered['jquery'] );
        }
    }
    add_action( 'wp_default_scripts', 'adctn_remove_jquery',999999 );
} */