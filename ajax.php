<?php
/**
 * AJAX handlers for Coltman Framework — relationship field live search.
 *
 * Registered via wp_ajax_* (authenticated users only).
 * Action: coltman_relationship_search
 *
 * Expected GET params:
 *   nonce     — wp_create_nonce('coltman_relationship')
 *   post_type — post type slug to search within
 *   q         — search term
 *   page      — pagination page (default 1)
 */

add_action( 'wp_ajax_coltman_relationship_search', static function () {
    check_ajax_referer( 'coltman_relationship', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( [ 'message' => 'Forbidden' ], 403 );
    }

    $raw_type  = isset( $_GET['post_type'] ) ? sanitize_text_field( $_GET['post_type'] ) : 'post';
    $post_type = count( $parts = array_filter( array_map( 'trim', explode( ',', $raw_type ) ) ) ) > 1 ? $parts : reset( $parts );
    $search    = isset( $_GET['q'] )   ? sanitize_text_field( $_GET['q'] )   : '';
    $page      = isset( $_GET['page'] ) ? max( 1, (int) $_GET['page'] )      : 1;

    $query = new WP_Query( [
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'posts_per_page' => 20,
        'paged'          => $page,
        's'              => $search,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ] );

    $results = [];
    foreach ( $query->posts as $post ) {
        $results[] = [ 'id' => $post->ID, 'text' => $post->post_title ];
    }

    wp_send_json( [
        'results' => $results,
        'more'    => $query->max_num_pages > $page,
    ] );
} );

add_action( 'wp_ajax_coltman_add_group_field', static function () {
    check_ajax_referer( 'coltman_group_schema', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized', 403 );
    }

    $group_id = sanitize_key( isset( $_POST['group_id'] ) ? $_POST['group_id'] : '' );
    $key      = sanitize_key( isset( $_POST['key'] )      ? $_POST['key']      : '' );
    $label    = sanitize_text_field( isset( $_POST['label'] ) ? $_POST['label'] : '' );
    $type     = sanitize_key( isset( $_POST['type'] )     ? $_POST['type']     : 'text' );

    if ( ! $group_id || ! $key || ! $label ) {
        wp_send_json_error( 'Missing required fields' );
    }

    $allowed_types = [ 'text', 'textarea', 'number', 'email', 'url' ];
    if ( ! in_array( $type, $allowed_types, true ) ) {
        wp_send_json_error( 'Invalid field type' );
    }

    $option_key = '_coltman_group_schema_' . $group_id;
    $schema     = get_option( $option_key, [] );
    if ( ! is_array( $schema ) ) $schema = [];

    $existing_keys = array_column( $schema, 'key' );
    if ( in_array( $key, $existing_keys, true ) ) {
        wp_send_json_error( 'Field key already exists in this group' );
    }

    $schema[] = [ 'key' => $key, 'label' => $label, 'type' => $type ];
    update_option( $option_key, $schema, false );

    wp_send_json_success( [ 'key' => $key, 'label' => $label, 'type' => $type ] );
} );

add_action( 'wp_ajax_coltman_remove_group_field', static function () {
    check_ajax_referer( 'coltman_group_schema', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized', 403 );
    }

    $group_id = sanitize_key( isset( $_POST['group_id'] ) ? $_POST['group_id'] : '' );
    $key      = sanitize_key( isset( $_POST['key'] )      ? $_POST['key']      : '' );

    if ( ! $group_id || ! $key ) {
        wp_send_json_error( 'Missing required fields' );
    }

    $option_key = '_coltman_group_schema_' . $group_id;
    $schema     = get_option( $option_key, [] );
    if ( ! is_array( $schema ) ) $schema = [];

    $schema = array_values( array_filter( $schema, static fn( $f ) => $f['key'] !== $key ) );
    update_option( $option_key, $schema, false );

    wp_send_json_success();
} );

add_action( 'wp_ajax_coltman_term_search', static function () {
    check_ajax_referer( 'coltman_term_search', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( [ 'message' => 'Forbidden' ], 403 );
    }

    $raw_tax  = isset( $_GET['taxonomy'] ) ? sanitize_text_field( $_GET['taxonomy'] ) : 'category';
    $parts    = array_values( array_filter( array_map( 'trim', explode( ',', $raw_tax ) ) ) );
    $taxonomy = count( $parts ) === 1 ? $parts[0] : $parts;
    $search   = isset( $_GET['q'] )    ? sanitize_text_field( $_GET['q'] )    : '';
    $page     = isset( $_GET['page'] ) ? max( 1, (int) $_GET['page'] )        : 1;
    $per_page = 20;

    $terms = get_terms( [
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'search'     => $search,
        'number'     => $per_page + 1,
        'offset'     => ( $page - 1 ) * $per_page,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ] );

    $has_more = count( $terms ) > $per_page;
    if ( $has_more ) {
        array_pop( $terms );
    }

    $results = [];
    foreach ( $terms as $term ) {
        $results[] = [ 'id' => $term->term_id, 'text' => $term->name ];
    }

    wp_send_json( [ 'results' => $results, 'more' => $has_more ] );
} );
