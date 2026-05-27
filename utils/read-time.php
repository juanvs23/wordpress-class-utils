<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Calculates the estimated reading time of a post.
 *
 * @param array{
 *     post?:          int|WP_Post|null,
 *     wpm?:           int,
 *     single_suffix?: string,
 *     plural_suffix?: string
 * } $args {
 *     @type int|WP_Post|null $post          Post ID, WP_Post object, or null for the global post.
 *     @type int              $wpm           Words per minute. Default 200.
 *     @type string           $single_suffix Suffix for singular reading time. Default 'min'.
 *     @type string           $plural_suffix Suffix for plural reading time. Default 'mins'.
 * }
 * @return object{time: int, suffix: string} Object with 'time' (minutes, min. 1) and 'suffix'.
 */
function get_estimated_reading_time( $args ) {
    // Obtener el objeto del Post
    $post = isset( $args['post'] ) ? $args['post'] : null;
    $wpm = isset( $args['wpm'] ) ? $args['wpm'] : 200;
    $single_sufix = isset( $args['single_suffix'] ) ? $args['single_suffix'] : 'min';
    $plural_sufix = isset( $args['plural_suffix'] ) ? $args['plural_suffix'] : 'mins';
    $post_obj = get_post( $post );

    if ( ! $post_obj || empty( $post_obj->post_content ) ) {
        return (object) [
            'time' => 1,
            'suffix' => $single_sufix
        ]; // Devuelve 1 minuto por defecto si no hay contenido
    }

    // Obtener el contenido
    $content = $post_obj->post_content;

    // Eliminar shortcodes y etiquetas HTML para contar solo texto real
    $content = strip_shortcodes( $content );
    $content = wp_strip_all_tags( $content );

    // Contar las palabras del string
    $word_count = str_word_count( $content );

    // Calcular el tiempo (Dividiendo las palabras entre palabras por minuto)
    $reading_time = (int) ceil( $word_count / $wpm );

    if ( $reading_time < 1 ) {
        $reading_time = 1;
    }
    
    $suffix = $reading_time === 1 ? $single_sufix : $plural_sufix;

    

    // Asegurarse de retornar al menos 1 minuto como mínimo (para posts cortos)
    return (object) [
        'time' =>  $reading_time,
        'suffix' => $suffix
    ];
}
