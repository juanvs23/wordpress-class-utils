<?php

if (!class_exists('ColtmanRegisterPost')) {

    /**
     * Class ColtmanRegisterPost
     * @param array $labelArgs
     * @param string $post_name
     * @param array $args
     * @param array $supports 
     * @param array $taxonomies 
     * @param array|bool $rewrite [
     *    'slug' => 'slug',
     *    'with_front' => true,
     *    'pages' => true,
     *    'feeds' => true,]
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
                        'all_items'             => __( 'Todos los '. $labelArgs['name'], $labelArgs['domain'] ),
                        'add_new_item'          => __( 'Añadir nuevo '. $labelArgs['item'], $labelArgs['domain'] ),
                        'add_new'               => __( 'Añadir ', $labelArgs['domain'] ),
                        'new_item'              => __( 'Nuevo '. $labelArgs['item'], $labelArgs['domain'] ),
                        'edit_item'             => __( 'Editar '. $labelArgs['item'], $labelArgs['domain'] ),
                        'update_item'           => __( 'Actualizar '. $labelArgs['item'], $labelArgs['domain'] ),
                        'view_item'             => __( 'Ver '. $labelArgs['item'], $labelArgs['domain'] ),
                        'view_items'            => __( 'Ver '. $labelArgs['name'], $labelArgs['domain'] ),
                        'search_items'          => __( 'Buscar '. $labelArgs['item'], $labelArgs['domain'] ),
                        'not_found'             => __( 'No encontrado', $labelArgs['domain'] ),
                        'not_found_in_trash'    => __( 'No encontrado en la papelera', $labelArgs['domain'] ),
                        'featured_image'        => __( 'Imagen destacada', $labelArgs['domain'] ),
                        'set_featured_image'    => __( 'Configurar imagen destacada', $labelArgs['domain'] ),
                        'remove_featured_image' => __( 'Eliminar imagen destacada', $labelArgs['domain'] ),
                        'use_featured_image'    => __( 'Usar como imagen destacada', $labelArgs['domain'] ),
                        'insert_into_item'      => __( 'Insertar '. $labelArgs['item'], $labelArgs['domain'] ),
                        'uploaded_to_this_item' => __( 'Subir '. $labelArgs['item'], $labelArgs['domain'] ),
                        'items_list'            => __( 'Lista '. $labelArgs['name'], $labelArgs['domain'] ),
                        'items_list_navigation' => __(  'Lista de navegación '.$labelArgs['item'], $labelArgs['domain'] ),
                        'filter_items_list'     => __( 'Filtrar lista de '.$labelArgs['item'].'', $labelArgs['domain'] ),
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
