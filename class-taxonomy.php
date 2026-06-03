<?php
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
                'name'                       => _x( $config['plural_name'], 'Taxonomy General Name', $config['text_domain'] ),
                'singular_name'              => _x( $config['singular_name'], 'Taxonomy Singular Name', $config['text_domain'] ),
                'menu_name'                  => __( $config['plural_name'], $config['text_domain'] ),
                'all_items'                  => __( 'All '.$config['plural_name'], $config['text_domain'] ),
                'parent_item'                => __( 'Superior '.$config['item'].':', $config['text_domain'] ),
                'parent_item_colon'          => __( 'Superior '.$config['item'].':', $config['text_domain'] ),
                'new_item_name'              => __( 'New '.$config['item'].' Name', $config['text_domain'] ),
                'add_new_item'               => __( 'Add new '.$config['item'], $config['text_domain'] ),
                'edit_item'                  => __( 'Edit '.$config['item'], $config['text_domain'] ),
                'update_item'                => __( 'Update '.$config['item'], $config['text_domain'] ),
                'view_item'                  => __( 'View '.$config['item'], $config['text_domain'] ),
                'separate_items_with_commas' => __( 'Separated '.$config['item'].' with commas', $config['text_domain'] ),
                'add_or_remove_items'        => __( 'Add or remove '.$config['item'], $config['text_domain'] ),
                'choose_from_most_used'      => __( 'Choose from the '.$config['item'].' most used', $config['text_domain'] ),
                'popular_items'              => __( 'Popular '.$config['item'], $config['text_domain'] ),
                'search_items'               => __( 'Search '.$config['plural_name'], $config['text_domain'] ),
                'not_found'                  => __( 'Not found', $config['text_domain'] ),
                'no_terms'                   => __( 'No '.$config['plural_name'], $config['text_domain'] ),
                'items_list'                 => __( $config['item'].' list', $config['text_domain'] ),
                'items_list_navigation'      => __( $config['item'].' list navigation', $config['text_domain'] ),
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
