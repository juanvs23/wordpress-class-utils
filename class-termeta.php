<?php
/**
 * class ColtmanTermMeta
 * 
 * Register and manage custom term meta fields.
 * 
 * @package Coltman
 * @author Juan Carlos Avila
 * @param string $taxonomy
 * @param array $config [
 *    'taxonomy' => string,
 *    'title' => string,
 *    'fields' => array]
 * @param array $fields [
 *    'label' => [
 *            'label' => string,
 *            'id' => string,
 *            'type' => string,
 *            'default' => string,
 *            'description' => string
 *        ],
 *      ]
 *
 * @since 1.0.0
 */
use Class\AddicClinicDirectory;
if (!class_exists('ColtmanTermMeta')) {
    class ColtmanTermMeta {

        /**
         * Array of custom fields.
         *
         * @since 1.0.0
         * @access private
         * @var array $fields Array of custom fields.
         */
        private $fields;
        private $config;
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
                // Register all the hooks.
                add_action( $config['taxonomy'].'_add_form_fields', [ $this, 'wpturbo_render_meta_fields' ], 10, 2 );
                add_action( $config['taxonomy'].'_edit_form_fields', [ $this, 'wpturbo_edit_meta_fields' ],  10, 2 );
                add_action( 'created_'.$config['taxonomy'], [ $this, 'wpturbo_save_meta_fields' ], 10, 1 );
                add_action( 'edited_'.$config['taxonomy'],  [ $this, 'wpturbo_save_meta_fields' ], 10, 1 );
    
                add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
                add_action( 'admin_head', [ $this, 'admin_head' ] );
    
                $this->fields = $config['fields'];
                $this->config = $config;
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
    
        public function admin_enqueue_scripts() {
            $taxonomy_name = $this->config['taxonomy']; 
            $taxonomy_url =  isset($_GET['taxonomy'])? $_GET['taxonomy'] : '';
            if (  $taxonomy_name == $taxonomy_url ) {
                wp_enqueue_media();
                wp_enqueue_script( 'wp-color-picker' );
                wp_enqueue_style( 'wp-color-picker' );
            }
        }
    
        public function admin_head() {
            $taxonomy_name = $this->config['taxonomy']; 
            $taxonomy_url =  isset($_GET['taxonomy'])? $_GET['taxonomy'] : '';
            if (  $taxonomy_name == $taxonomy_url ) {
                ?>
                <script defer src="<?php echo plugin_dir_url(__FILE__).'assets/js/media.js';?>"></script>
                	<!-- <script  src="<?php echo plugin_dir_url(__FILE__).'assets/js/tailwind.js';?>"></script> -->
                <?php
                ?>
                	<?php
				?>
				<style>.rwp-checkbox-label {
						display: block;
					}
					.block {
    display: block !important;
}
.w-full{
	width: 100% ;
}
.flex {
    display: flex;
	flex:1;
}
.justify-center{
	justify-content: center;
}
.justify-between {
    justify-content: space-between;
}
.items-center{
	align-items: center;
}
.pb-3 {
    padding-bottom: 0.75rem;
}
.gap-2{
	gap: 0.5rem !important;
}
.gap-3{
	gap: 0.75rem !important;
}
.gap-4 {
    gap: 1rem;
}
.flex-col {
    flex-direction: column;
}
.duration-300 {
    transition-duration: 300ms;
}
.text-white {
    --tw-text-opacity: 1;
    color: rgb(255 255 255 / var(--tw-text-opacity));
}
.py-2 {
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
}
.px-3 {
    padding-left: 0.75rem;
    padding-right: 0.75rem;
}
.px-4 {
    padding-left: 1rem;
    padding-right: 1rem;
}
.bg-blue-500 {
    --tw-bg-opacity: 1;
    background-color: rgb(59 130 246 / var(--tw-bg-opacity)) !important;
}
.bg-blue-500 {
    --tw-bg-opacity: 1;
    background-color: rgb(59 130 246 / var(--tw-bg-opacity));
}
.bg-red-500 {
    --tw-bg-opacity: 1;
    background-color: rgb(239 68 68 / var(--tw-bg-opacity));
}
.min-h-10 {
	min-height: 2.5rem !important;
}
.rounded {
    border-radius: 0.25rem;
}
.rounded-md {
	border-radius: 0.375rem;
}
.border {
	border-width: 1px;
}
.border-b {
	border-bottom-width: 1px;
}
.text-sm{
	font-size: 0.875rem; /* 14px */
	line-height: 1.25rem; /* 20px */
}
.border-gray-300 {
	--tw-border-opacity: 1;
	border-color: rgb(209 213 219 / var(--tw-border-opacity));
}
.border-blue-500 {
	--tw-border-opacity: 1;
	border-color: rgb(59 130 246 / var(--tw-border-opacity));
}
.cursor-pointer {
    cursor: pointer;
    appearance: none;
    border: none;
}
.hover\:bg-blue-600:hover {
    --tw-bg-opacity: 1;
    background-color: rgb(37 99 235 / var(--tw-bg-opacity));
}
.hover\:bg-red-600:hover {
    --tw-bg-opacity: 1;
    background-color: rgb(220 38 38 / var(--tw-bg-opacity));
}				
					</style>
                
                
                <?php
            }
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
            switch( $field['type'] ) {
                case 'select': {
                    $field_html = '<select name="'.$field_id.'" id="'.$field_id.'">';
                        foreach( $field['options'] as $key => $value ){
                            $key = ! is_numeric( $key ) ? $key : $value;
                            $selected = '';
                            if( $field_value === $key ){
                                $selected = 'selected="selected"';
                            }
                            $field_html .= '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
                        }
                    $field_html .= '</select>';
                    break;
                }
                case 'textarea': {
                    $field_html = '<textarea name="'.$field_id.'" id="'.$field_id.'" rows="6">'.$field_value.'</textarea>';
                    break;
                }
                case 'media': {
                    $field_html = "<input class='w-full px-4 py-2 font-bold border border-gray-300 rounded min-h-10' type='{$field['type']}' id='$field_id' name='$field_id' value='$field_value' />";
                    $field_html.= '<button class="px-4 py-2 text-white bg-blue-500 rounded cursor-pointer min-h-10 rwp-media-toggle hover:bg-blue-600" data-modal-button="';
                    $field_html.= isset( $field['modal-button'] ) ? $field['modal-button'] : __( 'Select this file', 'advanced-options' );
                    $field_html.= '" data-modal-title="';
                    $field_html.= isset( $field['modal-title'] ) ? $field['modal-title'] : __( 'Elige una imagen', 'advanced-options' );
                    $field_html.= '" data-return="';
                    $field_html.= ($field['return'])? $field['return'] : 'url';
                    $field_html.= '" id="'.$field_id.'_button" name="'.$field_id.'_button" type="button">';
                    $field_html.= isset( $field['button-text'] ) ? $field['button-text'] : __( 'Upload', 'advanced-options' );
                    $field_html.= '</button>';
                    break;
                }
                default: {
                    $field_html = "<input class='block' type='{$field['type']}' id='$field_id' name='$field_id' value='$field_value' />";
                    break;
                }
            }
        
            return $field_html;
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
            foreach ( $this->fields as $field_id => $field ) {
                if( isset( $_POST[$field_id] ) ){
                    // Sanitize fields that need to be sanitized.
                    switch( $field['type'] ){
                        case 'email': {
                            $_POST[$field_id] = sanitize_email( $_POST[$field_id] );
                            break;
                        }
                        case 'text': {
                            $_POST[$field_id] = sanitize_text_field( $_POST[$field_id] );
                            break;
                        }
                    }
                    update_term_meta( $term_id, $field_id, $_POST[$field_id] );
                }
            }
        }
        
    }
    
}