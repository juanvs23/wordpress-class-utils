<?php
/**
 * Creates admin metaboxes with dynamic custom fields for any post type.
 *
 * ```php
 * new ColtmanCreateMetabox([
 *     'title'       => 'Información Adicional',
 *     'description' => 'Añade información sobre la joya.',
 *     'prefix'      => 'jewel_list_',
 *     'domain'      => 'anillosdepedida',
 *     'class_name'  => 'jewel_list',
 *     'context'     => 'normal',   // 'normal' | 'side' | 'advanced'
 *     'priority'    => 'high',     // 'high' | 'default' | 'low'
 *     'cpt'         => 'anillo_jewelry',
 *     'fields'      => [
 *         ['label' => 'Nombre de la joya',  'id' => 'member_name',     'type' => 'text',    'default' => ''],
 *         ['label' => 'Orden de ubicación', 'id' => 'member_position', 'type' => 'text',    'default' => ''],
 *         ['label' => 'Texto descriptivo',  'id' => 'description_text','type' => 'text',    'default' => ''],
 *         ['label' => 'Galería',            'id' => 'gallery',         'type' => 'gallery', 'default' => ''],
 *     ],
 * ]);
 * ```
 *
 * @package Coltman
 * @since   1.0.0
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
			$this->config = $config;
			$this->process_cpts();
			$this->coltmanInputs = class_exists('ColtmanInputFields') ? new ColtmanInputFields() : false;
			add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
			add_action( 'admin_head', [ $this, 'admin_head' ] );
			add_action( 'save_post', [ $this, 'save_post' ] );
		}
	
		/**
		 * Normalizes the 'cpt' config string into the 'post-type' array.
		 *
		 * Parses the comma-separated 'cpt' value and merges it into config['post-type'].
		 *
		 * @return void
		 */
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
	
		/**
		 * 'add_meta_boxes' hook callback — registers the metabox via add_meta_box().
		 *
		 * @return void
		 */
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
	
		/**
		 * 'admin_enqueue_scripts' hook callback — enqueues media, color picker and Select2.
		 *
		 * Only runs on admin pages for the configured post types.
		 *
		 * @return void
		 */
		public function admin_enqueue_scripts() {
			global $typenow;
			global $post;
			if ( in_array( $typenow, $this->config['post-type'] ) ) {
				wp_enqueue_media( ! is_null( $post ) ? [ 'post' => $post->ID ] : [] );
				wp_enqueue_script( 'wp-color-picker' );
				wp_enqueue_style( 'wp-color-picker' );
				wp_register_style( 'select2css', COLTMAN_ASSETS_URL . '/libs/select2/select2.min.css', false, '4.0.13', 'all' );
				wp_register_script( 'select2', COLTMAN_ASSETS_URL . '/libs/select2/select2.min.js', [ 'jquery' ], '4.0.13', true );
				wp_enqueue_style( 'select2css' );
				wp_enqueue_style( 'coltman-admin', COLTMAN_ASSETS_URL . '/css/admin.css', [], '1.6.0' );
				wp_enqueue_script( 'select2' );
				wp_enqueue_script( 'coltman-media', COLTMAN_ASSETS_URL . '/js/media.js', [ 'jquery', 'select2', 'jquery-ui-sortable' ], '1.6.0', true );
			}
		}
	
		/**
		 * 'admin_head' hook callback — injects media.js and utility CSS for the metabox.
		 *
		 * Only runs on admin pages for the configured post types.
		 *
		 * @return void
		 */
		public function admin_head() {
			// CSS is now enqueued via admin_enqueue_scripts().
		}

		/**
		 * 'save_post' hook callback — persists each field value to post meta.
		 *
		 * Verifies nonce and user capability before saving.
		 * Sanitization by field type: JSON (get_posts), wp_filter_post_kses (editor),
		 * sanitize_email (email), sanitize_textarea_field (textarea), sanitize_text_field (default).
		 *
		 * @param int $post_id ID of the post being saved.
		 * @return void
		 */
		public function save_post( $post_id ) {
			$nonce_key = 'coltman_nonce_' . $this->config['prefix'];
			if ( ! isset( $_POST[ $nonce_key ] ) ) return;
			if ( ! wp_verify_nonce( $_POST[ $nonce_key ], 'coltman_metabox_' . $this->config['prefix'] ) ) return;
			if ( ! current_user_can( 'edit_post', $post_id ) ) return;
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

			foreach ( $this->config['fields'] as $field ) {
				switch ( $field['type'] ) {
					case 'get_posts':
						update_post_meta( $post_id, $field['id'], isset( $_POST[ $field['id'] ] ) ? json_encode( $_POST[ $field['id'] ] ) : '[]' );
						break;
					case 'checkbox':
						update_post_meta( $post_id, $field['id'], isset( $_POST[ $field['id'] ] ) ? $_POST[ $field['id'] ] : '' );
						break;
					case 'editor':
						if ( isset( $_POST[ $field['id'] ] ) ) {
							update_post_meta( $post_id, $field['id'], wp_filter_post_kses( $_POST[ $field['id'] ] ) );
						}
						break;
					case 'email':
						if ( isset( $_POST[ $field['id'] ] ) ) {
							update_post_meta( $post_id, $field['id'], sanitize_email( $_POST[ $field['id'] ] ) );
						}
						break;
					case 'textarea':
						if ( isset( $_POST[ $field['id'] ] ) ) {
							update_post_meta( $post_id, $field['id'], sanitize_textarea_field( $_POST[ $field['id'] ] ) );
						}
						break;
					case 'gallery':
					case 'accordion':
						if ( isset( $_POST[ $field['id'] ] ) ) {
							update_post_meta( $post_id, $field['id'], $_POST[ $field['id'] ] );
						}
						break;
					default:
						if ( isset( $_POST[ $field['id'] ] ) ) {
							update_post_meta( $post_id, $field['id'], sanitize_text_field( $_POST[ $field['id'] ] ) );
						}
				}
			}
		}
	
		/**
		 * Metabox render callback passed to add_meta_box().
		 *
		 * @return void
		 */
		public function add_meta_box_callback() {
			$this->fields_table();
		}
	
		/**
		 * Renders the HTML table containing all configured fields.
		 *
		 * @return void
		 */
		private function fields_table() {
			wp_nonce_field( 'coltman_metabox_' . $this->config['prefix'], 'coltman_nonce_' . $this->config['prefix'] );
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

		/**
		 * Outputs the field description paragraph if one is set.
		 *
		 * @param array<string, mixed> $field Field configuration array.
		 * @return void
		 */
		private function description( $field ) {
			if ( ! empty( $field['description'] ) ) {
				echo '<p class="w-full mt-1 text-sm text-gray-500 description">' . $field['description'] . '</p>';
			}
		}
	
		/**
		 * Outputs the field label element. Uses a <div> for editor fields, <label> for all others.
		 *
		 * @param array<string, mixed> $field Field configuration array.
		 * @return void
		 */
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
	
		/**
		 * Dispatches rendering to the appropriate ColtmanInputFields method based on field type.
		 *
		 * @param array<string, mixed> $field Field configuration array.
		 * @return void
		 */
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
				/* case 'repeater':
					$this->coltmanInputs->repeater( $field, $value );
					break; */
				default:
					$this->coltmanInputs->input( $field, $value );
			}
			
		}
		/**
		 * Returns the stored post meta value for a field, falling back to 'default' or empty string.
		 *
		 * Decodes the unicode apostrophe escape (') stored by the accordion JS serializer.
		 *
		 * @param array<string, mixed> $field Field configuration array.
		 * @return string|mixed Stored or default value.
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
		 * Returns 'checked' if the checkbox field is stored as 'on', or if the field has a 'checked' default.
		 *
		 * @param array<string, mixed> $field Field configuration array.
		 * @return string 'checked' or empty string.
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