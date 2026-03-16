<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Lee el contenido, inyecta un ID a cada heading (h1-h6) y retorna tanto el HTML modificado
 * como un array de objetos con los headings detectados.
 * Esto hace posible obtener los datos ANTES de que the_content() se imprima.
 *
 * @param string $content Contenido HTML.
 * @return array Array estructurado: ['content' => string, 'headings' => array]
 */
function process_headings_and_get_data( $content ) {
    if ( empty( $content ) ) {
        return [
            'content'  => $content,
            'headings' => []
        ];
    }

    $headings = [];
    $used_ids = [];

    $modified_content = preg_replace_callback( 
        '/<(h[1-6])([^>]*)>(.*?)<\/\1>/is', 
        function( $matches ) use ( &$headings, &$used_ids ) {
            $tag        = strtolower( $matches[1] );
            $attributes = $matches[2];
            $inner_html = $matches[3];

            $clean_text = trim( wp_strip_all_tags( $inner_html ) );
            $base_id = sanitize_title( $clean_text );
            
            if ( empty( $base_id ) ) {
                $base_id = 'seccion-titulo';
            }

            $id = $base_id;
            $counter = 1;
            while ( in_array( $id, $used_ids ) ) {
                $id = $base_id . '-' . $counter;
                $counter++;
            }
            $used_ids[] = $id;

            // Almacenamos el array de objetos directamente en nuestra variable por referencia
            $headings[] = (object) [
                'id'   => $id,
                'text' => $clean_text
            ];

            if ( ! preg_match( '/\bid\s*=\s*[\'"][^\'"]*[\'"]/is', $attributes ) ) {
                $attributes .= ' id="' . esc_attr( $id ) . '"';
            } else {
                $attributes = preg_replace( '/\bid\s*=\s*[\'"][^\'"]*[\'"]/is', 'id="' . esc_attr( $id ) . '"', $attributes );
            }

            return "<{$tag}{$attributes}>{$inner_html}</{$tag}>";
        }, 
        $content 
    );

    return [
        'content'  => $modified_content,
        'headings' => $headings
    ];
}

/**
 * Variable global para que el array de headings pueda ser consultado desde la plantilla.
 */
global $rc_global_headings;
$rc_global_headings = [];

/**
 * Filtramos the_content para aplicar el procesador.
 * Además, guardamos los headings detectados en nuestra variable global.
 */
function filter_content_inject_headings( $content ) {
    global $rc_global_headings;
    
    // Llamamos el procesador principal
    $processed = process_headings_and_get_data( $content );
    
    // Actualizamos la variable global con la lista de headings de esta carga
    $rc_global_headings = $processed['headings'];
    
    // Retornamos únicamente el String (contenido HTML para the_content)
    return $processed['content'];
}
add_filter( 'the_content', 'filter_content_inject_headings', 15 ); // Prioridad 15 para intentar atrapar shortcodes procesados

/**
 * Función pública para obtener el listado de headings desde la plantilla (por ej., para la tabla de contenidos).
 * Importante: Debe llamarse _DESPUÉS_ de the_content() o de consultar el post.
 */
function get_extracted_headings_array() {
    global $rc_global_headings;

    if ( ! empty( $rc_global_headings ) ) {
        return $rc_global_headings;
    }

    // Como fallback, si the_content() aún no corrió, intentamos forzar la detección basándonos en get_the_content().
    // Esto es útil si intentas pintar la tabla de contenidos ANTES de the_content().
    $raw_content = get_the_content();
    if ( ! empty( $raw_content ) ) {
        // Debemos aplicar bloques y shortcodes base antes del regex para obtener el HTML real
        $parsed_content = do_shortcode( wp_filter_content_tags( $raw_content ) ); 
        $dry_run = process_headings_and_get_data( $parsed_content );
        return $dry_run['headings'];
    }

    return [];
}

