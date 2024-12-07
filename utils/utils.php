<?php
if (!function_exists('coltman_trim_content_text_fn')) {
    
    function coltman_trim_content_text_fn($content, $length = 15, $ellipsis = '...') {
        return wp_trim_words($content, $length, $ellipsis);
    }
}