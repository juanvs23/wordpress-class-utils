<?php
/**
 * Class ColtmanCreateMetabox
 * Description: Create Metaboxes
 *  @param array $config
 *  $config = [
 *           'title' =>'Title of the Metabox',
 *           'description' => 'Description of the Metabox',
 *           'prefix' => 'local7_dish_foods_',
 *           'domain' => 'local7_dish_foods',
 *           'class_name' => 'css-class',
 *           'context' => 'normal',
 *           'priority' => 'high',
 *           'cpt' => 'post-typa',
 *           'fields' => [
 *                [
 *                    'label' => __('Galería de imágenes', 'restaurant-tools'),
 *                    'id' => 'menus_gallery',
 *                    'type' => 'gallery',
 *                    'default' => ''
 *                ],
 *                [
 *                    'label' => __('Precio: ', 'restaurant-tools'),
 *                    'id' => 'menus_banner',
 *                    'type' => 'text',
 *                    'default' => ''
 *                ],
 *                [
 *                    'label' => __('Receta: ', 'restaurant-tools'),
 *                    'id' => 'menus_recipe',
 *                    'type' => 'editor',
 *                    'default' => '',
 *                    'description' => __('Añade los ingredientes del platillo', 'restaurant-tools'),
 *                ]                
 *         ]
 *      ];
 * 
 */
if(!class_exists('ColtmanCreateMetabox')) {
	
	class ColtmanCreateMetabox {
		private $config;
		public $coltmanInputs;
		/**
		 * Constructor.
		 *
		 * Register hooks for rendering and saving custom post meta fields.
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @param array $config Metabox configuration.
		 */
		public function __construct($config) {
		// var_dump(  $config );
			$this->config = $config;
			$this->process_cpts();
			$this->coltmanInputs = class_exists('ColtmanInputFields') ? new ColtmanInputFields() : false;
			add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
			add_action( 'admin_head', [ $this, 'admin_head' ] );
			add_action( 'save_post', [ $this, 'save_post' ] );
		}
	
		public function process_cpts() {
			if ( !empty( $this->config['cpt'] ) ) {
				if ( empty( $this->config['post-type'] ) ) {
					$this->config['post-type'] = [];
				}
				$parts = explode( ',', $this->config['cpt'] );
				$parts = array_map( 'trim', $parts );
				$this->config['post-type'] = array_merge( $this->config['post-type'], $parts );
			}
		}
	
		public function add_meta_boxes() {
		 
				add_meta_box(
			$this->config['prefix'] . 'metabox',
					$this->config['title'],
					[ $this, 'add_meta_box_callback' ],
					$this->config['cpt'],
					$this->config['context'],
					$this->config['priority']
				);
	
		}
	
		public function admin_enqueue_scripts() {
			global $typenow;
			if ( in_array( $typenow, $this->config['post-type'] ) ) {
				wp_enqueue_media();
				wp_enqueue_script( 'wp-color-picker' );
				wp_enqueue_style( 'wp-color-picker' );
				wp_register_style( 'select2css', '//cdnjs.cloudflare.com/ajax/libs/select2/3.4.8/select2.css', false, '1.0', 'all' );
				wp_register_script( 'select2', '//cdnjs.cloudflare.com/ajax/libs/select2/3.4.8/select2.js', array( 'jquery' ), '1.0', true );
				wp_enqueue_style( 'select2css' );
				wp_enqueue_script( 'select2' );
			//	wp_enqueue_script('tymece',ADDIC_CLINIC_PLUGIN_URL. 'classes/assets/libs/tinymce/tinymce.min.js',[],'1.0.0',true);
			}
		}
	
		public function admin_head() {
			global $typenow;
			if ( in_array( $typenow, $this->config['post-type'] ) ) {
				?>
				<script defer src="<?php echo get_stylesheet_directory_uri() .'/classes/assets/js/media.js';?>"></script>
				<!-- <script  defer  src="<?php echo get_stylesheet_directory_uri().'/classes/assets/js/tailwind.js';?>"></script> -->
	
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
.bg-slate-100{
	background-color: #e2e8f0;
}
.p-4{
	padding: 1rem;
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
.bg-blue-500 {
    --tw-bg-opacity: 1;
    background-color: rgb(59 130 246 / var(--tw-bg-opacity));
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
.w-10\/12{
	width: 83.333333%;
	min-width: 83.333333%;
	max-width: 83.333333%;
}
.w-2\/12{
	width: 16.666667%;
	min-width: 16.666667%;
	max-width: 16.666667%;
}			
					</style>
				
				<?php
			}
		}
	
		public function save_post( $post_id ) {
			/* var_dump( $_POST );
			die(); */
			foreach ( $this->config['fields'] as $field ) {
				switch ( $field['type'] ) {
					case 'get_posts':
						update_post_meta( $post_id, $field['id'], isset( $_POST[ $field['id'] ] ) ? json_encode($_POST[ $field['id'] ]) : '[]' );
						break;
					case 'checkbox':
						update_post_meta( $post_id, $field['id'], isset( $_POST[ $field['id'] ] ) ? $_POST[ $field['id'] ] : '' );
						break;
					case 'editor':
						if ( isset( $_POST[ $field['id'] ] ) ) {
							$sanitized = wp_filter_post_kses( $_POST[ $field['id'] ] );
							update_post_meta( $post_id, $field['id'], $sanitized );
						}
						break;
					case 'email':
						if ( isset( $_POST[ $field['id'] ] ) ) {
							$sanitized = sanitize_email( $_POST[ $field['id'] ] );
							update_post_meta( $post_id, $field['id'], $sanitized );
						}
						break;
					case 'textarea':
						if ( isset( $_POST[ $field['id'] ] ) ) {
							
							update_post_meta( $post_id, $field['id'], $_POST[ $field['id'] ] );
						}
					default:
						if ( isset( $_POST[ $field['id'] ] ) ) {
							$sanitized = sanitize_text_field( $_POST[ $field['id'] ] );
							update_post_meta( $post_id, $field['id'], $sanitized );
						}
				}
			}
		}
	
		public function add_meta_box_callback() {
			$this->fields_table();
		}
	
		private function fields_table() {
			?>
				<h3 class="description"><?php echo $this->config['description']; ?></h3>
				<style>
					.wp-editor-container iframe {
						max-height: 200px;
					}
				</style>
				<table class="form-table" role="presentation">
					<tbody><?php
						foreach ( $this->config['fields'] as $field ) {
							?><tr>
								<th scope="row"><?php $this->label( $field ); ?></th>
								<td>
									<?php $this->field( $field ); ?>
									<?php $this->description( $field ); ?>
								</td>
							</tr><?php
						}
					?></tbody>
				</table>
			<?php
		}

		private function description( $field ) {
			if ( ! empty( $field['description'] ) ) {
				echo '<p class="w-full mt-1 text-sm text-gray-500 description">' . $field['description'] . '</p>';
			}
		}
	
		private function label( $field ) {
			switch ( $field['type'] ) {
				case 'editor':
					echo '<div class="">' . $field['label'] . '</div>';
					break;
				case 'media':
					printf(
						'<label class="" for="%s_button">%s</label>',
						$field['id'], $field['label']
					);
					break;
				default:
					printf(
						'<label class="" for="%s">%s</label>',
						$field['id'], $field['label']
					);
			}
		}
	
		private function field( $field ) {
			
			$value = $this->value( $field );
			$checked = $this->checked( $field );
			
			switch ( $field['type'] ) {
				case 'checkbox':
					$this->coltmanInputs->checkbox( $field, $checked );
					break;
				case 'number':
					$this->coltmanInputs->input_minmax( $field, $value );
					break;
				case 'get_terms':
					$this->coltmanInputs->get_terms( $field, $value );
					break;
				case 'accordion':
					$this->coltmanInputs->accordion( $field, $value );
					break;
				case 'date':
					$this->coltmanInputs->input_minmax( $field, $value );
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
				case 'repeater':
					$this->coltmanInputs->repeater( $field, $value );
					break;
				default:
					$this->coltmanInputs->input( $field, $value );
			}
			
		}
		/**
		 * Get the value of a field.
		 */
		private function value( $field ) {
			global $post;
			if ( metadata_exists( 'post', $post->ID, $field['id'] ) ) {
				$value = get_post_meta( $post->ID, $field['id'], true );
			} else if ( isset( $field['default'] ) ) {
				$value = $field['default'];
			} else {
				return '';
			}
			return str_replace( '\u0027', "'", $value );
		}

		/**
		 * Get the checked value of a field.
		 */
		private function checked( $field ) {
			global $post;
			if ( metadata_exists( 'post', $post->ID, $field['id'] ) ) {
				$value = get_post_meta( $post->ID, $field['id'], true );
				if ( $value === 'on' ) {
					return 'checked';
				}
				return '';
			} else if ( isset( $field['checked'] ) ) {
				return 'checked';
			}
			return '';
		}
	}
}