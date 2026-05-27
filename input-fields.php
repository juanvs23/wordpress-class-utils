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
                $field['id'], $field['id'],
                isset( $field['description'] ) ? $field['description'] : ''
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
                'textarea_rows' => isset( $field['rows'] ) ? isset( $field['rows'] ) : 20,
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
        public function get_posts ( $field, $value= '' ){
            global $wpdb;
            $post_type = $field['post_type'];
            $get_posts = get_posts(array('post_type' => $post_type, 'post_status' => 'publish', 'posts_per_page' => -1));
           // var_dump($value);
            ?>
                <select <?php echo count($get_posts)== 0 ? 'disabled' :'' ?>  multiple="multiple"  name="<?php echo $field['id'];?>[]" id="<?php echo $field['id'];?>"  class="block w-full js-select2 regular-text min-h-10" data-placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : __( 'Select posts…', COLTMAN_TEXT_DOMAIN ) ); ?>" >
                    <?php if(count($get_posts)> 0): ?>
                       
                        <?php foreach($get_posts as $post): ?>
                            <option value="<?php echo $post->ID;?>" <?php echo in_array($post->ID, json_decode($value)) ? 'selected' : '';?>><?php echo $post->post_title;?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value=""><?php echo __( "Don't have posts available", COLTMAN_TEXT_DOMAIN ); ?></option>
                    <?php endif; ?>
                </select>
            <?php
        }


        /**
         * Echoes a single-select populated with all terms of a given taxonomy.
         *
         * @param array<string, mixed> $field Field config — requires 'taxonomy' key.
         * @param string               $value Selected term_id as string.
         * @return void
         */
        public function  get_terms( $field, $value= '' ){
            $taxonomy = $field['taxonomy'];
            $terms = get_terms( $taxonomy, ['hide_empty' => false] );
            ?>
            <select <?php echo count($terms)== 0 ? 'disabled' :'' ?> name="<?php echo $field['id'];?>" id="<?php echo $field['id'];?>"  class="block w-full js-select2 regular-text min-h-10" data-placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : __( 'Select a term', COLTMAN_TEXT_DOMAIN ) ); ?>" data-allow-clear="1">
            <?php if(count($terms)> 0): ?>
                <option value=""><?php echo __( 'Select a term', COLTMAN_TEXT_DOMAIN ); ?></option>
                <?php foreach($terms as $term): ?>
                    <option value="<?php echo $term->term_id;?>" <?php echo $value == $term->term_id ? 'selected' : '';?>><?php echo $term->name;?></option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value=""><?php echo __( "Don't have terms available", COLTMAN_TEXT_DOMAIN ); ?></option>
            <?php endif;?>
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
                isset( $field['class'] ) ? $field['class'] : '',
                $field['id'], $field['id'],
                isset( $field['pattern'] ) ? "pattern='{$field['pattern']}'" : '',
                $field['type'],
                $value
            );
        }
    
    
        /**
         * Echoes the "Upload" button that opens the WP media modal.
         *
         * @param array<string, mixed> $field Field config — uses 'modal-button', 'modal-title', 'return', 'id', 'button-text'.
         * @return void
         */
        private function media_button( $field ) {
            printf(
                '<button class="flex gap-2 px-3 py-2 text-white transition duration-300 bg-blue-500 rounded rwp-media-toggle hover:bg-blue-600" data-modal-button="%s" data-modal-title="%s" data-return="%s" id="%s_button" name="%s_button" type="button">%s</button>',
                isset( $field['modal-button'] ) ? $field['modal-button'] : __( 'Select this file', COLTMAN_TEXT_DOMAIN ),
                isset( $field['modal-title'] ) ? $field['modal-title'] : __( 'Choose a file', COLTMAN_TEXT_DOMAIN ),
                $field['return'],
                $field['id'], $field['id'],
                isset( $field['button-text'] ) ? $field['button-text'] : __( 'Upload', COLTMAN_TEXT_DOMAIN )
            );
        }
    
        /**
         * Echoes a text input + media picker button wrapped in a flex container.
         *
         * @param array<string, mixed> $field Field config.
         * @param string               $value Current URL or ID value.
         * @return void
         */
        public function media( $field, $value = '' ) {
            echo '<div class="flex items-center justify-between w-full gap-2 min-h-10">';
                $this->input( $field, $value );
                $this->media_button( $field );
            echo '</div>';
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
                isset( $field['rows'] ) ? $field['rows'] : 5,
                $placeholder,
                $field['id'], 
                $field['id'],
                $value
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
                $field['id'],
                isset( $field['max'] ) ? "max='{$field['max']}'" : '',
                isset( $field['min'] ) ? "min='{$field['min']}'" : '',
                $field['id'],
                isset( $field['step'] ) ? "step='{$field['step']}'" : '',
                $field['type'],
                $value
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
            <div class="gallery">
                <input type="hidden" class="gallery-data"  name="<?php echo $field['id']; ?>" id="<?php echo $field['id']; ?>" value='<?php echo json_encode($value); ?>'>
                <div class="flex flex-col w-full gap-4 pb-3 gallery-container" 
                    data-buttonmodal="<?php echo $modal_button; ?>"
                    data-buttonmodaltitle="<?php echo $modal_title; ?>"
                    data-buttonreturn="<?php echo $return; ?>">
                    <?php if (count($value)>0): 
                            foreach($value as $item){
                                $html ='';
                                $html .= '<div class="flex items-center justify-between gap-2 gallery-item" data-item="'.$item->item.'">';
                                $html .= '<div class="flex items-center justify-center w-full gap-2 get-image" >';
                                $html .= '<input type="text" class="block w-full h-4 px-3 py-2 regular-text block w-full min-h-10 rounded image-url" value="'.$item->url.'">';
                                $html .= '<button class="px-3 py-2 text-white transition duration-300 bg-blue-500 rounded rwp-media-toggle hover:bg-blue-600" ';
                                $html .= 'data-modal-button="'.$modal_button.'" ';
                                $html .= 'data-modal-title="'.$modal_title.'" ';
                                $html .= 'data-return="'.$return.'" ';
                                $html .= 'type="button">'.$text_button.'</button>';
                                $html .= '</div>';
                                $html .= '<button type="button" onclick="removeiTem(this)" class="px-3 py-2 text-white transition duration-300 bg-red-500 rounded btn btn-primary remove-image hover:bg-red-600">';
                                $html .=  __( 'Remove', COLTMAN_TEXT_DOMAIN );
                                $html .= '</button>';
                                $html .= '</div>';
                                echo $html;
                            }; ?>
                    <?php else: ?>
                        <div class="flex items-center justify-between gap-2 gallery-item" data-item="<?php echo date('YmdHis') . mt_rand(1000, 9999); ?>">
                            <div class="flex items-center justify-center w-full gap-2 get-image" >
                                <input type="text" class="block w-full h-4 px-3 py-2 regular-text block w-full min-h-10 rounded image-url">
                                <button 
                                    class="px-3 py-2 text-white transition duration-300 bg-blue-500 rounded rwp-media-toggle hover:bg-blue-600" 
                                    data-modal-button="<?php echo $modal_button; ?>" 
                                    data-modal-title="<?php echo $modal_title; ?>"
                                    data-return="<?php echo $return; ?>" type="button">
                                    <?php echo $text_button; ?>
                                </button>
                            </div>
                            <button type="button" 
                                onclick="removeiTem(this)" 
                                class="px-3 py-2 text-white transition duration-300 bg-red-500 rounded btn btn-primary remove-image hover:bg-red-600">
                                <?php echo __( 'Remove', COLTMAN_TEXT_DOMAIN ); ?>
                            </button>
                        </div>
                    <?php endif; ?>
    
                </div>
    
                <button type="button"
                onclick="addiTemImage(this)" 
                class="flex gap-2 px-3 py-2 text-white transition duration-300 bg-blue-500 rounded btn btn-primary add-image min-w-max hover:bg-blue-600">
                    <?php echo __( 'Add image', COLTMAN_TEXT_DOMAIN ); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" class="bi bi-plus-lg" viewBox="0 0 16 16">
                          <path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2"/>
                    </svg>
                </button>
    
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
                    <button type="button" class="coltman-wysiwyg-btn" data-cmd="bold"                title="<?php esc_attr_e( 'Bold',             COLTMAN_TEXT_DOMAIN ); ?>"><strong>B</strong></button>
                    <button type="button" class="coltman-wysiwyg-btn" data-cmd="italic"              title="<?php esc_attr_e( 'Italic',           COLTMAN_TEXT_DOMAIN ); ?>"><em>I</em></button>
                    <button type="button" class="coltman-wysiwyg-btn" data-cmd="underline"           title="<?php esc_attr_e( 'Underline',        COLTMAN_TEXT_DOMAIN ); ?>"><u>U</u></button>
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
                <input type="hidden" class="accordion-data" name="<?php echo $field['id']; ?>" id="<?php echo $field['id']; ?>" value='<?php echo json_encode( $value ); ?>'>
                <div class="flex flex-col gap-4 pb-3 accordion-container">

                    <?php if ( count( $value ) > 0 ) : ?>
                        <?php foreach ( $value as $item ) :
                            $id      = $item->id;
                            $title   = $item->title;
                            $content = $item->content;
                            $image   = $item->image;
                            $id_base = str_replace( '_parent', '', $id );
                        ?>
                        <div data-id="<?php echo $field['id']; ?>" id="<?php echo $id; ?>"
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
                        <div data-id="<?php echo $field['id']; ?>" id="<?php echo $field_id . '_parent'; ?>"
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
    
    
    }
}