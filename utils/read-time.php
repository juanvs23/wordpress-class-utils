<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Calcula el tiempo de lectura estimado de un post basado en su contenido.
 *
 * @param int|WP_Post|null $post ID del post o el objeto WP_Post. Si es null, toma el post global actual.
 * @param int $wpm Palabras leídas por minuto. Por defecto 200 (velocidad promedio).
 * @param string $single_suffix Sufijo para el tiempo de lectura en singular. Por defecto 'min'.
 * @param string $plural_suffix Sufijo para el tiempo de lectura en plural. Por defecto 'mins'.
 * @return object Objeto con las propiedades 'time' (minutos estimados) y 'suffix' (sufijo correspondiente).
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
    $reading_time = ceil( $word_count / $wpm );

    if ( $reading_time < 1 ) {
        $reading_time = 1; // Asegurar que el tiempo mínimo sea 1 minuto
    }
    
    $suffix = $reading_time === 1 ? $single_sufix : $plural_sufix;

    

    // Asegurarse de retornar al menos 1 minuto como mínimo (para posts cortos)
    return (object) [
        'time' =>  $reading_time,
        'suffix' => $suffix
    ];
}
