<?php

  if ( ! class_exists( 'ColtmanCreateUserMeta' ) ) {

    /**
     * Adds a custom fields section to the WordPress user profile admin pages.
     *
     * ```php
     * new ColtmanCreateUserMeta([
     *     'title'       => 'Información adicional',
     *     'description' => 'Campos adicionales del perfil de usuario.',
     *     'fields'      => [
     *         ['label' => 'Empresa',    'id' => 'user_company', 'type' => 'text',     'default' => ''],
     *         ['label' => 'Teléfono',   'id' => 'user_phone',   'type' => 'text',     'default' => ''],
     *         ['label' => 'Newsletter', 'id' => 'newsletter',   'type' => 'checkbox'],
     *         ['label' => 'Avatar',     'id' => 'user_avatar',  'type' => 'media',    'return' => 'url'],
     *     ],
     * ]);
     * ```
     *
     * @package Coltman
     * @since   1.0.0
     */
    class ColtmanCreateUserMeta {

        private $config;
        public $coltmanInputs;

        /**
         * Constructor.
         *
         * @param array $config Configuración de la sección y campos.
         */
        public function __construct( $config ) {
            $this->config = $config;

            // Instancia la clase de campos si existe
            $this->coltmanInputs = class_exists( 'ColtmanInputFields' ) ? new ColtmanInputFields() : false;

            // Hooks para mostrar los campos en el perfil de usuario
            add_action( 'show_user_profile', [ $this, 'add_user_meta_section' ] );
            add_action( 'edit_user_profile', [ $this, 'add_user_meta_section' ] );

            // Hooks para guardar los datos
            add_action( 'personal_options_update', [ $this, 'save_user_meta' ] );
            add_action( 'edit_user_profile_update', [ $this, 'save_user_meta' ] );

            // Carga scripts y estilos solo en las páginas de perfil
            add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
        }

        /**
         * Carga los scripts y estilos necesarios (similar a la clase original).
         *
         * @param string $hook Hook actual de la página de admin.
         */
        public function admin_enqueue_scripts( $hook ) {
            // Solo cargar en páginas de perfil de usuario
            if ( ! in_array( $hook, [ 'profile.php', 'user-edit.php' ] ) ) {
                return;
            }

            wp_enqueue_media();
            wp_enqueue_script( 'wp-color-picker' );
            wp_enqueue_style( 'wp-color-picker' );
            wp_register_style( 'select2css', COLTMAN_ASSETS_URL . '/libs/select2/select2.min.css', false, '4.1.0', 'all' );
            wp_register_script( 'select2', COLTMAN_ASSETS_URL . '/libs/select2/select2.min.js', [ 'jquery' ], '4.1.0', true );
            wp_enqueue_style( 'select2css' );
            wp_enqueue_style( 'coltman-admin', COLTMAN_ASSETS_URL . '/css/admin.css', [], '1.6.0' );
            wp_enqueue_script( 'select2' );
            wp_enqueue_style( 'leaflet',  COLTMAN_ASSETS_URL . '/libs/leaflet/leaflet.min.css', [], '1.9.4' );
            wp_enqueue_script( 'leaflet', COLTMAN_ASSETS_URL . '/libs/leaflet/leaflet.min.js', [], '1.9.4', true );
            wp_enqueue_script( 'coltman-media', COLTMAN_ASSETS_URL . '/js/media.js', [ 'jquery', 'select2', 'jquery-ui-sortable' ], '1.6.0', true );
            wp_localize_script( 'coltman-media', 'coltmanVars', [ 'assetsUrl' => COLTMAN_ASSETS_URL ] );
        }

        /**
         * Renderiza la sección completa dentro del perfil de usuario.
         *
         * @param WP_User $user Objeto del usuario actual.
         */
        public function add_user_meta_section( $user ) {
            ?>
            <h2><?php echo esc_html( $this->config['title'] ); ?></h2>
            <?php if ( ! empty( $this->config['description'] ) ) : ?>
                <p class="description"><?php echo esc_html( $this->config['description'] ); ?></p>
            <?php endif; ?>

            <?php wp_nonce_field( 'coltman_user_meta_save_' . $user->ID, 'coltman_user_meta_nonce' ); ?>
            <table class="form-table">
                <tbody>
                <?php
                foreach ( $this->config['fields'] as $field ) {
                    $this->render_field_row( $field, $user );
                }
                ?>
                </tbody>
            </table>
            <?php
        }

        /**
         * Renderiza una fila de campo (etiqueta + input).
         *
         * @param array   $field Configuración del campo.
         * @param WP_User $user  Objeto del usuario.
         */
        private function render_field_row( $field, $user ) {
            $value   = $this->get_user_meta_value( $user->ID, $field );
            $checked = $this->get_checked( $user->ID, $field );
            if ( isset( $field['type'] ) && $field['type'] === 'media' ) {
                $field['_alt_value'] = (string) get_user_meta( $user->ID, $field['id'] . '_alt', true );
            }
            ?>
            <tr>
                <th>
                    <label for="<?php echo esc_attr( $field['id'] ); ?>">
                        <?php echo esc_html( $field['label'] ); ?>
                    </label>
                </th>
                <td>
                    <?php $this->render_field( $field, $value, $checked ); ?>
                    <?php if ( ! empty( $field['description'] ) ) : ?>
                        <p class="description"><?php echo esc_html( $field['description'] ); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <?php
        }

        /**
         * Renderiza el input correspondiente usando ColtmanInputFields.
         *
         * @param array  $field   Configuración del campo.
         * @param mixed  $value   Valor actual.
         * @param string $checked Atributo checked para checkboxes.
         */
        private function render_field( $field, $value, $checked ) {
            if ( ! $this->coltmanInputs ) {
                // Fallback básico si no existe la clase de inputs
                echo '<input type="text" name="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $value ) . '" class="regular-text">';
                return;
            }

            switch ( $field['type'] ) {
                case 'checkbox':
                    $this->coltmanInputs->checkbox( $field, $checked );
                    break;
                case 'number':
                case 'date':
                    $this->coltmanInputs->input_minmax( $field, $value );
                    break;
                case 'get_terms':
                    $this->coltmanInputs->get_terms( $field, $value );
                    break;
                case 'editor':
                    $this->coltmanInputs->editor( $field, $value );
                    break;
                case 'media':
                    $this->coltmanInputs->media( $field, $value );
                    break;
                case 'gallery':
                    $this->coltmanInputs->gallery_input( $field, $value );
                    break;
                case 'list':
                    $this->coltmanInputs->list_input( $field, $value );
                    break;
                case 'select':
                    $this->coltmanInputs->select( $field, $value );
                    break;
                case 'textarea':
                    $this->coltmanInputs->textarea( $field, $value );
                    break;
                case 'get_posts':
                    $this->coltmanInputs->get_posts( $field, $value );
                    break;
                case 'accordion':
                    $this->coltmanInputs->accordion( $field, $value );
                    break;
                case 'relationship':
                    $this->coltmanInputs->relationship( $field, $value );
                    break;
                case 'color':
                    $this->coltmanInputs->color( $field, $value );
                    break;
                case 'repeater':
                    $this->coltmanInputs->repeater( $field, $value );
                    break;
                case 'map':
                    $this->coltmanInputs->map( $field, $value );
                    break;
                default:
                    $this->coltmanInputs->input( $field, $value );
            }
        }

        /**
         * Obtiene el valor del meta para un usuario.
         *
         * @param int   $user_id ID del usuario.
         * @param array $field   Configuración del campo.
         * @return mixed Valor almacenado o por defecto.
         */
        private function get_user_meta_value( $user_id, $field ) {
            $value = get_user_meta( $user_id, $field['id'], true );
            if ( '' === $value && isset( $field['default'] ) ) {
                $value = $field['default'];
            }
            return $value;
        }

        /**
         * Determina si un checkbox debe aparecer marcado.
         *
         * @param int   $user_id ID del usuario.
         * @param array $field   Configuración del campo.
         * @return string 'checked' o cadena vacía.
         */
        private function get_checked( $user_id, $field ) {
            $value = get_user_meta( $user_id, $field['id'], true );
            if ( 'on' === $value || '1' === $value ) {
                return 'checked';
            }
            if ( isset( $field['checked'] ) && true === $field['checked'] ) {
                return 'checked';
            }
            return '';
        }

        /**
         * Guarda los metadatos del usuario.
         *
         * @param int $user_id ID del usuario.
         */
        public function save_user_meta( $user_id ) {
            if ( ! isset( $_POST['coltman_user_meta_nonce'] ) ||
                 ! wp_verify_nonce( $_POST['coltman_user_meta_nonce'], 'coltman_user_meta_save_' . $user_id ) ) {
                return false;
            }
            if ( ! current_user_can( 'edit_user', $user_id ) ) {
                return false;
            }

            foreach ( $this->config['fields'] as $field ) {
                $field_id = $field['id'];
                $type     = $field['type'];

                // Obtener valor del POST (para checkboxes no marcados será cadena vacía)
                $value = isset( $_POST[ $field_id ] ) ? $_POST[ $field_id ] : '';

                // Sanitización según el tipo
                switch ( $type ) {
                    case 'media':
                        $value = isset( $field['return'] ) && $field['return'] === 'id'
                            ? (string) absint( $value )
                            : esc_url_raw( (string) $value );
                        $_alt_key = $field_id . '_alt';
                        update_user_meta( $user_id, $_alt_key,
                            sanitize_text_field( isset( $_POST[ $_alt_key ] ) ? (string) $_POST[ $_alt_key ] : '' ) );
                        break;
                    case 'get_posts':
                    case 'relationship':
                        $value = is_array( $value ) ? json_encode( $value ) : '[]';
                        break;
                    case 'get_terms':
                        $is_multiple = ! ( isset( $field['multiple'] ) && ! $field['multiple'] );
                        $value = $is_multiple
                            ? ( is_array( $value ) ? json_encode( $value ) : '[]' )
                            : sanitize_text_field( (string) $value );
                        break;
                    case 'gallery':
                    case 'accordion':
                    case 'list':
                        break;
                    case 'repeater':
                        if ( is_array( $value ) ) {
                            $rows = [];
                            foreach ( $value as $row ) {
                                if ( ! is_array( $row ) ) continue;
                                $clean = [];
                                foreach ( isset( $field['sub_fields'] ) ? $field['sub_fields'] : [] as $sub ) {
                                    $sv = isset( $row[ $sub['id'] ] ) ? $row[ $sub['id'] ] : '';
                                    switch ( isset( $sub['type'] ) ? $sub['type'] : 'text' ) {
                                        case 'email':    $clean[ $sub['id'] ] = sanitize_email( (string) $sv );         break;
                                        case 'textarea': $clean[ $sub['id'] ] = wp_kses_post( (string) $sv );          break;
                                        case 'url':      $clean[ $sub['id'] ] = esc_url_raw( (string) $sv );           break;
                                        default:         $clean[ $sub['id'] ] = sanitize_text_field( (string) $sv );
                                    }
                                }
                                if ( array_filter( $clean ) ) $rows[] = $clean;
                            }
                            $value = json_encode( $rows );
                        } else {
                            $value = '[]';
                        }
                        break;
                    case 'map':
                        if ( is_string( $value ) && $value !== '' ) {
                            $raw   = json_decode( wp_unslash( $value ), true );
                            if ( is_array( $raw ) ) {
                                $lat   = isset( $raw['lat'] )  ? (float) $raw['lat']  : null;
                                $lng   = isset( $raw['lng'] )  ? (float) $raw['lng']  : null;
                                $mzoom = isset( $raw['zoom'] ) ? (int)   $raw['zoom'] : 13;
                                if ( $lat !== null && $lat >= -90 && $lat <= 90 && $lng !== null && $lng >= -180 && $lng <= 180 ) {
                                    $value = wp_json_encode( [ 'lat' => $lat, 'lng' => $lng, 'zoom' => $mzoom ] );
                                } else {
                                    $value = '';
                                }
                            } else {
                                $value = '';
                            }
                        }
                        break;
                    case 'color':
                        $value = sanitize_text_field( (string) $value );
                        break;
                    case 'checkbox':
                        break;
                    case 'editor':
                        $value = wp_filter_post_kses( $value );
                        break;
                    case 'email':
                        $value = sanitize_email( $value );
                        break;
                    case 'textarea':
                        $value = wp_kses_post( $value );
                        break;
                    case 'url':
                        $value = esc_url_raw( $value );
                        break;
                    default:
                        $value = sanitize_text_field( $value );
                }

                update_user_meta( $user_id, $field_id, $value );
            }
        }
    }
}
