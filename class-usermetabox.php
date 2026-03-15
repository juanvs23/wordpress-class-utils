<?php

  if ( ! class_exists( 'ColtmanCreateUserMeta' ) ) {

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

            wp_enqueue_media(); // Para campos de imagen/galería

            // Color picker
            wp_enqueue_script( 'wp-color-picker' );
            wp_enqueue_style( 'wp-color-picker' );

            // Select2 (si se usa en algún campo)
            wp_register_style( 'select2css', '//cdnjs.cloudflare.com/ajax/libs/select2/3.4.8/select2.css', false, '1.0', 'all' );
            wp_register_script( 'select2', '//cdnjs.cloudflare.com/ajax/libs/select2/3.4.8/select2.js', [ 'jquery' ], '1.0', true );
            wp_enqueue_style( 'select2css' );
            wp_enqueue_script( 'select2' );

            // Si tu tema/plugin tiene scripts personalizados (por ejemplo, media.js para galerías)
            // Puedes encolarlos aquí. Ajusta la ruta según tu implementación.
            // wp_enqueue_script( 'user-meta-media', get_stylesheet_directory_uri() . '/classes/assets/js/media.js', [], '1.0', true );
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
            // Verificar permisos (opcional pero recomendado)
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
                    case 'get_posts':
                        // Se espera un array, lo guardamos como JSON
                        $value = is_array( $value ) ? json_encode( $value ) : '[]';
                        break;
                    case 'checkbox':
                        // Para checkbox, si está presente suele ser 'on', sino ya es ''
                        break;
                    case 'editor':
                        $value = wp_filter_post_kses( $value );
                        break;
                    case 'email':
                        $value = sanitize_email( $value );
                        break;
                    case 'textarea':
                        $value = sanitize_textarea_field( $value );
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
