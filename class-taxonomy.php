<?php
if(!class_exists('ColtmanRegisterTaxonomy')){
    class ColtmanRegisterTaxonomy{
        
        
        private $labels;
        private $taxonomy_name;
        private array|bool $rewrite;
        private $post_types;
        private $args;

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
                'all_items'                  => __( 'Todos '.$config['plural_name'], $config['text_domain'] ),
                'parent_item'                => __( 'Superior '.$config['item'].':', $config['text_domain'] ),
                'parent_item_colon'          => __( 'Superior '.$config['item'].':', $config['text_domain'] ),
                'new_item_name'              => __( 'Nuevo '.$config['item'].' Name', $config['text_domain'] ),
                'add_new_item'               => __( 'Añadir nuevo '.$config['item'], $config['text_domain'] ),
                'edit_item'                  => __( 'Editar '.$config['item'], $config['text_domain'] ),
                'update_item'                => __( 'Actualizar '.$config['item'], $config['text_domain'] ),
                'view_item'                  => __( 'Ver '.$config['item'], $config['text_domain'] ),
                'separate_items_with_commas' => __( 'Separado '.$config['item'].' con comas', $config['text_domain'] ),
                'add_or_remove_items'        => __( 'Anñadir o eliminar '.$config['item'], $config['text_domain'] ),
                'choose_from_most_used'      => __( 'Elija entre los '.$config['item'].' mas usados', $config['text_domain'] ),
                'popular_items'              => __( 'Populares '.$config['item'], $config['text_domain'] ),
                'search_items'               => __( 'Buscar '.$config['plural_name'], $config['text_domain'] ),
                'not_found'                  => __( 'No encontrado', $config['text_domain'] ),
                'no_terms'                   => __( 'No hay '.$config['plural_name'], $config['text_domain'] ),
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
                'show_in_menu'               => true,
                'show_tagcloud'              => $config['show_tagcloud'],
                'show_in_rest'               => $config['show_in_rest'],
                'rest_base'                  => $config['rest_base'],
                'rewrite'                    => $this->rewrite
            ];

            add_action( 'init', [$this, 'register_new_taxonomy'] );
        }

        public function register_new_taxonomy(){
            register_taxonomy($this->taxonomy_name, $this->post_types, $this->args );
        }
    }
}
