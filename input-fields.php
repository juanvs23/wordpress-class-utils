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

        public function get_posts ( $field, $value= '' ){
            global $wpdb;
            $post_type = $field['post_type'];
            $get_posts = get_posts(array('post_type' => $post_type, 'post_status' => 'publish', 'posts_per_page' => -1));
           // var_dump($value);
            ?>
                <select <?php echo count($get_posts)== 0 ? 'disabled' :'' ?>  multiple="multiple"  name="<?php echo $field['id'];?>[]" id="<?php echo $field['id'];?>"  class="block w-full get_posts regular-text min-h-10" >
                    <?php if(count($get_posts)> 0): ?>
                       
                        <?php foreach($get_posts as $post): ?>
                            <option value="<?php echo $post->ID;?>" <?php echo in_array($post->ID, json_decode($value)) ? 'selected' : '';?>><?php echo $post->post_title;?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="">Don't have posts available </option>
                    <?php endif; ?>
                </select>
            <?php
        }


        public function  get_terms( $field, $value= '' ){
            $taxonomy = $field['taxonomy'];
            $terms = get_terms( $taxonomy, ['hide_empty' => false] );
            ?>
            <select <?php echo count($terms)== 0 ? 'disabled' :'' ?> name="<?php echo $field['id'];?>" id="<?php echo $field['id'];?>"  class="block w-full regular-text min-h-10">
            <?php if(count($terms)> 0): ?>
                <option value="">Select a term</option>
                <?php foreach($terms as $term): ?>
                    <option value="<?php echo $term->term_id;?>" <?php echo $value == $term->term_id ? 'selected' : '';?>><?php echo $term->name;?></option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="">Don't have terms available </option>
            <?php endif;?>
            </select>
            <?php    
        }

       
    
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
    
    
        private function media_button( $field ) {
            printf(
                '<button class="flex gap-2 px-3 py-2 text-white transition duration-300 bg-blue-500 rounded rwp-media-toggle hover:bg-blue-600" data-modal-button="%s" data-modal-title="%s" data-return="%s" id="%s_button" name="%s_button" type="button">%s</button>',
                isset( $field['modal-button'] ) ? $field['modal-button'] : __( 'Select this file', 'advanced-options' ),
                isset( $field['modal-title'] ) ? $field['modal-title'] : __( 'Choose a file', 'advanced-options' ),
                $field['return'],
                $field['id'], $field['id'],
                isset( $field['button-text'] ) ? $field['button-text'] : __( 'Upload', 'advanced-options' )
            );
        }
    
        public function media( $field, $value = '' ) {
            echo '<div class="flex items-center justify-between w-full gap-2 min-h-10">';
                $this->input( $field, $value );
                $this->media_button( $field );
            echo '</div>';
        }
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
    
        public function gallery_input( $field, $value = '' ) {
            $modal_button = isset( $field['modal-button'] ) ? $field['modal-button'] : __( 'Select this file', 'advanced-options' );
            $modal_title = isset( $field['modal-title'] ) ? $field['modal-title'] : __( 'Choose a file', 'advanced-options' );
            $return = isset( $field['return'] ) ? $field['return']: 'url';
                
            $text_button = isset( $field['button-text'] ) ? $field['button-text'] : __( 'Upload', 'advanced-options' );
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
                                $html .=  __( 'Remove', 'addic-clinic-directory' );
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
                                <?php echo __( 'Remove', 'addic-clinic-directory' ); ?>
                            </button>
                        </div>
                    <?php endif; ?>
    
                </div>
    
                <button type="button"
                onclick="addiTemImage(this)" 
                class="flex gap-2 px-3 py-2 text-white transition duration-300 bg-blue-500 rounded btn btn-primary add-image min-w-max hover:bg-blue-600">
                    <?php echo __( 'Add image', 'addic-clinic-directory' ); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" class="bi bi-plus-lg" viewBox="0 0 16 16">
                          <path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2"/>
                    </svg>
                </button>
    
            </div>
            
            <?php
        }
    
        public function select( $field ) {
    
            printf(
                '<select id="%s" class="block w-full regular-text min-h-10" name="%s">%s</select>',
                $field['id'], $field['id'],
                $this->select_options( $field )
            );
        }
    
        private function select_selected(  bool $selected = false ) {
            if ( $selected ) {
                return 'selected';
            }
            return '';
        }
    
        private function select_options( $field, $value  = '' ) {
            $options = '';
            foreach ( $field['options'] as $option ) {
                if ( $value === $option['value'] ) {
                    $option['selected'] = true;
                }
                $options .= sprintf(
                    '<option value="%s" %s>%s</option>',
                    $option['value'],
                    $this->select_selected(  $option['selected'] ),
                    $option['label']
                );
            }
            return $options;
        }
    
        public function accordion($field, $value){
            $value = !is_null( $value ) && $value !='' ? json_decode($value) : [];
            $have_image = !isset($field['add_image']) || $field['add_image'] !='false' ? true : false;
            ?>
            <div class="accordion flex flex-col gap-2 w-full">
                <input type="hidden" class="accordion-data"  name="<?php echo $field['id']; ?>" id="<?php echo $field['id']; ?>" value='<?php echo json_encode($value); ?>'>
                <div class="flex flex-col gap-4 pb-3 accordion-container " >
                    <?php if(count($value)>0):?>
                        <?php foreach($value as $item):
                            $id = $item->id;
                            $title = $item->title;
                            $content = $item->content;
                            $image = $item->image;
    
                            $id_base = str_replace("_parent", "",  $id);
                           // var_dump($id_base);
                            ?>
                            <div 
                            data-id="<?php echo $field['id']; ?>" id="<?php echo $id; ?>" 
                            class="accordion-item flex items-center justify-between gap-2 bg-slate-100 p-4">
                            <div class=" w-10/12 accodeon-item-content gap-2 flex flex-col gap-2 ">
                                <h3 style="margin-top:0;margin-bottom:0px"><?php echo $field['label']. ' item';?></h3>
                                <?php 
                                if($have_image):
                                $this->media([
                                    'id' => $id_base.'_image',
                                    'type' => 'media',
                                    'class' => 'image-url-accodeon',
                                    'button-text' => 'Upload',
                                    'return' => 'url',
                                    'default' => '',
                                ], $image );
                                endif;
                                ?>
                                <input type="text" class="regular-text block w-full min-h-10  rounded input-title" id="<?php echo $id_base.'-title'; ?>"  value="<?php echo $title; ?>"  placeholder="Title" >
                                
                                <?php
                                
                               echo '<textarea  id="'.$id_base.'-content" class="block w-full h-4 px-3 py-2 rounded input-content" name="content" placeholder="Content">'.$content.'</textarea>';
                                
                                ?>
                            </div>
                            <div class="accodeon-item-panel flex gap-2 flex-col align-center justify-center w-2/12" style="padding-top:15px">
                                <button type="button" 
                                    onclick="removeAccordeonItem(this)" 
                                    class="flex gap-2 px-3 py-2 text-white transition duration-300 bg-red-500 cursor-pointer rounded btn btn-primary remove-image hover:bg-red-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" class="bi bi-trash" viewBox="0 0 16 16">
                                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                                        <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
                                    </svg>
                                    <?php echo __( 'Remove', 'addic-clinic-directory' ); ?>
                                </button>
                                <button type="button" 
                                    onclick="saveAccordeonItem(this)" 
                                    class="flex gap-2 px-3 py-2 text-white transition duration-300 bg-blue-500 rounded cursor-pointer btn btn-primary add-image min-w-max hover:bg-blue-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" class="bi bi-pencil" viewBox="0 0 16 16">
                                        <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/>
                                    </svg>
                                    <?php echo __( 'Save', 'addic-clinic-directory' ); ?>
                                </button>
                            </div>
                        </div>
    
                        <?php endforeach; ?>
                    <?php else:
                         $field_id = $field['id']."_". mt_rand(1000, 9999);
                        ?>
                        <div 
                            data-id="<?php echo $field['id']; ?>" id="<?php echo $field_id."_parent"; ?>" 
                            class="accordion-item flex items-center justify-between gap-2 bg-slate-100 p-4">
                            <div class=" w-10/12 accodeon-item-content gap-2 flex flex-col gap-2 ">
                                <h3 style="margin-top:0;margin-bottom:10px"><?php echo $field['label']. ' item';?></h3>
                                <?php
                                 if($have_image): 
                                $this->media([
                                    'id' => $field_id.'_image',
                                    'type' => 'media',
                                    'class' => 'image-url-accodeon',
                                    'button-text' => 'Upload',
                                    'return' => 'url',
                                    'default' => '',
                                ], '' );
                                endif;
                                ?>
                                <input type="text" class="regular-text block w-full min-h-10  rounded input-title" id="<?php echo $field_id.'-title'; ?>" name="title"  placeholder="Title" >
                                <?php
                                
                                echo '<textarea  id="'.$field_id.'-content" class="block w-full h-4 px-3 py-2 rounded input-content" name="content" placeholder="Content"></textarea>';
                                
                                ?>
                            </div>
                            <div class="accodeon-item-panel flex gap-2 flex-col align-center justify-center w-2/12" style="padding-top:10px">
                                <button type="button" 
                                    onclick="removeAccordeonItem(this)" 
                                    class="flex gap-2 px-3 py-2 text-white transition duration-300 bg-red-500 cursor-pointer rounded btn btn-primary remove-image hover:bg-red-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" class="bi bi-trash" viewBox="0 0 16 16">
                                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                                        <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
                                    </svg>
                                    <?php echo __( 'Remove', 'addic-clinic-directory' ); ?>
                                </button>
                                <button type="button" 
                                    onclick="saveAccordeonItem(this)" 
                                    class="flex gap-2 px-3 py-2 text-white transition duration-300 bg-blue-500 rounded cursor-pointer btn btn-primary add-image min-w-max hover:bg-blue-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" class="bi bi-pencil" viewBox="0 0 16 16">
                                        <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/>
                                    </svg>
                                    <?php echo __( 'Save', 'addic-clinic-directory' ); ?>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
               <!--  <div class="accordion-button-conta">
                    <button type="button"
                        onclick="addAccordeonItem(this)" 
                        class="flex gap-2 px-3 py-2 text-white transition duration-300 bg-blue-500 rounded cursor-pointer btn btn-primary add-image min-w-max hover:bg-blue-600">
                            <?php echo __( 'Add Row', 'luxerecovery' ); ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" class="bi bi-plus-lg" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2"/>
                            </svg>
                    </button>
                </div> -->
            <div>
            <?php
        }
    
    
    }
}