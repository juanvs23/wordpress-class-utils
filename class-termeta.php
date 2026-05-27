<?php
/**
 * Adds custom meta fields to taxonomy term edit/add screens.
 *
 * Field IDs use the array key (not an 'id' property) — this differs from ColtmanCreateMetabox.
 *
 * ```php
 * new ColtmanTermMeta([
 *     'taxonomy' => 'tipo_de_joyeria',
 *     'fields'   => [
 *         'jewel_material' => [
 *             'label'   => 'Nombre del material principal',
 *             'type'    => 'text',
 *             'default' => '',
 *         ],
 *         'jewel_color' => [
 *             'label'   => 'Color principal',
 *             'type'    => 'select',
 *             'options' => ['oro' => 'Oro', 'plata' => 'Plata', 'bronce' => 'Bronce'],
 *             'default' => 'oro',
 *         ],
 *     ],
 * ]);
 * ```
 *
 * @package Coltman
 * @since   1.0.0
 */
if (!class_exists('ColtmanTermMeta')) {
    class ColtmanTermMeta {

        /**
         * Array of custom fields (keys are field IDs, each entry has 'id' injected).
         *
         * @var array<string, mixed>
         */
        private $fields;
        /**
         * Full configuration array (taxonomy, title, fields).
         *
         * @var array<string, mixed>
         */
        private $config;
        /**
         * ColtmanInputFields instance for field rendering.
         *
         * @var ColtmanInputFields|false
         */
        public $coltmanInputs;
        /**
         * Constructor.
         *
         * Register hooks for rendering and saving custom term meta fields.
         *
         * @since 1.0.0
         * @access public
         */
        public function __construct( array $config) {
            if ( is_admin() ) {
                // Normalize fields: inject 'id' from array key so ColtmanInputFields can use it.
                foreach ( $config['fields'] as $key => &$field ) {
                    if ( ! isset( $field['id'] ) ) {
                        $field['id'] = $key;
                    }
                }
                unset( $field );

                $this->coltmanInputs = class_exists( 'ColtmanInputFields' ) ? new ColtmanInputFields() : false;
                $this->fields = $config['fields'];
                $this->config = $config;

                // Register all the hooks.
                add_action( $config['taxonomy'].'_add_form_fields', [ $this, 'wpturbo_render_meta_fields' ], 10, 2 );
                add_action( $config['taxonomy'].'_edit_form_fields', [ $this, 'wpturbo_edit_meta_fields' ],  10, 2 );
                add_action( 'created_'.$config['taxonomy'], [ $this, 'wpturbo_save_meta_fields' ], 10, 1 );
                add_action( 'edited_'.$config['taxonomy'],  [ $this, 'wpturbo_save_meta_fields' ], 10, 1 );

                add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
                add_action( 'admin_head', [ $this, 'admin_head' ] );
            }
        }
        
        /**
         * Render fields on the add taxonomy page.
         *
         * @since 1.0.0
         * @access public
         *
         * @param string $taxonomy Current taxonomy name.
         */
        public function wpturbo_render_meta_fields( string $taxonomy ) : void {
            $html = '';
            foreach( $this->fields as $field_id => $field ){
                $meta_value = '';
                if ( isset( $field['default'] ) ) {
                    $meta_value = $field['default'];
                }
        
                $field_html = $this->wpturbo_render_input_field( $field_id, $field, $meta_value );
                $label = "<label for='$field_id'>{$field['label']}</label>";
                $html .= $this->wpturbo_format_field( $label, $field_html );
            }
            echo $html;
        }
        
        /**
         * Render fields on the edit taxonomy page.
         *
         * @since 1.0.0
         * @access public
         *
         * @param WP_Term $term     Current term object.
         * @param string  $taxonomy Current taxonomy name.
         */
        public function wpturbo_edit_meta_fields( WP_Term $term, string $taxonomy ) : void {
            $html = '';
            foreach( $this->fields as $field_id => $field ){
                $meta_value = get_term_meta( $term->term_id, $field_id, true );
                $field_html = $this->wpturbo_render_input_field( $field_id, $field, $meta_value );
                $label = "<label class='font-bold' for='$field_id'>{$field['label']}</label>";
                $html .= $this->wpturbo_format_field( $label, $field_html );
            }
            echo $html;
        }
    
        /**
         * 'admin_enqueue_scripts' hook callback — enqueues media, color picker and Select2.
         *
         * Only runs on the taxonomy edit/add page for the configured taxonomy.
         *
         * @return void
         */
        public function admin_enqueue_scripts() {
            $taxonomy_name = $this->config['taxonomy'];
            $taxonomy_url  = isset( $_GET['taxonomy'] ) ? $_GET['taxonomy'] : '';
            if ( $taxonomy_name === $taxonomy_url ) {
                wp_enqueue_media();
                wp_enqueue_script( 'wp-color-picker' );
                wp_enqueue_style( 'wp-color-picker' );
                wp_register_style( 'select2css', COLTMAN_ASSETS_URL . '/libs/select2/select2.min.css', false, '4.0.13', 'all' );
                wp_register_script( 'select2', COLTMAN_ASSETS_URL . '/libs/select2/select2.min.js', [ 'jquery' ], '4.0.13', true );
                wp_enqueue_style( 'select2css' );
                wp_enqueue_style( 'coltman-admin', COLTMAN_ASSETS_URL . '/css/admin.css', [], '1.6.0' );
                wp_enqueue_script( 'select2' );
                wp_enqueue_script( 'coltman-media', COLTMAN_ASSETS_URL . '/js/media.js', [ 'jquery', 'select2', 'jquery-ui-sortable' ], '1.6.0', true );
            }
        }
    
        /**
         * 'admin_head' hook callback — injects media.js and utility CSS.
         *
         * Only runs on the taxonomy edit/add page for the configured taxonomy.
         *
         * @return void
         */
        public function admin_head() {
            // CSS is now enqueued via admin_enqueue_scripts().
        }
        
        /**
         * Format every field to table display.
         *
         * @since 1.0.0
         * @access public
         *
         * @param string $label Label for the field.
         * @param string $field Field HTML.
         *
         * @return string Formatted field HTML.
         */
        public function wpturbo_format_field( string $label, string $field ): string {
            return '<div class="form-field"><div class="font-bold">'.$label.'</div><div class="flex items-center justify-between gap-2" >'.$field.'</div></div>';
        }
        
        /**
         * Render each individual field.
         *
         * @since 1.0.0
         * @access public
         *
         * @param string $field_id Field ID.
         * @param array  $field    Field settings.
         * @param string $field_value Field value.
         *
         * @return string Rendered field HTML.
         */
        public function wpturbo_render_input_field( string $field_id, array $field, string $field_value): string {
            if ( ! $this->coltmanInputs ) {
                return "<input type='text' id='" . esc_attr( $field_id ) . "' name='" . esc_attr( $field_id ) . "' value='" . esc_attr( $field_value ) . "' />";
            }

            $field['id'] = $field_id;

            ob_start();
            switch ( $field['type'] ) {
                case 'select':
                    $this->coltmanInputs->select( $field, $field_value );
                    break;
                case 'textarea':
                    $this->coltmanInputs->textarea( $field, $field_value );
                    break;
                case 'media':
                    $this->coltmanInputs->media( $field, $field_value );
                    break;
                case 'gallery':
                    $this->coltmanInputs->gallery_input( $field, $field_value );
                    break;
                case 'editor':
                    $this->coltmanInputs->editor( $field, $field_value );
                    break;
                case 'accordion':
                    $this->coltmanInputs->accordion( $field, $field_value );
                    break;
                case 'number':
                case 'date':
                    $this->coltmanInputs->input_minmax( $field, $field_value );
                    break;
                default:
                    $this->coltmanInputs->input( $field, $field_value );
            }
            return ob_get_clean();
        }
        
        /**
         * Save the new meta values for our taxonomy.
         *
         * @since 1.0.0
         * @access public
         *
         * @param int $term_id Term ID.
         */
        public function wpturbo_save_meta_fields( int $term_id ) : void {
            if ( ! current_user_can( 'manage_categories' ) ) return;

            foreach ( $this->fields as $field_id => $field ) {
                if ( ! isset( $_POST[ $field_id ] ) ) continue;

                switch ( $field['type'] ) {
                    case 'email':
                        $sanitized = sanitize_email( $_POST[ $field_id ] );
                        break;
                    case 'textarea':
                        $sanitized = sanitize_textarea_field( $_POST[ $field_id ] );
                        break;
                    case 'media':
                    case 'url':
                        $sanitized = esc_url_raw( $_POST[ $field_id ] );
                        break;
                    case 'gallery':
                    case 'accordion':
                        $sanitized = $_POST[ $field_id ];
                        break;
                    default:
                        $sanitized = sanitize_text_field( $_POST[ $field_id ] );
                }

                update_term_meta( $term_id, $field_id, $sanitized );
            }
        }
        
    }
    
}