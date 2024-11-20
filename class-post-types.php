<?php

if (!class_exists('ColtmanRegisterPost')) {

    /**
     * 
     * Class ColtmanRegisterPost
     * @param array $labelArgs [
     *            'name' => __('Eventos de local 7', 'restaurant-tools'),
     *           'item' => __('Evento', 'restaurant-tools'),
     *           'domain' => 'restaurant-tools',
     *
     *       ]
     * @param string $post_name 'local7_events'
     * @param array $args [
     *    'description' => __('Lista de eventos', 'restaurant-tools'),
     *    'hierarchical' => bool,
     *    'public' => bool,
     *        'show_ui' => bool,
     *        'show_in_menu' => bool,
     *        'show_in_nav_menus' => bool,
     *        'show_in_admin_bar' => bool,
     *        'menu_position' => int,
     *        'menu_icon' => string,
     *        'can_export' => bool,
     *        'has_archive' => bool,
     *        'exclude_from_search' => bool,
     *        'capability_type' => string,
     *        'publicly_queryable' => bool,
     *        'show_in_rest' => bool,
     *        'map_meta_cap' => bool,
     *        'rest_base' => string,
     * ]
     * @param array $supports [
     *    'thumbnail',
     *    'editor',
     *    'author',
     *    'excerpt',
     *    'custom-fields',
     *    'revisions',
     *    'title'
     * ]
     * @param array $taxonomies [
     *    'taxonomy_names' // string
     * ]
     * @param array|bool $rewrite [
     *    'slug' => 'slug',
     *    'with_front' => bool,
     *    'pages' => bool,
     *    'feeds' => bool,] or false
     * @return void
     * @since 1.0.0
     * @author Coltman
     * @link https://github.com/juanvs23
     */
    class ColtmanRegisterPost 
    {
        private array $labels = [];

        private array $args = [];

        private string $post_name = '';


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

        public function register_new_post_type (){
           register_post_type($this->post_name, $this->args);
        }
    }
    
}
