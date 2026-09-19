<?php
if ( ! defined( 'ABSPATH' ) ) exit;

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
            // Un grupo no es un input propio: se renderiza como bloque con sus
            // sub-campos (mismos meta keys que los datos ya guardados).
            if ( isset( $field['type'] ) && $field['type'] === 'group' ) {
                $this->render_group( $field['id'], $field, (int) $user->ID );
                return;
            }
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
         * Esquema dinámico del grupo (campos añadidos desde el field manager).
         *
         * Compartido con metabox/term meta: la opción es global por id de grupo.
         *
         * @param string $group_id Id del grupo.
         * @return array<int, array{key: string, type: string, label: string}>
         */
        private function get_group_schema( string $group_id ): array {
            $schema = get_option( '_coltman_group_schema_' . $group_id, [] );
            return is_array( $schema ) ? $schema : [];
        }

        /**
         * Renderiza un campo tipo 'group' con todos sus sub-campos.
         *
         * Espejo del render de term meta, con los valores leídos de usermeta.
         * Los sub-campos usan su propio id como meta key (no el id del grupo).
         *
         * @param string  $group_id Id del grupo.
         * @param array   $field    Configuración del grupo.
         * @param WP_User $user     Usuario en edición.
         */
        private function render_group( string $group_id, array $field, int $user_id ): void {
            $gid            = esc_attr( $group_id );
            $label          = isset( $field['label'] ) ? esc_html( $field['label'] ) : '';
            $static_fields  = isset( $field['fields'] ) ? $field['fields'] : [];
            $dynamic_schema = $this->get_group_schema( $group_id );
            $static_ids     = array_column( $static_fields, 'id' );
            $nonce          = wp_create_nonce( 'coltman_group_schema' );

            echo '<tr class="coltman-group-row"><th scope="row">' . $label . '</th><td>';
            echo '<div class="coltman-group-header">';
            echo '<span class="coltman-group-label">' . $label . '</span>';
            echo '<button type="button" class="coltman-group-toggle" data-group="' . $gid . '" aria-expanded="true">&#9650;</button>';
            echo '</div>';
            if ( ! empty( $field['description'] ) ) {
                echo '<p class="w-full mb-2 text-sm text-gray-500 description">' . esc_html( $field['description'] ) . '</p>';
            }
            echo '<div class="coltman-group-body" id="coltman-group-' . $gid . '">';

            foreach ( $static_fields as $sub_field ) {
                $sub_id   = $sub_field['id'];
                $sub_val  = (string) get_user_meta( $user_id, $sub_id, true );
                if ( '' === $sub_val && isset( $sub_field['default'] ) ) {
                    $sub_val = (string) $sub_field['default'];
                }
                echo '<div class="coltman-group-field-row">';
                echo '<label for="' . esc_attr( $sub_id ) . '">' . ( isset( $sub_field['label'] ) ? esc_html( $sub_field['label'] ) : '' ) . '</label>';
                $this->render_field( $sub_field, $sub_val, '' );
                echo '</div>';
            }

            foreach ( $dynamic_schema as $df ) {
                if ( in_array( $df['key'], $static_ids, true ) ) {
                    continue;
                }
                $sub     = [ 'id' => $df['key'], 'type' => $df['type'], 'label' => $df['label'] ];
                $dyn_val = (string) get_user_meta( $user_id, $df['key'], true );
                echo '<div class="coltman-group-field-row" data-dynamic-key="' . esc_attr( $df['key'] ) . '">';
                echo '<label for="' . esc_attr( $df['key'] ) . '">' . esc_html( $df['label'] ) . '</label>';
                $this->render_field( $sub, $dyn_val, '' );
                echo '</div>';
            }

            $dyn_visible = array_values( array_filter( $dynamic_schema, static fn( $df ) => ! in_array( $df['key'], $static_ids, true ) ) );
            echo '<div class="coltman-field-manager" data-group="' . $gid . '" data-nonce="' . esc_attr( $nonce ) . '">';
            echo '<div class="coltman-field-manager-toggle-row">';
            echo '<button type="button" class="coltman-field-manager-toggle" aria-expanded="false">&#9881; ' . esc_html__( 'Manage fields', 'coltman' ) . '</button>';
            echo '</div>';
            echo '<div class="coltman-field-manager-panel" style="display:none">';
            echo '<div class="coltman-dynamic-fields-list">';
            if ( empty( $dyn_visible ) ) {
                echo '<p class="coltman-no-dynamic-fields">' . esc_html__( 'No dynamic fields added yet.', 'coltman' ) . '</p>';
            } else {
                foreach ( $dyn_visible as $df ) {
                    echo '<div class="coltman-dynamic-field-item" data-key="' . esc_attr( $df['key'] ) . '">';
                    echo '<span class="coltman-dynamic-field-info">' . esc_html( $df['type'] ) . ' &middot; ' . esc_html( $df['label'] ) . ' <code>' . esc_html( $df['key'] ) . '</code></span>';
                    echo '<button type="button" class="coltman-remove-dynamic-field" data-key="' . esc_attr( $df['key'] ) . '">&#10005;</button>';
                    echo '</div>';
                }
            }
            echo '</div>';
            echo '<div class="coltman-add-field-form">';
            echo '<select class="coltman-new-field-type">';
            echo '<option value="text">' . esc_html__( 'Text', 'coltman' ) . '</option>';
            echo '<option value="textarea">' . esc_html__( 'Textarea', 'coltman' ) . '</option>';
            echo '<option value="number">' . esc_html__( 'Number', 'coltman' ) . '</option>';
            echo '<option value="email">' . esc_html__( 'Email', 'coltman' ) . '</option>';
            echo '<option value="url">URL</option>';
            echo '</select>';
            echo '<input type="text" class="coltman-new-field-key" placeholder="' . esc_attr__( 'field_key', 'coltman' ) . '">';
            echo '<input type="text" class="coltman-new-field-label" placeholder="' . esc_attr__( 'Field Label', 'coltman' ) . '">';
            echo '<button type="button" class="coltman-add-dynamic-field">+ ' . esc_html__( 'Add field', 'coltman' ) . '</button>';
            echo '</div>';
            echo '<p class="coltman-field-manager-note">' . esc_html__( '* Dynamic fields apply to all users with this group.', 'coltman' ) . '</p>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</td></tr>';
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
                    case 'group':
                        // El grupo no tiene meta propia: cada sub-campo guarda con
                        // su propio id (mismo esquema que metabox y term meta).
                        $all_sub_fields = isset( $field['fields'] ) ? $field['fields'] : [];
                        $dyn_schema     = $this->get_group_schema( $field_id );
                        $static_sub_ids = array_column( $all_sub_fields, 'id' );
                        foreach ( $dyn_schema as $df ) {
                            if ( ! in_array( $df['key'], $static_sub_ids, true ) ) {
                                $all_sub_fields[] = [ 'id' => $df['key'], 'type' => $df['type'], 'label' => $df['label'] ];
                            }
                        }
                        foreach ( $all_sub_fields as $sub_field ) {
                            $sub_id = isset( $sub_field['id'] ) ? $sub_field['id'] : '';
                            if ( '' === $sub_id || ! isset( $_POST[ $sub_id ] ) ) {
                                continue;
                            }
                            $sub_val = $_POST[ $sub_id ];
                            switch ( isset( $sub_field['type'] ) ? $sub_field['type'] : 'text' ) {
                                case 'textarea':     update_user_meta( $user_id, $sub_id, wp_kses_post( (string) $sub_val ) ); break;
                                case 'email':        update_user_meta( $user_id, $sub_id, sanitize_email( (string) $sub_val ) ); break;
                                case 'url':          update_user_meta( $user_id, $sub_id, esc_url_raw( (string) $sub_val ) ); break;
                                case 'editor':       update_user_meta( $user_id, $sub_id, wp_filter_post_kses( (string) $sub_val ) ); break;
                                case 'get_posts':
                                case 'relationship': update_user_meta( $user_id, $sub_id, is_array( $sub_val ) ? json_encode( $sub_val ) : '[]' ); break;
                                default:             update_user_meta( $user_id, $sub_id, sanitize_text_field( (string) $sub_val ) );
                            }
                        }
                        continue 2;
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
