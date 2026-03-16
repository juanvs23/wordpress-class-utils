<?php
require __DIR__ . '/read-time.php';
require __DIR__ . '/navigations_archors.php';
if (!function_exists('coltman_trim_content_text_fn')) {
    
    function coltman_trim_content_text_fn($content, $length = 15, $ellipsis = '...') {
        return wp_trim_words($content, $length, $ellipsis);
    }
}

if(!function_exists('formaturltext')){

    function formaturltext($text) {
        // 1. Eliminar tildes y diacríticos (convertir "á" a "a", etc.)
        $noAccents = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        
        // 2. Eliminar cualquier carácter que no sea letra, número o espacio
        //    (opcional: puedes mantener guiones si lo deseas, pero aquí solo dejamos letras, números y espacios)
        $onlyValid = preg_replace('/[^a-zA-Z0-9\s]/', '', $noAccents);
        
        // 3. Reemplazar espacios por '+'
        $replaceSpace  = str_replace(' ', '+', $onlyValid);
        
        // 4. Eliminar posibles múltiples espacios consecutivos (opcional)
        $removeSpaces = preg_replace('/\s+/', '+', $replaceSpace);
        
        // 5. Devolver el resultado
        return $removeSpaces;
    }
}