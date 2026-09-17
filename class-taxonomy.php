<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if(!class_exists('ColtmanRegisterTaxonomy')){


    /**
     * Registers a custom taxonomy declaratively with auto-generated labels.
     *
     * ```php
     * new ColtmanRegisterTaxonomy(
     *     [
     *         'plural_name'       => 'Tipos de joya',
     *         'singular_name'     => 'Tipo de joya',
     *         'item'              => 'Tipo de joya',
     *         'text_domain'       => 'anillosdepedida',
     *         'hierarchical'      => true,
     *         'public'            => true,
     *         'show_ui'           => true,
     *         'show_admin_column' => true,
     *         'show_in_nav_menus' => true,
     *         'show_tagcloud'     => true,
     *         'show_in_rest'      => true,
     *         'rest_base'         => 'tipo-de-joyeria',
     *     ],
     *     'tipo_de_joyeria',
     *     ['anillo_jewelry'],
     *     false
     * );
     * ```
     *
     * @package Coltman
     * @since   1.0.0
     */
    class ColtmanRegisterTaxonomy{

        /** @var array<string, string> Translated label set for register_taxonomy(). */
        private array $labels;
        /** @var string Taxonomy slug. */
        private string $taxonomy_name;
        /** @var array|bool Rewrite config or false. */
        private array|bool $rewrite;
        /** @var string[] Post type slugs to associate this taxonomy with. */
        private array $post_types;
        /** @var array<string, mixed> Arguments for register_taxonomy(). */
        private array $args;
        /** @var array<string, string> Capability map. Defaults to manage_categories / edit_posts. */
        private array $capabilities = [
            'manage_terms'  =>'manage_categories',
            'edit_terms'    => 'manage_categories',
            'delete_terms'  => 'manage_categories',
            'assign_terms'  => 'edit_posts'
        ];

        /**
         * @param array<string, mixed> $config        Taxonomy configuration (plural_name, singular_name, item, text_domain, hierarchical, …).
         * @param string               $taxonomy_name Taxonomy slug.
         * @param string[]             $post_types    Post type slugs to attach this taxonomy to.
         * @param array|bool           $rewrite       Rewrite config or false to disable.
         */
        public function __construct(
            array $config,
            string $taxonomy_name,
            array $post_types = [],
            array|bool $rewrite = false)
            {

            $this->taxonomy_name = $taxonomy_name;
            $this->post_types = $post_types;

            $this->labels = [
                        'name'                       => $config['plural_name'],
                        'singular_name'              => $config['singular_name'],
                        'menu_name'                  => $config['plural_name'],
                        // translators: %1$s: taxonomy plural name
                        'all_items'                  => sprintf( __( 'All %1$s', 'coltman' ), $config['plural_name'] ),
                        // translators: %1$s: taxonomy item name
                        'parent_item'                => sprintf( __( 'Superior %1$s:', 'coltman' ), $config['item'] ),
                        // translators: %1$s: taxonomy item name
                        'parent_item_colon'          => sprintf( __( 'Superior %1$s:', 'coltman' ), $config['item'] ),
                        // translators: %1$s: taxonomy item name
                        'new_item_name'              => sprintf( __( 'New %1$s Name', 'coltman' ), $config['item'] ),
                        // translators: %1$s: taxonomy item name
                        'add_new_item'               => sprintf( __( 'Add new %1$s', 'coltman' ), $config['item'] ),
                        // translators: %1$s: taxonomy item name
                        'edit_item'                  => sprintf( __( 'Edit %1$s', 'coltman' ), $config['item'] ),
                        // translators: %1$s: taxonomy item name
                        'update_item'                => sprintf( __( 'Update %1$s', 'coltman' ), $config['item'] ),
                        // translators: %1$s: taxonomy item name
                        'view_item'                  => sprintf( __( 'View %1$s', 'coltman' ), $config['item'] ),
                        // translators: %1$s: taxonomy item name
                        'separate_items_with_commas' => sprintf( __( 'Separated %1$s with commas', 'coltman' ), $config['item'] ),
                        // translators: %1$s: taxonomy item name
                        'add_or_remove_items'        => sprintf( __( 'Add or remove %1$s', 'coltman' ), $config['item'] ),
                        // translators: %1$s: taxonomy item name
                        'choose_from_most_used'      => sprintf( __( 'Choose from the %1$s most used', 'coltman' ), $config['item'] ),
                        // translators: %1$s: taxonomy item name
                        'popular_items'              => sprintf( __( 'Popular %1$s', 'coltman' ), $config['item'] ),
                        // translators: %1$s: taxonomy plural name
                        'search_items'               => sprintf( __( 'Search %1$s', 'coltman' ), $config['plural_name'] ),
                'not_found'                  => __( 'Not found', 'coltman' ),
                        // translators: %1$s: taxonomy plural name
                        'no_terms'                   => sprintf( __( 'No %1$s', 'coltman' ), $config['plural_name'] ),
                        // translators: %1$s: taxonomy item name
                        'items_list'                 => sprintf( __( '%1$s list', 'coltman' ), $config['item'] ),
                        // translators: %1$s: taxonomy item name
                        'items_list_navigation'      => sprintf( __( '%1$s list navigation', 'coltman' ), $config['item'] ),
            ];

            $this->rewrite = $rewrite;
            $this->args = [
                'labels'                     => $this->labels,
                'hierarchical'               => $config['hierarchical'],
                'public'                     => $config['public'],
                'show_ui'                    => $config['show_ui'],
                'show_admin_column'          => $config['show_admin_column'],
                'show_in_nav_menus'          => $config['show_in_nav_menus'],
                'show_in_menu'               => isset($config['show_in_menu']) ? $config['show_in_menu'] : true,
                'capabilities'               => isset($config['capabilities']) ? $config['capabilities'] : $this->capabilities,
                'show_tagcloud'              => isset($config['show_tagcloud']) ? $config['show_tagcloud'] : true,
                'show_in_rest'               => $config['show_in_rest'],
                'rest_base'                  => $config['rest_base'],
                'rewrite'                    => $this->rewrite
            ];

            add_action( 'init', [$this, 'register_new_taxonomy'] );
        }

        /**
         * 'init' hook callback — calls register_taxonomy() with the built args.
         *
         * @return void
         */
        public function register_new_taxonomy(){
            register_taxonomy($this->taxonomy_name, $this->post_types, $this->args );
        }
    }
}
