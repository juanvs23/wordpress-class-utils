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
		public function __construct($config) {
		// var_dump(  $config );
			$this->config = $config;
			$this->process_cpts();
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
			}
		}
	
		public function admin_head() {
			global $typenow;
			if ( in_array( $typenow, $this->config['post-type'] ) ) {
				?>
				<script defer src="<?php echo get_stylesheet_directory_uri().'/classes/assets/js/media.js';?>"></script>
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
					</style>
				
				<?php
			}
		}
	
		public function save_post( $post_id ) {
			foreach ( $this->config['fields'] as $field ) {
				switch ( $field['type'] ) {
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
			
			switch ( $field['type'] ) {
				case 'checkbox':
					$this->checkbox( $field );

					break;
				case 'date':
					$this->input_minmax( $field );
					break;
				case 'editor':
					$this->editor( $field );
					break;
				case 'media':
					echo '<div class="flex items-center justify-between w-full gap-2 min-h-10">';
					$this->input( $field );
					$this->media_button( $field );
					echo '</div>';
					break;
				case 'gallery':
					$this->gallery_input( $field );
					break;
				case 'select':
					$this->select( $field );
					break;
				case 'textarea':
					$this->textarea( $field );
					break;
				default:
					$this->input( $field );
			}
			
		}
	
		private function gallery_input( $field ) {
			$modal_button = isset( $field['modal-button'] ) ? $field['modal-button'] : __( 'Select this file', 'advanced-options' );
			$modal_title = isset( $field['modal-title'] ) ? $field['modal-title'] : __( 'Choose a file', 'advanced-options' );
			$return = isset( $field['return'] ) ? $field['return']: 'url';
				
			$text_button = isset( $field['button-text'] ) ? $field['button-text'] : __( 'Upload', 'advanced-options' );
			$value = !is_null($this->value( $field )) && $this->value( $field )!='' ? json_decode($this->value( $field )) : [];
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
								$html .= '<input type="text" class="block w-full h-4 px-3 py-2 rounded image-url" value="'.$item->url.'">';
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
								<input type="text" class="block w-full h-4 px-3 py-2 rounded image-url">
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
	
		private function checkbox( $field ) {
			printf(
				'<label class="rwp-checkbox-label"><input %s id="%s" name="%s" type="checkbox"> %s</label>',
				$this->checked( $field ),
				$field['id'], $field['id'],
				isset( $field['description'] ) ? $field['description'] : ''
			);
		}
	
		private function editor( $field ) {
			wp_editor( $this->value( $field ), $field['id'], [
				'wpautop' => isset( $field['wpautop'] ) ? true : false,
				'media_buttons' => isset( $field['media-buttons'] ) ? true : false,
				'textarea_name' => $field['id'],
				'textarea_rows' => isset( $field['rows'] ) ? isset( $field['rows'] ) : 20,
				'teeny' => isset( $field['teeny'] ) ? true : false
			] );
		}
	
		private function input( $field ) {
			if ( $field['type'] === 'media' ) {
				$field['type'] = 'text';
			}
			if ( isset( $field['color-picker'] ) ) {
				$field['class'] = 'rwp-color-picker';
			}
			printf(
				'<input class="regular-text block w-full min-h-10 %s" id="%s" name="%s" %s type="%s" value="%s">',
				isset( $field['class'] ) ? $field['class'] : '',
				$field['id'], $field['id'],
				isset( $field['pattern'] ) ? "pattern='{$field['pattern']}'" : '',
				$field['type'],
				$this->value( $field )
			);
		}
	
		private function input_minmax( $field ) {
			printf(
				'<input class="block w-full regular-text min-h-10" id="%s" %s %s name="%s" %s type="%s" value="%s">',
				$field['id'],
				isset( $field['max'] ) ? "max='{$field['max']}'" : '',
				isset( $field['min'] ) ? "min='{$field['min']}'" : '',
				$field['id'],
				isset( $field['step'] ) ? "step='{$field['step']}'" : '',
				$field['type'],
				$this->value( $field )
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
	
		private function select( $field ) {

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
	
		private function select_options( $field ) {
			$options = '';
			foreach ( $field['options'] as $option ) {
				if ( $this->value( $field ) === $option['value'] ) {
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
	
		private function textarea( $field ) {
			printf(
				'<textarea class="block w-full regular-text min-h-10" id="%s" name="%s" rows="%d">%s</textarea>',
				$field['id'], $field['id'],
				isset( $field['rows'] ) ? $field['rows'] : 5,
				$this->value( $field )
			);
		}
	
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