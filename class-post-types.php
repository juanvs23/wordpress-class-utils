<?php

if (!class_exists('ColtmanRegisterPost')) {

    /**
     * Registers a Custom Post Type declaratively with auto-generated labels.
     *
     * ```php
     * new ColtmanRegisterPost(
     *     ['name' => 'Joyas', 'item' => 'Joya', 'domain' => 'anillosdepedida'],
     *     'anillo_jewelry',
     *     [
     *         'description'         => '',
     *         'hierarchical'        => true,
     *         'public'              => true,
     *         'show_ui'             => true,
     *         'show_in_menu'        => true,
     *         'show_in_admin_bar'   => true,
     *         'show_in_nav_menus'   => true,
     *         'menu_position'       => 5,
     *         'menu_icon'           => 'dashicons-superhero-alt',
     *         'can_export'          => true,
     *         'has_archive'         => false,
     *         'exclude_from_search' => false,
     *         'publicly_queryable'  => true,
     *         'capability_type'     => 'post',
     *         'show_in_rest'        => false,
     *         'map_meta_cap'        => true,
     *         'rest_base'           => '',
     *     ],
     *     ['thumbnail', 'custom-fields', 'editor', 'revisions', 'title'],
     *     ['tipo_de_joyeria'],
     *     false
     * );
     * ```
     *
     * @package Coltman
     * @since   1.0.0
     */
    class ColtmanRegisterPost
    {
        /** @var array<string, string> Translated label set passed to register_post_type(). */
        private array $labels = [];

        /** @var array<string, mixed> Arguments array for register_post_type(). */
        private array $args = [];

        /** @var string Post type slug. */
        private string $post_name = '';


        /**
         * @param array{name: string, item: string, domain: string} $labelArgs  Plural name, singular item label, and text domain.
         * @param string       $post_name  Post type slug (e.g. 'anillo_jewelry').
         * @param array<string, mixed> $args  Arguments passed to register_post_type() (public, show_ui, menu_position, …).
         * @param string[]     $supports   Post-type feature support ('title', 'editor', 'thumbnail', …).
         * @param string[]     $taxonomies Taxonomy slugs to associate on registration.
         * @param array|bool   $rewrite    Rewrite config array or false to disable.
         */
        public function __construct(
                array $labelArgs,
                string $post_name,
                array $args = [],
                array $supports = [],
                array $taxonomies = [],
                array|bool $rewrite = false )
        {

                    $this->post_name = $post_name;

                    $this->labels = [
                        'name'                  => _x( $labelArgs['name'], 'Post Type General Name', $labelArgs['domain'] ),
                        'singular_name'         => _x( $labelArgs['name'], 'Post Type Singular Name', $labelArgs['domain'] ),
                        'menu_name'             => __( $labelArgs['name'], $labelArgs['domain'] ),
                        'name_admin_bar'        => __( $labelArgs['item'], $labelArgs['domain'] ),
                        'archives'              => __(  $labelArgs['item'] . ' Archivos', $labelArgs['domain'] ),
                        'attributes'            => __(  $labelArgs['item'] . ' atributos', $labelArgs['domain'] ),
                        'parent_item_colon'     => __( 'Parent '. $labelArgs['item'].':', $labelArgs['domain'] ),
                        'all_items'             => __( 'All '. $labelArgs['name'], $labelArgs['domain'] ),
                        'add_new_item'          => __( 'Add new '. $labelArgs['item'], $labelArgs['domain'] ),
                        'add_new'               => __( 'Add ', $labelArgs['domain'] ),
                        'new_item'              => __( 'New '. $labelArgs['item'], $labelArgs['domain'] ),
                        'edit_item'             => __( 'Edit '. $labelArgs['item'], $labelArgs['domain'] ),
                        'update_item'           => __( 'Update '. $labelArgs['item'], $labelArgs['domain'] ),
                        'view_item'             => __( 'View '. $labelArgs['item'], $labelArgs['domain'] ),
                        'view_items'            => __( 'View '. $labelArgs['name'], $labelArgs['domain'] ),
                        'search_items'          => __( 'Search '. $labelArgs['item'], $labelArgs['domain'] ),
                        'not_found'             => __( 'Not found', $labelArgs['domain'] ),
                        'not_found_in_trash'    => __( 'Not found in Trash', $labelArgs['domain'] ),
                        'featured_image'        => __( 'Featured Image', $labelArgs['domain'] ),
                        'set_featured_image'    => __( 'Set featured image', $labelArgs['domain'] ),
                        'remove_featured_image' => __( 'Remove featured image', $labelArgs['domain'] ),
                        'use_featured_image'    => __( 'Use as featured image', $labelArgs['domain'] ),
                        'insert_into_item'      => __( 'Insert into '. $labelArgs['item'], $labelArgs['domain'] ),
                        'uploaded_to_this_item' => __( 'Uploaded to this '. $labelArgs['item'], $labelArgs['domain'] ),
                        'items_list'            => __( 'Items '. $labelArgs['name'], $labelArgs['domain'] ),
                        'items_list_navigation' => __(  'Items '.$labelArgs['item'], $labelArgs['domain'] ),
                        'filter_items_list'     => __( 'Filter '.$labelArgs['item'].'', $labelArgs['domain'] ),
                    ];
                    $this->args =[
                        'label'                 => $labelArgs['name'],
                        'description'           => $args['description'],
                        'labels'                => $this->labels,
                        'supports'              => $supports,
                        'taxonomies'            => $taxonomies,
                        'hierarchical'          => $args['hierarchical'],
                        'public'                => $args['public'],
                        'show_ui'               => $args['show_ui'],
                        'show_in_menu'          => $args['show_in_menu'],
                        'menu_position'         => $args['menu_position'],
                        'menu_icon'             => $args['menu_icon'],
                        'show_in_admin_bar'     => $args['show_in_admin_bar'],
                        'show_in_nav_menus'     => $args['show_in_nav_menus'],
                        'can_export'            => $args['can_export'],
                        'has_archive'           => $args['has_archive'],
                        'exclude_from_search'   => $args['exclude_from_search'],
                        'publicly_queryable'    => $args['publicly_queryable'],
                        'rewrite'               => $rewrite,
                        'capability_type'       => $args['capability_type'],
                        'show_in_rest'          => $args['show_in_rest'],
                        'rest_base'             => $args['rest_base'],
                        'map_meta_cap'          => $args['map_meta_cap'],

                    ] ;
                    
                    add_action('init', [$this, 'register_new_post_type']);
        }

        /**
         * 'init' hook callback — calls register_post_type() with the built args.
         *
         * @return void
         */
        public function register_new_post_type (){
           register_post_type($this->post_name, $this->args);
        }
    }
    
}
