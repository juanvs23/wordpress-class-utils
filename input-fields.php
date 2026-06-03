<?php
if(!class_exists('ColtmanInputFields')){
    class ColtmanInputFields {
    
    
        
        /**
         * Echo a checkbox input element.
         *
         * @param array $field {
         *     An array of field arguments.
         *
         *     @type string $id         The field ID.
         *     @type string $description The field description.
         * }
         * @param string $checked Optional. The value attribute of the checkbox.
         *                        Defaults to an empty string.
         */
        public function checkbox( $field, $checked = '' ) {
            printf(
                '<label class="rwp-checkbox-label"><input %s id="%s" name="%s" type="checkbox"> %s</label>',
                $checked,
                esc_attr( $field['id'] ), esc_attr( $field['id'] ),
                isset( $field['description'] ) ? esc_html( $field['description'] ) : ''
            );
        }
        
        /**
         * Echo a WYSIWYG input element.
         *
         * @param array $field {
         *     An array of field arguments.
         *
         *     @type string $id The field ID.
         * }
         * @param string $value Optional. The field value.
         *                      Defaults to an empty string.
         */
        public function editor( $field, $value = '' ) {
            wp_editor( $value, $field['id'], [
                'wpautop' => isset( $field['wpautop'] ) ? true : false,
                'media_buttons' => isset( $field['media-buttons'] ) ? true : false,
                'textarea_name' => $field['id'],
                'textarea_rows' => isset( $field['rows'] ) ? $field['rows'] : 20,
                'teeny' => isset( $field['teeny'] ) ? true : false,
                'quicktags' => true,
            ] );
        }

        /**
         * Echoes a multiple-select populated with published posts of a given post type.
         *
         * Value is expected to be a JSON-encoded array of post IDs. Uses Select2 for the UI.
         *
         * @param array<string, mixed> $field Field config — requires 'post_type' key.
         * @param string               $value JSON-encoded array of selected post IDs (e.g. '[1,5,12]').
         * @return void
         */
        public function get_posts( $field, $value = '' ): void {
            $this->relationship( $field, $value );
        }


        /**
         * Echoes a single-select populated with all terms of a given taxonomy.
         *
         * @param array<string, mixed> $field Field config — requires 'taxonomy' key.
         * @param string               $value Selected term_id as string.
         * @return void
         */
        public function get_terms( $field, $value = '' ): void {
            $raw_tax     = isset( $field['taxonomy'] ) ? $field['taxonomy'] : 'category';
            $taxonomy    = is_array( $raw_tax )
                ? implode( ',', $raw_tax )
                : implode( ',', array_map( 'trim', explode( ',', $raw_tax ) ) );
            $multiple    = ! ( isset( $field['multiple'] ) && ! $field['multiple'] );
            $placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : __( 'Search terms…', COLTMAN_TEXT_DOMAIN );
            $nonce       = wp_create_nonce( 'coltman_term_search' );

            $selected_ids = [];
            if ( $value !== '' && $value !== null ) {
                if ( $multiple ) {
                    $decoded      = json_decode( $value, true );
                    // backward compat: plain string ID stored before multiple was the default
                    $selected_ids = is_array( $decoded ) ? $decoded : ( $value !== '' ? [ $value ] : [] );
                } else {
                    $selected_ids = [ $value ];
                }
            }
            ?>
            <select id="<?php echo esc_attr( $field['id'] ); ?>"
                    name="<?php echo esc_attr( $field['id'] ); ?><?php echo $multiple ? '[]' : ''; ?>"
                    <?php echo $multiple ? 'multiple="multiple"' : ''; ?>
                    class="block w-full js-term-select regular-text min-h-10"
                    data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>"
                    data-nonce="<?php echo esc_attr( $nonce ); ?>"
                    data-placeholder="<?php echo esc_attr( $placeholder ); ?>"
                    <?php echo ! $multiple ? 'data-allow-clear="1"' : ''; ?>>
                <?php foreach ( $selected_ids as $term_id ) :
                    $term = get_term( (int) $term_id );
                    if ( ! $term || is_wp_error( $term ) ) continue;
                ?>
                <option value="<?php echo esc_attr( (string) $term_id ); ?>" selected="selected"><?php echo esc_html( $term->name ); ?></option>
                <?php endforeach; ?>
            </select>
            <?php
        }

       
    
        /**
         * Echoes a generic <input> element.
         *
         * Forces type to 'text' when the field type is 'media' or 'accordion'.
         * Supports optional 'class' and 'pattern' field keys.
         *
         * @param array<string, mixed> $field Field config — requires 'id' and 'type'.
         * @param string               $value Current field value.
         * @return void
         */
        public function input( $field, $value = '' ) {
            if ( $field['type'] === 'media' || $field['type'] === 'accordion' ) {
                $field['type'] = 'text';
            }
            printf(
                '<input class="regular-text block w-full min-h-10 %s" id="%s" name="%s" %s type="%s" value="%s">',
                esc_attr( isset( $field['class'] ) ? $field['class'] : '' ),
                esc_attr( $field['id'] ), esc_attr( $field['id'] ),
                isset( $field['pattern'] ) ? 'pattern="' . esc_attr( $field['pattern'] ) . '"' : '',
                esc_attr( $field['type'] ),
                esc_attr( $value )
            );
        }
    
    
        /**
         * Echoes a media picker field: thumbnail preview + readonly URL input + Upload/Clear buttons.
         *
         * Thumbnail is shown automatically when the stored value is an image URL.
         * JS (media.js) handles selection, preview update and clearing.
         *
         * @param array<string, mixed> $field Field config — supports 'modal-button', 'modal-title', 'return', 'button-text', 'class'.
         * @param string               $value Current URL or ID value.
         * @return void
         */
        public function media( $field, $value = '' ) {
            $extra_class = isset( $field['class'] )        ? $field['class']                                        : '';
            $return      = isset( $field['return'] )       ? $field['return']                                       : 'url';
            $btn_text    = isset( $field['button-text'] )  ? $field['button-text']                                  : __( 'Upload', COLTMAN_TEXT_DOMAIN );
            $modal_btn   = isset( $field['modal-button'] ) ? $field['modal-button']                                 : __( 'Select this file', COLTMAN_TEXT_DOMAIN );
            $modal_title = isset( $field['modal-title'] )  ? $field['modal-title']                                  : __( 'Choose a file', COLTMAN_TEXT_DOMAIN );
            $thumb_src = '';
            $is_image  = false;
            if ( $return === 'url' && $value !== '' ) {
                $is_image  = (bool) preg_match( '/\.(jpg|jpeg|png|gif|webp|svg)(\?.*)?$/i', $value );
                $thumb_src = $is_image ? $value : '';
            } elseif ( $return === 'id' && $value !== '' && is_numeric( $value ) ) {
                $thumb_src = wp_get_attachment_image_url( (int) $value, 'thumbnail' ) ?: '';
                $is_image  = $thumb_src !== '';
            }
            ?>
            <div class="coltman-media">
                <div class="coltman-media-preview">
                    <img class="coltman-media-thumb<?php echo $is_image ? ' has-image' : ''; ?>"
                         src="<?php echo $is_image ? esc_url( $thumb_src ) : ''; ?>"
                         alt="">
                    <span class="coltman-media-placeholder"<?php echo $is_image ? ' style="display:none"' : ''; ?>>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </span>
                </div>
                <div class="coltman-media-body">
                    <input class="regular-text block w-full coltman-media-url<?php echo $extra_class ? ' ' . esc_attr( $extra_class ) : ''; ?>"
                           id="<?php echo esc_attr( $field['id'] ); ?>"
                           name="<?php echo esc_attr( $field['id'] ); ?>"
                           type="text"
                           value="<?php echo esc_attr( $value ); ?>"
                           placeholder="<?php esc_attr_e( 'No file selected', COLTMAN_TEXT_DOMAIN ); ?>"
                           readonly>
                    <input class="regular-text block w-full coltman-media-alt"
                           id="<?php echo esc_attr( $field['id'] ); ?>_alt"
                           name="<?php echo esc_attr( $field['id'] ); ?>_alt"
                           type="text"
                           value="<?php echo esc_attr( isset( $field['_alt_value'] ) ? $field['_alt_value'] : '' ); ?>"
                           placeholder="<?php esc_attr_e( 'Alt text', COLTMAN_TEXT_DOMAIN ); ?>">
                    <div class="coltman-media-actions">
                        <button type="button"
                                class="button rwp-media-toggle coltman-media-btn"
                                data-modal-button="<?php echo esc_attr( $modal_btn ); ?>"
                                data-modal-title="<?php echo esc_attr( $modal_title ); ?>"
                                data-return="<?php echo esc_attr( $return ); ?>"
                                id="<?php echo esc_attr( $field['id'] ); ?>_button"
                                name="<?php echo esc_attr( $field['id'] ); ?>_button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <?php echo esc_html( $btn_text ); ?>
                        </button>
                        <button type="button"
                                class="coltman-media-clear<?php echo $value === '' ? ' hidden' : ''; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            <?php esc_html_e( 'Clear', COLTMAN_TEXT_DOMAIN ); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php
        }
        /**
         * Echoes a <textarea> element.
         *
         * @param array<string, mixed> $field Field config — supports 'rows' (default 5) and 'placeholder'.
         * @param string               $value Current field value (not escaped — caller must ensure safe content).
         * @return void
         */
        public function textarea( $field, $value = '' ) {
           $placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
            printf(
                '<textarea class="block w-full regular-text min-h-10"  rows="%d" placeholder="%s" id="%s" name="%s">%s</textarea>',
                isset( $field['rows'] ) ? (int) $field['rows'] : 5,
                esc_attr( $placeholder ),
                esc_attr( $field['id'] ),
                esc_attr( $field['id'] ),
                esc_textarea( $value )
            );
        }
    
        /**
         * Echoes a numeric or date <input> with optional min, max and step attributes.
         *
         * @param array<string, mixed> $field Field config — supports 'min', 'max', 'step'.
         * @param string               $value Current field value.
         * @return void
         */
        public function input_minmax( $field, $value = '' ) {
            printf(
                '<input class="block w-full regular-text min-h-10" id="%s" %s %s name="%s" %s type="%s" value="%s">',
                esc_attr( $field['id'] ),
                isset( $field['max'] )  ? 'max="'  . esc_attr( $field['max'] )  . '"' : '',
                isset( $field['min'] )  ? 'min="'  . esc_attr( $field['min'] )  . '"' : '',
                esc_attr( $field['id'] ),
                isset( $field['step'] ) ? 'step="' . esc_attr( $field['step'] ) . '"' : '',
                esc_attr( $field['type'] ),
                esc_attr( $value )
            );
        }
    
        /**
         * Echoes a repeatable gallery field.
         *
         * Each item stores a JSON object: {id, url, alt, item, sizes, title}.
         * The full gallery is stored as a JSON array in a hidden input named $field['id'].
         * Requires media.js for add/remove item behaviour.
         *
         * @param array<string, mixed> $field Field config — supports 'modal-button', 'modal-title', 'return', 'button-text'.
         * @param string               $value JSON-encoded array of gallery item objects.
         * @return void
         */
        public function gallery_input( $field, $value = '' ) {
            $modal_button = isset( $field['modal-button'] ) ? $field['modal-button'] : __( 'Select this file', COLTMAN_TEXT_DOMAIN );
            $modal_title = isset( $field['modal-title'] ) ? $field['modal-title'] : __( 'Choose a file', COLTMAN_TEXT_DOMAIN );
            $return = isset( $field['return'] ) ? $field['return']: 'url';
                
            $text_button = isset( $field['button-text'] ) ? $field['button-text'] : __( 'Upload', COLTMAN_TEXT_DOMAIN );
            $value = !is_null( $value ) && $value !='' ? json_decode($value) : [];
            ?>
            <div class="coltman-gallery">
                <input type="hidden" class="gallery-data" name="<?php echo esc_attr( $field['id'] ); ?>" id="<?php echo esc_attr( $field['id'] ); ?>" value='<?php echo esc_attr( json_encode( $value ) ); ?>'>
                <div class="coltman-gallery-list gallery-container gallery-sortable"
                    data-buttonmodal="<?php echo esc_attr( $modal_button ); ?>"
                    data-buttonmodaltitle="<?php echo esc_attr( $modal_title ); ?>"
                    data-buttonreturn="<?php echo esc_attr( $return ); ?>">
                    <?php if ( count( $value ) > 0 ) :
                        foreach ( $value as $item ) :
                            $alt_val = isset( $item->alt ) ? $item->alt : '';
                            $has_img = ! empty( $item->url ) ? ' has-image' : '';
                    ?>
                    <div class="coltman-gallery-item gallery-item" data-item="<?php echo esc_attr( $item->item ); ?>">
                        <span class="gallery-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', COLTMAN_TEXT_DOMAIN ); ?>">&#8942;</span>
                        <div class="coltman-gallery-thumb-wrap">
                            <img class="coltman-gallery-thumb<?php echo $has_img; ?>"
                                 src="<?php echo esc_url( $item->url ); ?>"
                                 alt="<?php echo esc_attr( $alt_val ); ?>">
                            <span class="coltman-gallery-thumb-placeholder" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            </span>
                        </div>
                        <div class="coltman-gallery-fields get-image">
                            <div class="coltman-gallery-url-row">
                                <input type="text" class="regular-text image-url" value="<?php echo esc_attr( $item->url ); ?>" placeholder="<?php esc_attr_e( 'Image URL', COLTMAN_TEXT_DOMAIN ); ?>">
                                <button class="button coltman-gallery-upload rwp-media-toggle"
                                        data-modal-button="<?php echo esc_attr( $modal_button ); ?>"
                                        data-modal-title="<?php echo esc_attr( $modal_title ); ?>"
                                        data-return="<?php echo esc_attr( $return ); ?>"
                                        type="button"><?php echo esc_html( $text_button ); ?></button>
                            </div>
                            <input type="text" class="regular-text image-alt" placeholder="<?php esc_attr_e( 'Alt text', COLTMAN_TEXT_DOMAIN ); ?>" value="<?php echo esc_attr( $alt_val ); ?>">
                        </div>
                        <button type="button" class="coltman-gallery-remove remove-image" onclick="removeiTem(this)" title="<?php esc_attr_e( 'Remove', COLTMAN_TEXT_DOMAIN ); ?>">&#10005;</button>
                    </div>
                    <?php endforeach; ?>
                    <?php else : ?>
                    <div class="coltman-gallery-item gallery-item" data-item="<?php echo esc_attr( date('YmdHis') . mt_rand(1000, 9999) ); ?>">
                        <span class="gallery-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', COLTMAN_TEXT_DOMAIN ); ?>">&#8942;</span>
                        <div class="coltman-gallery-thumb-wrap">
                            <img class="coltman-gallery-thumb" src="" alt="">
                            <span class="coltman-gallery-thumb-placeholder" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            </span>
                        </div>
                        <div class="coltman-gallery-fields get-image">
                            <div class="coltman-gallery-url-row">
                                <input type="text" class="regular-text image-url" placeholder="<?php esc_attr_e( 'Image URL', COLTMAN_TEXT_DOMAIN ); ?>">
                                <button class="button coltman-gallery-upload rwp-media-toggle"
                                        data-modal-button="<?php echo esc_attr( $modal_button ); ?>"
                                        data-modal-title="<?php echo esc_attr( $modal_title ); ?>"
                                        data-return="<?php echo esc_attr( $return ); ?>"
                                        type="button"><?php echo esc_html( $text_button ); ?></button>
                            </div>
                            <input type="text" class="regular-text image-alt" placeholder="<?php esc_attr_e( 'Alt text', COLTMAN_TEXT_DOMAIN ); ?>">
                        </div>
                        <button type="button" class="coltman-gallery-remove remove-image" onclick="removeiTem(this)" title="<?php esc_attr_e( 'Remove', COLTMAN_TEXT_DOMAIN ); ?>">&#10005;</button>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="coltman-gallery-footer">
                    <button type="button" onclick="addiTemImage(this)" class="button coltman-gallery-add add-image">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        <?php esc_html_e( 'Add image', COLTMAN_TEXT_DOMAIN ); ?>
                    </button>
                </div>
            </div>
            
            <?php
        }

        /**
         * Echoes a repeatable list field with textarea items.
         *
         * Each item stores a JSON object: {item, text}.
         * The full list is stored as a JSON array in a hidden input named $field['id'].
         * Requires media.js for add/remove/sort behaviour.
         *
         * @param array<string, mixed> $field Field config.
         * @param string               $value JSON-encoded array of list item objects.
         * @return void
         */
        public function list_input( $field, $value = '' ) {
            $value = !is_null( $value ) && $value !='' ? json_decode($value) : [];
            ?>
            <div class="coltman-list">
                <input type="hidden" class="list-data" name="<?php echo esc_attr( $field['id'] ); ?>" id="<?php echo esc_attr( $field['id'] ); ?>" value='<?php echo esc_attr( json_encode( $value ) ); ?>'>
                <div class="coltman-list-items list-container list-sortable">
                    <?php if ( count( $value ) > 0 ) :
                        foreach ( $value as $item ) :
                            $text_val = isset( $item->text ) ? $item->text : '';
                    ?>
                    <div class="coltman-list-item list-item" data-item="<?php echo esc_attr( $item->item ); ?>">
                        <span class="list-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', COLTMAN_TEXT_DOMAIN ); ?>">&#8942;</span>
                        <textarea class="list-textarea" placeholder="<?php esc_attr_e( 'Enter text...', COLTMAN_TEXT_DOMAIN ); ?>"><?php echo esc_textarea( $text_val ); ?></textarea>
                        <button type="button" class="coltman-list-remove" onclick="removeListItem(this)" title="<?php esc_attr_e( 'Remove', COLTMAN_TEXT_DOMAIN ); ?>">&#10005;</button>
                    </div>
                    <?php endforeach; ?>
                    <?php else : ?>
                    <div class="coltman-list-item list-item" data-item="<?php echo esc_attr( date('YmdHis') . mt_rand(1000, 9999) ); ?>">
                        <span class="list-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', COLTMAN_TEXT_DOMAIN ); ?>">&#8942;</span>
                        <textarea class="list-textarea" placeholder="<?php esc_attr_e( 'Enter text...', COLTMAN_TEXT_DOMAIN ); ?>"></textarea>
                        <button type="button" class="coltman-list-remove" onclick="removeListItem(this)" title="<?php esc_attr_e( 'Remove', COLTMAN_TEXT_DOMAIN ); ?>">&#10005;</button>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="coltman-list-footer">
                    <button type="button" onclick="addiTemList(this)" class="button coltman-list-add">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        <?php esc_html_e( 'Add item', COLTMAN_TEXT_DOMAIN ); ?>
                    </button>
                </div>
            </div>
            <?php
        }

        /**
         * Echoes a <select> element built from field options.
         *
         * @param array<string, mixed> $field Field config — requires 'options' array.
         * @param string               $value Currently selected value.
         * @return void
         */
        public function select( $field, $value = '' ) {

            printf(
                '<select id="%s" class="block w-full regular-text min-h-10" name="%s">%s</select>',
                $field['id'], $field['id'],
                $this->select_options( $field, $value )
            );
        }
    
        /**
         * Returns the selected="selected" attribute string when $selected is true.
         *
         * @param bool $selected Whether this option is selected.
         * @return string ' selected="selected"' or empty string.
         */
        private function select_selected(  bool $selected = false ) {
            if ( $selected ) {
                return ' selected="selected"';
            }
            return '';
        }
    
        /**
         * Builds and returns the <option> elements for a select field.
         *
         * Supports two option formats:
         * - Associative: ['key' => 'label']
         * - Array of arrays: [['value' => …, 'label' => …, 'selected' => bool]]
         *
         * @param array<string, mixed> $field Field config — requires 'options'.
         * @param string               $value Currently selected value.
         * @return string HTML string of <option> elements.
         */
        private function select_options( $field, $value  = '' ) {
            $options = '';
            if ( ! isset( $field['options'] ) || ! is_array( $field['options'] ) ) {
                return $options;
            }
            foreach ( $field['options'] as $key => $option ) {
                $opt_value = '';
                $opt_label = '';
                $opt_selected = false;

                if ( is_array( $option ) ) {
                    $opt_value = isset( $option['value'] ) ? $option['value'] : ( isset( $option[0] ) ? $option[0] : '' );
                    $opt_label = isset( $option['label'] ) ? $option['label'] : $opt_value;
                    $opt_selected = isset( $option['selected'] ) ? (bool) $option['selected'] : false;
                } else {
                    // support 'key' => 'label' or numeric => 'label'
                    $opt_value = is_string( $key ) ? $key : $option;
                    $opt_label = $option;
                }

                if ( $value !== '' && (string) $value === (string) $opt_value ) {
                    $opt_selected = true;
                }

                $options .= sprintf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr( $opt_value ),
                    $this->select_selected( $opt_selected ),
                    esc_html( $opt_label )
                );
            }
            return $options;
        }
    
        /**
         * Echoes a repeatable accordion field (title + content + optional image per item).
         *
         * Value is a JSON-encoded array of objects: [{id, title, content, image}].
         * Stored in a hidden input. Requires media.js for save/remove/add item behaviour.
         * To disable per-item images: set 'add_image' => 'false' (string) in the field config.
         *
         * @param array<string, mixed> $field Field config — supports 'label', 'id', 'add_image'.
         * @param string               $value JSON-encoded accordion items array.
         * @return void
         */
        /**
         * Renders a lightweight contenteditable WYSIWYG editor for accordion item content.
         * Uses a hidden <textarea> as the form value; the JS keeps them in sync.
         */
        private function accordion_editor( string $id, string $content ): void {
            ?>
            <div class="coltman-wysiwyg" data-for="<?php echo esc_attr( $id ); ?>">
                <div class="coltman-wysiwyg-toolbar" role="toolbar">
                    <select class="coltman-wysiwyg-headings" title="<?php esc_attr_e( 'Block format', COLTMAN_TEXT_DOMAIN ); ?>">
                        <option value="p"><?php esc_html_e( 'Normal', COLTMAN_TEXT_DOMAIN ); ?></option>
                        <option value="h2">H2</option>
                        <option value="h3">H3</option>
                        <option value="h4">H4</option>
                    </select>
                    <span class="coltman-wysiwyg-sep"></span>
                    <button type="button" class="coltman-wysiwyg-btn" data-cmd="bold"                title="<?php esc_attr_e( 'Bold',             COLTMAN_TEXT_DOMAIN ); ?>"><strong>B</strong></button>
                    <button type="button" class="coltman-wysiwyg-btn" data-cmd="italic"              title="<?php esc_attr_e( 'Italic',           COLTMAN_TEXT_DOMAIN ); ?>"><em>I</em></button>
                    <button type="button" class="coltman-wysiwyg-btn" data-cmd="underline"           title="<?php esc_attr_e( 'Underline',        COLTMAN_TEXT_DOMAIN ); ?>"><u>U</u></button>
                    <button type="button" class="coltman-wysiwyg-btn" data-cmd="strikeThrough"       title="<?php esc_attr_e( 'Strikethrough',    COLTMAN_TEXT_DOMAIN ); ?>"><s>S</s></button>
                    <span class="coltman-wysiwyg-sep"></span>
                    <button type="button" class="coltman-wysiwyg-btn" data-cmd="insertUnorderedList" title="<?php esc_attr_e( 'Bullet list',      COLTMAN_TEXT_DOMAIN ); ?>">&#8226; List</button>
                    <button type="button" class="coltman-wysiwyg-btn" data-cmd="insertOrderedList"   title="<?php esc_attr_e( 'Ordered list',     COLTMAN_TEXT_DOMAIN ); ?>">1. List</button>
                    <span class="coltman-wysiwyg-sep"></span>
                    <button type="button" class="coltman-wysiwyg-btn" data-cmd="createLink"          title="<?php esc_attr_e( 'Insert link',      COLTMAN_TEXT_DOMAIN ); ?>">Link</button>
                    <button type="button" class="coltman-wysiwyg-btn" data-cmd="unlink"              title="<?php esc_attr_e( 'Remove link',      COLTMAN_TEXT_DOMAIN ); ?>">Unlink</button>
                    <span class="coltman-wysiwyg-sep"></span>
                    <button type="button" class="coltman-wysiwyg-btn" data-cmd="removeFormat"        title="<?php esc_attr_e( 'Clear formatting', COLTMAN_TEXT_DOMAIN ); ?>">Clear</button>
                </div>
                <div class="coltman-wysiwyg-body"
                     contenteditable="true"
                     data-sync="<?php echo esc_attr( $id ); ?>"><?php echo wp_kses_post( $content ); ?></div>
            </div>
            <textarea id="<?php echo esc_attr( $id ); ?>"
                      name="<?php echo esc_attr( $id ); ?>"
                      class="input-content"
                      hidden><?php echo esc_textarea( $content ); ?></textarea>
            <?php
        }

        public function accordion($field, $value){
            $value      = isset( $value ) && $value !== '' && is_iterable( json_decode( $value ) ) ? json_decode( $value ) : [];
            $have_image = ! ( isset( $field['add_image'] ) && ( $field['add_image'] === false || $field['add_image'] === 'false' ) );
            ?>
            <div class="accordion flex flex-col gap-2 w-full">
                <input type="hidden" class="accordion-data" name="<?php echo esc_attr( $field['id'] ); ?>" id="<?php echo esc_attr( $field['id'] ); ?>" value='<?php echo esc_attr( json_encode( $value ) ); ?>'>
                <div class="flex flex-col gap-4 pb-3 accordion-container">

                    <?php if ( count( $value ) > 0 ) : ?>
                        <?php foreach ( $value as $item ) :
                            $id      = $item->id;
                            $title   = $item->title;
                            $content = $item->content;
                            $image   = $item->image;
                            $id_base = str_replace( '_parent', '', $id );
                        ?>
                        <div data-id="<?php echo esc_attr( $field['id'] ); ?>" id="<?php echo esc_attr( $id ); ?>"
                             class="accordion-item flex items-center justify-between gap-2 bg-slate-100 p-4">
                            <div class="w-10/12 accodeon-item-content flex flex-col gap-2">
                                <h3 style="margin:0 0 4px"><?php echo esc_html( $field['label'] . ' item' ); ?></h3>
                                <?php if ( $have_image ) : $this->media( [ 'id' => $id_base . '_image', 'type' => 'media', 'class' => 'image-url-accodeon', 'button-text' => 'Upload', 'return' => 'url', 'default' => '' ], $image ); endif; ?>
                                <input type="text" class="regular-text block w-full min-h-10 rounded input-title" id="<?php echo $id_base . '-title'; ?>" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php esc_attr_e( 'Title', COLTMAN_TEXT_DOMAIN ); ?>">
                                <?php $this->accordion_editor( $id_base . '_content', $content ); ?>
                            </div>
                            <div class="accodeon-item-panel flex gap-2 flex-col items-center justify-center w-2/12" style="padding-top:15px">
                                <div class="accordion-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', COLTMAN_TEXT_DOMAIN ); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M7 2a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/></svg>
                                </div>
                                <button type="button" onclick="removeAccordeonItem(this)"
                                        class="flex gap-2 px-3 py-2 text-white transition duration-300 bg-red-500 cursor-pointer rounded hover:bg-red-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                    <?php echo __( 'Remove', COLTMAN_TEXT_DOMAIN ); ?>
                                </button>
                                <button type="button" onclick="saveAccordeonItem(this)"
                                        class="flex gap-2 px-3 py-2 text-white transition duration-300 bg-blue-500 rounded cursor-pointer hover:bg-blue-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/></svg>
                                    <?php echo __( 'Save', COLTMAN_TEXT_DOMAIN ); ?>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>

                    <?php else :
                        $field_id = $field['id'] . '_' . mt_rand( 1000, 9999 );
                    ?>
                        <div data-id="<?php echo esc_attr( $field['id'] ); ?>" id="<?php echo esc_attr( $field_id . '_parent' ); ?>"
                             class="accordion-item flex items-center justify-between gap-2 bg-slate-100 p-4">
                            <div class="w-10/12 accodeon-item-content flex flex-col gap-2">
                                <h3 style="margin:0 0 4px"><?php echo esc_html( $field['label'] . ' item' ); ?></h3>
                                <?php if ( $have_image ) : $this->media( [ 'id' => $field_id . '_image', 'type' => 'media', 'class' => 'image-url-accodeon', 'button-text' => 'Upload', 'return' => 'url', 'default' => '' ], '' ); endif; ?>
                                <input type="text" class="regular-text block w-full min-h-10 rounded input-title" id="<?php echo $field_id . '-title'; ?>" placeholder="<?php esc_attr_e( 'Title', COLTMAN_TEXT_DOMAIN ); ?>">
                                <?php $this->accordion_editor( $field_id . '_content', '' ); ?>
                            </div>
                            <div class="accodeon-item-panel flex gap-2 flex-col items-center justify-center w-2/12" style="padding-top:10px">
                                <div class="accordion-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', COLTMAN_TEXT_DOMAIN ); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M7 2a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/></svg>
                                </div>
                                <button type="button" onclick="removeAccordeonItem(this)"
                                        class="flex gap-2 px-3 py-2 text-white transition duration-300 bg-red-500 cursor-pointer rounded hover:bg-red-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                    <?php echo __( 'Remove', COLTMAN_TEXT_DOMAIN ); ?>
                                </button>
                                <button type="button" onclick="saveAccordeonItem(this)"
                                        class="flex gap-2 px-3 py-2 text-white transition duration-300 bg-blue-500 rounded cursor-pointer hover:bg-blue-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/></svg>
                                    <?php echo __( 'Save', COLTMAN_TEXT_DOMAIN ); ?>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="accordion-button-container" style="padding-top:4px">
                    <button type="button" onclick="addAccordeonItem(this)"
                            class="flex gap-2 px-3 py-2 text-white transition duration-300 bg-blue-500 rounded cursor-pointer hover:bg-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2"/></svg>
                        <?php echo __( 'Add Row', COLTMAN_TEXT_DOMAIN ); ?>
                    </button>
                </div>
            </div>
            <?php
        }
    

        /**
         * Echoes a Leaflet map picker. Stores {"lat":…,"lng":…,"zoom":…} as JSON.
         *
         * Supported field config keys:
         *   'provider' (string) — currently only 'leaflet' (default).
         *   'zoom'     (int)    — default zoom level when no value is stored (default 13).
         *
         * @param array<string, mixed> $field Field config.
         * @param string               $value JSON string with lat/lng/zoom or empty string.
         * @return void
         */
        public function map( array $field, string $value = '' ): void {
            $id     = esc_attr( $field['id'] );
            $zoom   = isset( $field['zoom'] ) ? (int) $field['zoom'] : 13;
            $coords = ( $value !== '' ) ? json_decode( $value, true ) : [];
            $lat    = isset( $coords['lat'] )  ? (float) $coords['lat']  : '';
            $lng    = isset( $coords['lng'] )  ? (float) $coords['lng']  : '';
            $czoom  = isset( $coords['zoom'] ) ? (int)   $coords['zoom'] : $zoom;
            echo '<div class="coltman-map-wrap">';
            printf(
                '<input type="hidden" id="%s" name="%s" value="%s">',
                $id, $id, esc_attr( $value )
            );
            echo '<div class="coltman-map-coords">';
            printf(
                '<label class="coltman-map-coord-label">%s <input type="text" class="coltman-map-lat small-text" readonly value="%s" placeholder="lat"></label>',
                esc_html__( 'Lat:', COLTMAN_TEXT_DOMAIN ),
                esc_attr( (string) $lat )
            );
            printf(
                '<label class="coltman-map-coord-label">%s <input type="text" class="coltman-map-lng small-text" readonly value="%s" placeholder="lng"></label>',
                esc_html__( 'Lng:', COLTMAN_TEXT_DOMAIN ),
                esc_attr( (string) $lng )
            );
            printf(
                '<button type="button" class="button coltman-map-clear">%s</button>',
                esc_html__( 'Clear', COLTMAN_TEXT_DOMAIN )
            );
            echo '</div>';
            printf(
                '<div class="coltman-map-container" id="map-%s" data-field="%s" data-lat="%s" data-lng="%s" data-zoom="%d"></div>',
                $id, $id,
                esc_attr( (string) $lat ),
                esc_attr( (string) $lng ),
                $czoom
            );
            echo '</div>';
        }

        /**
         * Echoes a color picker input. Activated by jQuery.fn.wpColorPicker in media.js.
         *
         * @param array<string, mixed> $field Field config — supports 'default' for the initial swatch color.
         * @param string               $value Current hex color (e.g. '#ff0000').
         * @return void
         */
        public function color( $field, $value = '' ): void {
            printf(
                '<input class="coltman-color-picker" id="%s" name="%s" type="text" value="%s" data-default-color="%s">',
                esc_attr( $field['id'] ),
                esc_attr( $field['id'] ),
                esc_attr( $value ),
                esc_attr( isset( $field['default'] ) ? $field['default'] : '#ffffff' )
            );
        }

        /**
         * Echoes a repeatable field set. Each row renders the sub_fields defined in $field['sub_fields'].
         *
         * Value is a JSON-encoded array of row objects stored as post meta.
         * Supported sub-field types: text, email, number, date, url, textarea, select, checkbox, color.
         *
         * @param array<string, mixed> $field  Field config — requires 'sub_fields' array.
         *                                     Each sub_field: ['id' => …, 'label' => …, 'type' => …].
         * @param string               $value  JSON-encoded rows or empty string.
         * @return void
         */
        public function repeater( $field, $value = '' ): void {
            $rows       = ( is_string( $value ) && $value !== '' ) ? json_decode( $value, true ) : [];
            $rows       = is_array( $rows ) ? $rows : [];
            $sub_fields = isset( $field['sub_fields'] ) ? $field['sub_fields'] : [];
            $field_id   = $field['id'];
            ?>
            <div class="coltman-repeater flex flex-col gap-2 w-full" data-field="<?php echo esc_attr( $field_id ); ?>">
                <div class="repeater-rows flex flex-col gap-2">
                    <?php if ( ! empty( $rows ) ) :
                        foreach ( $rows as $i => $row ) : ?>
                        <div class="repeater-row flex flex-col gap-2 bg-slate-100 p-4 rounded border border-gray-300" data-index="<?php echo (int) $i; ?>">
                            <?php $this->repeater_row_header( (int) $i ); ?>
                            <?php $this->repeater_sub_fields( $sub_fields, $field_id, (int) $i, is_array( $row ) ? $row : [] ); ?>
                        </div>
                    <?php endforeach; else : ?>
                        <div class="repeater-row flex flex-col gap-2 bg-slate-100 p-4 rounded border border-gray-300" data-index="0">
                            <?php $this->repeater_row_header( 0 ); ?>
                            <?php $this->repeater_sub_fields( $sub_fields, $field_id, 0, [] ); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" onclick="addRepeaterRow(this)"
                        class="flex gap-2 px-3 py-2 text-white transition duration-300 bg-blue-500 rounded cursor-pointer hover:bg-blue-600 min-w-max">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2"/></svg>
                    <?php echo __( 'Add Row', COLTMAN_TEXT_DOMAIN ); ?>
                </button>
            </div>
            <?php
        }

        private function repeater_row_header( int $index ): void {
            ?>
            <div class="flex items-center justify-between gap-2 border-b border-gray-300 pb-3">
                <span class="text-sm repeater-row-num"><?php printf( __( 'Row %d', COLTMAN_TEXT_DOMAIN ), $index + 1 ); ?></span>
                <div class="flex gap-2 items-center">
                    <div class="repeater-drag-handle" title="<?php echo esc_attr( __( 'Drag to reorder', COLTMAN_TEXT_DOMAIN ) ); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M7 2a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/></svg>
                    </div>
                    <button type="button" onclick="removeRepeaterRow(this)"
                            class="flex gap-2 px-3 py-2 text-white transition duration-300 bg-red-500 cursor-pointer rounded hover:bg-red-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                        <?php echo __( 'Remove', COLTMAN_TEXT_DOMAIN ); ?>
                    </button>
                </div>
            </div>
            <?php
        }

        private function repeater_sub_fields( array $sub_fields, string $field_id, int $index, array $row ): void {
            foreach ( $sub_fields as $sub ) {
                $sub_id  = $sub['id'];
                $html_id = $field_id . '_' . $index . '_' . $sub_id;
                $name    = $field_id . '[' . $index . '][' . $sub_id . ']';
                $sub_val = isset( $row[ $sub_id ] )    ? (string) $row[ $sub_id ]    :
                           ( isset( $sub['default'] )  ? (string) $sub['default']    : '' );
                ?>
                <div class="flex flex-col gap-1">
                    <label for="<?php echo esc_attr( $html_id ); ?>" class="text-sm"><?php echo esc_html( $sub['label'] ); ?></label>
                    <?php $this->repeater_sub_field( $sub, $name, $html_id, $sub_val ); ?>
                </div>
                <?php
            }
        }

        private function repeater_sub_field( array $sub, string $name, string $html_id, string $value ): void {
            $type = isset( $sub['type'] ) ? $sub['type'] : 'text';
            switch ( $type ) {
                case 'textarea':
                    printf(
                        '<textarea class="block w-full regular-text min-h-10" rows="%d" id="%s" name="%s">%s</textarea>',
                        isset( $sub['rows'] ) ? (int) $sub['rows'] : 3,
                        esc_attr( $html_id ),
                        esc_attr( $name ),
                        esc_textarea( $value )
                    );
                    break;
                case 'select':
                    printf(
                        '<select class="block w-full regular-text min-h-10" id="%s" name="%s">%s</select>',
                        esc_attr( $html_id ),
                        esc_attr( $name ),
                        $this->select_options( $sub, $value )
                    );
                    break;
                case 'checkbox':
                    printf(
                        '<label class="rwp-checkbox-label"><input %s id="%s" name="%s" type="checkbox" value="on"> %s</label>',
                        $value === 'on' ? 'checked' : '',
                        esc_attr( $html_id ),
                        esc_attr( $name ),
                        esc_html( isset( $sub['description'] ) ? $sub['description'] : '' )
                    );
                    break;
                case 'color':
                    printf(
                        '<input class="coltman-color-picker" id="%s" name="%s" type="text" value="%s" data-default-color="%s">',
                        esc_attr( $html_id ),
                        esc_attr( $name ),
                        esc_attr( $value ),
                        esc_attr( isset( $sub['default'] ) ? $sub['default'] : '#ffffff' )
                    );
                    break;
                case 'media':
                    echo '<div class="flex items-center justify-between w-full gap-2 min-h-10">';
                    printf(
                        '<input class="regular-text block w-full min-h-10" id="%s" name="%s" type="text" value="%s">',
                        esc_attr( $html_id ),
                        esc_attr( $name ),
                        esc_attr( $value )
                    );
                    printf(
                        '<button class="flex gap-2 px-3 py-2 text-white transition duration-300 bg-blue-500 rounded rwp-media-toggle hover:bg-blue-600" data-modal-button="%s" data-modal-title="%s" data-return="%s" id="%s_button" type="button">%s</button>',
                        esc_attr( isset( $sub['modal-button'] ) ? $sub['modal-button'] : __( 'Select this file', COLTMAN_TEXT_DOMAIN ) ),
                        esc_attr( isset( $sub['modal-title'] )  ? $sub['modal-title']  : __( 'Choose a file', COLTMAN_TEXT_DOMAIN ) ),
                        esc_attr( isset( $sub['return'] )       ? $sub['return']       : 'url' ),
                        esc_attr( $html_id ),
                        esc_html( isset( $sub['button-text'] )  ? $sub['button-text']  : __( 'Upload', COLTMAN_TEXT_DOMAIN ) )
                    );
                    echo '</div>';
                    break;
                default:
                    printf(
                        '<input class="regular-text block w-full min-h-10" id="%s" name="%s" type="%s" value="%s"%s>',
                        esc_attr( $html_id ),
                        esc_attr( $name ),
                        esc_attr( in_array( $type, [ 'email', 'number', 'date', 'url' ] ) ? $type : 'text' ),
                        esc_attr( $value ),
                        isset( $sub['pattern'] ) ? ' pattern="' . esc_attr( $sub['pattern'] ) . '"' : ''
                    );
            }
        }

        /**
         * Echoes a relationship select field with real-time AJAX search via Select2.
         *
         * Stores selected post IDs as a JSON array (same format as get_posts).
         * Requires the coltman_relationship_search AJAX action from ajax.php.
         *
         * @param array<string, mixed> $field  Field config — requires 'post_type'; supports 'placeholder'.
         * @param string               $value  JSON-encoded array of selected post IDs.
         * @return void
         */
        public function relationship( $field, $value = '' ): void {
            $raw_type    = isset( $field['post_type'] ) ? $field['post_type'] : 'post';
            $post_type   = is_array( $raw_type )
                ? implode( ',', $raw_type )
                : implode( ',', array_map( 'trim', explode( ',', $raw_type ) ) );
            $placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : __( 'Search posts…', COLTMAN_TEXT_DOMAIN );
            $selected_ids = [];
            if ( $value !== '' && $value !== null ) {
                $decoded = json_decode( $value );
                if ( is_array( $decoded ) ) {
                    $selected_ids = $decoded;
                }
            }
            $nonce = wp_create_nonce( 'coltman_relationship' );
            ?>
            <select id="<?php echo esc_attr( $field['id'] ); ?>"
                    name="<?php echo esc_attr( $field['id'] ); ?>[]"
                    multiple="multiple"
                    class="block w-full js-relationship-select regular-text min-h-10"
                    data-post-type="<?php echo esc_attr( $post_type ); ?>"
                    data-nonce="<?php echo esc_attr( $nonce ); ?>"
                    data-placeholder="<?php echo esc_attr( $placeholder ); ?>">
                <?php foreach ( $selected_ids as $post_id ) :
                    $post = get_post( (int) $post_id );
                    if ( ! $post ) continue;
                ?>
                <option value="<?php echo esc_attr( $post_id ); ?>" selected="selected"><?php echo esc_html( $post->post_title ); ?></option>
                <?php endforeach; ?>
            </select>
            <?php
        }


    }
}