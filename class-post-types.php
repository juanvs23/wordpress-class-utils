<?php
if ( ! defined( 'ABSPATH' ) ) exit;

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
                        'name'                  => $labelArgs['name'],
                        'singular_name'         => $labelArgs['name'],
                        'menu_name'             => $labelArgs['name'],
                        'name_admin_bar'        => $labelArgs['item'],
                        // translators: %1$s: singular post type item name
                        'archives'              => sprintf( __( '%1$s Archivos', 'coltman' ), $labelArgs['item'] ),
                        // translators: %1$s: singular post type item name
                        'attributes'            => sprintf( __( '%1$s atributos', 'coltman' ), $labelArgs['item'] ),
                        // translators: %1$s: singular post type item name
                        'parent_item_colon'     => sprintf( __( 'Parent %1$s:', 'coltman' ), $labelArgs['item'] ),
                        // translators: %1$s: post type plural name
                        'all_items'             => sprintf( __( 'All %1$s', 'coltman' ), $labelArgs['name'] ),
                        // translators: %1$s: singular post type item name
                        'add_new_item'          => sprintf( __( 'Add new %1$s', 'coltman' ), $labelArgs['item'] ),
                        'add_new'               => __( 'Add ', 'coltman' ),
                        // translators: %1$s: singular post type item name
                        'new_item'              => sprintf( __( 'New %1$s', 'coltman' ), $labelArgs['item'] ),
                        // translators: %1$s: singular post type item name
                        'edit_item'             => sprintf( __( 'Edit %1$s', 'coltman' ), $labelArgs['item'] ),
                        // translators: %1$s: singular post type item name
                        'update_item'           => sprintf( __( 'Update %1$s', 'coltman' ), $labelArgs['item'] ),
                        // translators: %1$s: singular post type item name
                        'view_item'             => sprintf( __( 'View %1$s', 'coltman' ), $labelArgs['item'] ),
                        // translators: %1$s: post type plural name
                        'view_items'            => sprintf( __( 'View %1$s', 'coltman' ), $labelArgs['name'] ),
                        // translators: %1$s: singular post type item name
                        'search_items'          => sprintf( __( 'Search %1$s', 'coltman' ), $labelArgs['item'] ),
                        'not_found'             => __( 'Not found', 'coltman' ),
                        'not_found_in_trash'    => __( 'Not found in Trash', 'coltman' ),
                        'featured_image'        => __( 'Featured Image', 'coltman' ),
                        'set_featured_image'    => __( 'Set featured image', 'coltman' ),
                        'remove_featured_image' => __( 'Remove featured image', 'coltman' ),
                        'use_featured_image'    => __( 'Use as featured image', 'coltman' ),
                        // translators: %1$s: singular post type item name
                        'insert_into_item'      => sprintf( __( 'Insert into %1$s', 'coltman' ), $labelArgs['item'] ),
                        // translators: %1$s: singular post type item name
                        'uploaded_to_this_item' => sprintf( __( 'Uploaded to this %1$s', 'coltman' ), $labelArgs['item'] ),
                        // translators: %1$s: post type plural name
                        'items_list'            => sprintf( __( 'Items %1$s', 'coltman' ), $labelArgs['name'] ),
                        // translators: %1$s: singular post type item name
                        'items_list_navigation' => sprintf( __( 'Items %1$s', 'coltman' ), $labelArgs['item'] ),
                        // translators: %1$s: singular post type item name
                        'filter_items_list'     => sprintf( __( 'Filter %1$s', 'coltman' ), $labelArgs['item'] ),
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
