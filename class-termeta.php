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
        
                if ( isset( $field['type'] ) && $field['type'] === 'group' ) {
                    $html .= $this->wpturbo_render_group( $field_id, $field, '' );
                    continue;
                }
                $field_html = $this->wpturbo_render_input_field( $field_id, $field, $meta_value );
                $label = "<label for='" . esc_attr( $field_id ) . "'>" . esc_html( $field['label'] ) . "</label>";
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
                if ( isset( $field['type'] ) && $field['type'] === 'group' ) {
                    $html .= $this->wpturbo_render_group( $field_id, $field, $term->term_id );
                    continue;
                }
                $field_html = $this->wpturbo_render_input_field( $field_id, $field, $meta_value, $term->term_id );
                $label  = '<label for="' . esc_attr( $field_id ) . '">' . esc_html( $field['label'] ) . '</label>';
                $html  .= '<tr class="form-field term-' . esc_attr( $field_id ) . '-wrap">';
                $html  .= '<th scope="row">' . $label . '</th>';
                $html  .= '<td>' . $field_html . '</td>';
                $html  .= '</tr>';
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
                wp_register_style( 'select2css', COLTMAN_ASSETS_URL . '/libs/select2/select2.min.css', false, '4.1.0', 'all' );
                wp_register_script( 'select2', COLTMAN_ASSETS_URL . '/libs/select2/select2.min.js', [ 'jquery' ], '4.1.0', true );
                wp_enqueue_style( 'select2css' );
                wp_enqueue_style( 'coltman-admin', COLTMAN_ASSETS_URL . '/css/admin.css', [], '1.6.0' );
                wp_enqueue_script( 'select2' );
                wp_enqueue_script( 'coltman-media', COLTMAN_ASSETS_URL . '/js/media.js', [ 'jquery', 'select2', 'jquery-ui-sortable' ], '1.6.0', true );
                wp_localize_script( 'coltman-media', 'coltmanVars', [ 'assetsUrl' => COLTMAN_ASSETS_URL ] );
                wp_enqueue_style( 'leaflet',  COLTMAN_ASSETS_URL . '/libs/leaflet/leaflet.min.css', [], '1.9.4' );
                wp_enqueue_script( 'leaflet', COLTMAN_ASSETS_URL . '/libs/leaflet/leaflet.min.js', [], '1.9.4', true );
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
        public function wpturbo_render_input_field( string $field_id, array $field, string $field_value, int $term_id = 0 ): string {
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
                    $field['_alt_value'] = $term_id
                        ? (string) get_term_meta( $term_id, $field_id . '_alt', true ) : '';
                    $this->coltmanInputs->media( $field, $field_value );
                    break;
                case 'gallery':
                    $this->coltmanInputs->gallery_input( $field, $field_value );
                    break;
                case 'list':
                    $this->coltmanInputs->list_input( $field, $field_value );
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
                case 'color':
                    $this->coltmanInputs->color( $field, $field_value );
                    break;
                case 'repeater':
                    $this->coltmanInputs->repeater( $field, $field_value );
                    break;
                case 'relationship':
                    $this->coltmanInputs->relationship( $field, $field_value );
                    break;
                case 'map':
                    $this->coltmanInputs->map( $field, $field_value );
                    break;
                case 'get_terms':
                    $this->coltmanInputs->get_terms( $field, $field_value );
                    break;
                case 'get_posts':
                    $this->coltmanInputs->get_posts( $field, $field_value );
                    break;
                case 'checkbox':
                    $checked = $field_value === 'on' || ! empty( $field['checked'] ) ? 'checked' : '';
                    $this->coltmanInputs->checkbox( $field, $checked );
                    break;
                default:
                    $this->coltmanInputs->input( $field, $field_value );
            }
            return ob_get_clean();
        }
        
        /**
         * Renders a group field as a single collapsible card: header with label + toggle,
         * then each sub-field stacked inside. Each sub-field is dispatched through
         * wpturbo_render_input_field() and saved with its own meta key.
         *
         * @param string $group_id  Field ID (used as the collapse target ID).
         * @param array  $field     Group field configuration.
         * @param mixed  $term_id   Term ID or empty string (for new-term page).
         * @return string HTML string.
         */
        private function get_group_schema( string $group_id ): array {
            $schema = get_option( '_coltman_group_schema_' . $group_id, [] );
            return is_array( $schema ) ? $schema : [];
        }

        private function wpturbo_render_group( string $group_id, array $field, $term_id ): string {
            $gid            = esc_attr( $group_id );
            $label          = isset( $field['label'] ) ? esc_html( $field['label'] ) : '';
            $static_fields  = isset( $field['fields'] ) ? $field['fields'] : [];
            $dynamic_schema = $this->get_group_schema( $group_id );
            $static_ids     = array_column( $static_fields, 'id' );
            $nonce          = wp_create_nonce( 'coltman_group_schema' );
            $html  = '<div class="coltman-group-header">';
            $html .= '<span class="coltman-group-label">' . $label . '</span>';
            $html .= '<button type="button" class="coltman-group-toggle" data-group="' . $gid . '" aria-expanded="true">&#9650;</button>';
            $html .= '</div>';
            if ( ! empty( $field['description'] ) ) {
                $html .= '<p class="w-full mb-2 text-sm text-gray-500 description">' . esc_html( $field['description'] ) . '</p>';
            }
            $html .= '<div class="coltman-group-body" id="coltman-group-' . $gid . '">';
            foreach ( $static_fields as $sub_field ) {
                $sub_id   = $sub_field['id'];
                $sub_val  = $term_id ? (string) get_term_meta( (int) $term_id, $sub_id, true ) : ( isset( $sub_field['default'] ) ? (string) $sub_field['default'] : '' );
                $sub_html  = $this->wpturbo_render_input_field( $sub_id, $sub_field, $sub_val );
                $sub_label = '<label for="' . esc_attr( $sub_id ) . '">' . ( isset( $sub_field['label'] ) ? esc_html( $sub_field['label'] ) : '' ) . '</label>';
                $html .= '<div class="coltman-group-field-row">' . $sub_label . $sub_html . '</div>';
            }
            foreach ( $dynamic_schema as $df ) {
                if ( in_array( $df['key'], $static_ids, true ) ) continue;
                $sub = [ 'id' => $df['key'], 'type' => $df['type'], 'label' => $df['label'] ];
                $dyn_val  = $term_id ? (string) get_term_meta( (int) $term_id, $df['key'], true ) : '';
                $dyn_html  = $this->wpturbo_render_input_field( $df['key'], $sub, $dyn_val );
                $dyn_label = '<label for="' . esc_attr( $df['key'] ) . '">' . esc_html( $df['label'] ) . '</label>';
                $html .= '<div class="coltman-group-field-row" data-dynamic-key="' . esc_attr( $df['key'] ) . '">' . $dyn_label . $dyn_html . '</div>';
            }
            $dyn_visible = array_values( array_filter( $dynamic_schema, static fn( $df ) => ! in_array( $df['key'], $static_ids, true ) ) );
            $fm  = '<div class="coltman-field-manager" data-group="' . $gid . '" data-nonce="' . esc_attr( $nonce ) . '">';
            $fm .= '<div class="coltman-field-manager-toggle-row">';
            $fm .= '<button type="button" class="coltman-field-manager-toggle" aria-expanded="false">&#9881; ' . esc_html__( 'Manage fields', COLTMAN_TEXT_DOMAIN ) . '</button>';
            $fm .= '</div>';
            $fm .= '<div class="coltman-field-manager-panel" style="display:none">';
            $fm .= '<div class="coltman-dynamic-fields-list">';
            if ( empty( $dyn_visible ) ) {
                $fm .= '<p class="coltman-no-dynamic-fields">' . esc_html__( 'No dynamic fields added yet.', COLTMAN_TEXT_DOMAIN ) . '</p>';
            } else {
                foreach ( $dyn_visible as $df ) {
                    $fm .= '<div class="coltman-dynamic-field-item" data-key="' . esc_attr( $df['key'] ) . '">';
                    $fm .= '<span class="coltman-dynamic-field-info">' . esc_html( $df['type'] ) . ' &middot; ' . esc_html( $df['label'] ) . ' <code>' . esc_html( $df['key'] ) . '</code></span>';
                    $fm .= '<button type="button" class="coltman-remove-dynamic-field" data-key="' . esc_attr( $df['key'] ) . '">&#10005;</button>';
                    $fm .= '</div>';
                }
            }
            $fm .= '</div>';
            $fm .= '<div class="coltman-add-field-form">';
            $fm .= '<select class="coltman-new-field-type">';
            $fm .= '<option value="text">'   . esc_html__( 'Text',     COLTMAN_TEXT_DOMAIN ) . '</option>';
            $fm .= '<option value="textarea">' . esc_html__( 'Textarea', COLTMAN_TEXT_DOMAIN ) . '</option>';
            $fm .= '<option value="number">'  . esc_html__( 'Number',   COLTMAN_TEXT_DOMAIN ) . '</option>';
            $fm .= '<option value="email">'   . esc_html__( 'Email',    COLTMAN_TEXT_DOMAIN ) . '</option>';
            $fm .= '<option value="url">URL</option>';
            $fm .= '</select>';
            $fm .= '<input type="text" class="coltman-new-field-key" placeholder="' . esc_attr__( 'field_key', COLTMAN_TEXT_DOMAIN ) . '">';
            $fm .= '<input type="text" class="coltman-new-field-label" placeholder="' . esc_attr__( 'Field Label', COLTMAN_TEXT_DOMAIN ) . '">';
            $fm .= '<button type="button" class="coltman-add-dynamic-field">+ ' . esc_html__( 'Add field', COLTMAN_TEXT_DOMAIN ) . '</button>';
            $fm .= '</div>';
            $fm .= '<p class="coltman-field-manager-note">' . esc_html__( '* Dynamic fields apply to all terms with this group.', COLTMAN_TEXT_DOMAIN ) . '</p>';
            $fm .= '</div>';
            $fm .= '</div>';
            $html .= $fm;
            $html .= '</div>';
            return $html;
        }

                public function wpturbo_save_meta_fields( int $term_id ) : void {
            if ( ! current_user_can( 'manage_categories' ) ) return;

            foreach ( $this->fields as $field_id => $field ) {
                if ( $field['type'] !== 'checkbox' && ! isset( $_POST[ $field_id ] ) ) continue;

                switch ( $field['type'] ) {
                    case 'email':
                        $sanitized = sanitize_email( $_POST[ $field_id ] );
                        break;
                    case 'textarea':
                        $sanitized = wp_kses_post( $_POST[ $field_id ] );
                        break;
                    case 'url':
                        $sanitized = esc_url_raw( $_POST[ $field_id ] );
                        break;
                    case 'media':
                        $_m_val = isset( $_POST[ $field_id ] ) ? (string) $_POST[ $field_id ] : '';
                        $_m_saved = ( isset( $field['return'] ) && $field['return'] === 'id' )
                            ? (string) absint( $_m_val ) : esc_url_raw( $_m_val );
                        update_term_meta( $term_id, $field_id, $_m_saved );
                        $_alt_k = $field_id . '_alt';
                        update_term_meta( $term_id, $_alt_k,
                            sanitize_text_field( isset( $_POST[ $_alt_k ] ) ? (string) $_POST[ $_alt_k ] : '' ) );
                        continue 2;
                    case 'editor':
                        $sanitized = wp_filter_post_kses( (string) $_POST[ $field_id ] );
                        break;
                    case 'gallery':
                    case 'accordion':
                    case 'list':
                        $sanitized = $_POST[ $field_id ];
                        break;
                    case 'color':
                        $sanitized = sanitize_text_field( $_POST[ $field_id ] );
                        break;
                    case 'map':
                        if ( isset( $_POST[ $field_id ] ) && $_POST[ $field_id ] !== '' ) {
                            $raw   = json_decode( wp_unslash( $_POST[ $field_id ] ), true );
                            $lat   = isset( $raw['lat'] )  ? (float) $raw['lat']  : null;
                            $lng   = isset( $raw['lng'] )  ? (float) $raw['lng']  : null;
                            $mzoom = isset( $raw['zoom'] ) ? (int)   $raw['zoom'] : 13;
                            if ( $lat !== null && $lat >= -90 && $lat <= 90 && $lng !== null && $lng >= -180 && $lng <= 180 ) {
                                update_term_meta( $term_id, $field_id, wp_json_encode( [ 'lat' => $lat, 'lng' => $lng, 'zoom' => $mzoom ] ) );
                            }
                        } else {
                            update_term_meta( $term_id, $field_id, '' );
                        }
                        continue 2;
                    case 'relationship':
                        $sanitized = json_encode( (array) $_POST[ $field_id ] );
                        break;
                    case 'repeater':
                        if ( ! is_array( $_POST[ $field_id ] ) ) continue 2;
                        $rows = [];
                        foreach ( $_POST[ $field_id ] as $row ) {
                            if ( ! is_array( $row ) ) continue;
                            $clean = [];
                            foreach ( isset( $field['sub_fields'] ) ? $field['sub_fields'] : [] as $sub ) {
                                $sub_val = isset( $row[ $sub['id'] ] ) ? $row[ $sub['id'] ] : '';
                                switch ( isset( $sub['type'] ) ? $sub['type'] : 'text' ) {
                                    case 'email':    $clean[ $sub['id'] ] = sanitize_email( (string) $sub_val );         break;
                                    case 'textarea': $clean[ $sub['id'] ] = wp_kses_post( (string) $sub_val ); break;
                                    case 'url':      $clean[ $sub['id'] ] = esc_url_raw( (string) $sub_val );             break;
                                    default:         $clean[ $sub['id'] ] = sanitize_text_field( (string) $sub_val );
                                }
                            }
                            if ( array_filter( $clean ) ) $rows[] = $clean;
                        }
                        $sanitized = json_encode( $rows );
                        break;
                    case 'get_terms':
                        $is_multiple = ! ( isset( $field['multiple'] ) && ! $field['multiple'] );
                        if ( $is_multiple ) {
                            $sanitized = json_encode( isset( $_POST[ $field_id ] ) ? (array) $_POST[ $field_id ] : [] );
                        } else {
                            $sanitized = isset( $_POST[ $field_id ] ) ? sanitize_text_field( $_POST[ $field_id ] ) : '';
                        }
                        break;
                    case 'get_posts':
                        $sanitized = json_encode( isset( $_POST[ $field_id ] ) ? (array) $_POST[ $field_id ] : [] );
                        break;
                    case 'checkbox':
                        $sanitized = isset( $_POST[ $field_id ] ) && $_POST[ $field_id ] === 'on' ? 'on' : '';
                        break;
                    case 'group':
                        $all_sub_fields = isset( $field['fields'] ) ? $field['fields'] : [];
                        $dyn_schema     = $this->get_group_schema( $field_id );
                        $static_sub_ids = array_column( $all_sub_fields, 'id' );
                        foreach ( $dyn_schema as $df ) {
                            if ( ! in_array( $df['key'], $static_sub_ids, true ) ) {
                                $all_sub_fields[] = [ 'id' => $df['key'], 'type' => $df['type'], 'label' => $df['label'] ];
                            }
                        }
                        foreach ( $all_sub_fields as $sub_field ) {
                            $sub_id  = $sub_field['id'];
                            $sub_val = isset( $_POST[ $sub_id ] ) ? $_POST[ $sub_id ] : null;
                            if ( $sub_val === null ) continue;
                            switch ( isset( $sub_field['type'] ) ? $sub_field['type'] : 'text' ) {
                                case 'textarea':    $sv = wp_kses_post( $sub_val ); break;
                                case 'email':       $sv = sanitize_email( (string) $sub_val ); break;
                                case 'url':         $sv = esc_url_raw( (string) $sub_val ); break;
                                case 'editor':      $sv = wp_filter_post_kses( (string) $sub_val ); break;
                                case 'get_posts':
                                case 'relationship': $sv = is_array( $sub_val ) ? json_encode( $sub_val ) : '[]'; break;
                                case 'get_terms':   $sv = ! empty( $sub_field['multiple'] ) && is_array( $sub_val ) ? json_encode( $sub_val ) : sanitize_text_field( (string) $sub_val ); break;
                                default:             $sv = sanitize_text_field( (string) $sub_val );
                            }
                            update_term_meta( $term_id, $sub_id, $sv );
                        }
                        continue 2;

                    default:
                        $sanitized = sanitize_text_field( $_POST[ $field_id ] );
                }

                update_term_meta( $term_id, $field_id, $sanitized );
            }
        }
        
    }
    
}