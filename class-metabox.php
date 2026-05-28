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
			add_action( 'init', [ $this, 'register_rest_meta' ] );
			add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_gutenberg_panel' ] );
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
				wp_localize_script( 'coltman-media', 'coltmanVars', [ 'assetsUrl' => COLTMAN_ASSETS_URL ] );
				wp_enqueue_style( 'leaflet',  COLTMAN_ASSETS_URL . '/libs/leaflet/leaflet.min.css', [], '1.9.4' );
				wp_enqueue_script( 'leaflet', COLTMAN_ASSETS_URL . '/libs/leaflet/leaflet.min.js', [], '1.9.4', true );
			}
		}
	
		/**
		 * Maps a Coltman field type to its WordPress REST API type string.
		 * @param string $type  Coltman field type.
		 * @return string 'string', 'number', or 'boolean'.
		 */
		private function rest_field_type( string $type ): string {
			return match ( $type ) {
				'number' => 'number',
				default  => 'string',
			};
		}

		/**
		 * 'init' hook callback -- registers REST-enabled post meta.
		 *
		 * Fields that include 'rest' => true are registered with register_post_meta()
		 * so they are accessible from the REST API and the Gutenberg editor.
		 *
		 * @return void
		 */
		public function register_rest_meta(): void {
			$post_types = isset( $this->config['post-type'] ) ? $this->config['post-type'] : [];
			$fields     = isset( $this->config['fields'] ) ? $this->config['fields'] : [];
			foreach ( $fields as $field ) {
				if ( empty( $field['rest'] ) ) continue;
				foreach ( $post_types as $post_type ) {
					register_post_meta( $post_type, $field['id'], [
						'show_in_rest'  => true,
						'single'        => true,
						'type'          => $this->rest_field_type( isset( $field['type'] ) ? $field['type'] : 'text' ),
						'auth_callback' => static fn() => current_user_can( 'edit_posts' ),
					] );
				}
			}
		}

		/**
		 * 'enqueue_block_editor_assets' hook -- loads the Gutenberg sidebar panel script.
		 *
		 * Only enqueues when the current screen matches this metabox and at least one
		 * field has 'rest' => true.
		 *
		 * @return void
		 */
		public function enqueue_gutenberg_panel(): void {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			if ( ! $screen ) return;
			$post_types = isset( $this->config['post-type'] ) ? $this->config['post-type'] : [];
			if ( ! in_array( $screen->post_type, $post_types, true ) ) return;
			$rest_fields = array_values( array_filter(
				isset( $this->config['fields'] ) ? $this->config['fields'] : [],
				static fn( $f ) => ! empty( $f['rest'] )
			) );
			if ( empty( $rest_fields ) ) return;
			wp_enqueue_script(
				'coltman-gutenberg-panel',
				COLTMAN_ASSETS_URL . '/js/gutenberg-panel.js',
				[ 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data' ],
				'1.11.0',
				true
			);
			$panel_data = array_map( static function ( $f ) {
				$data = [
					'id'    => $f['id'],
					'type'  => isset( $f['type'] )  ? $f['type']  : 'text',
					'label' => isset( $f['label'] ) ? $f['label'] : $f['id'],
				];
				if ( ! empty( $f['description'] ) ) $data['description'] = $f['description'];
				if ( isset( $f['default'] ) )       $data['default']     = $f['default'];
				if ( ! empty( $f['options'] ) )      $data['options']     = $f['options'];
				return $data;
			}, $rest_fields );
			wp_localize_script( 'coltman-gutenberg-panel', 'coltmanGutenbergData', [
				'fields'     => $panel_data,
				'panelTitle' => isset( $this->config['title'] ) ? $this->config['title'] : __( 'Custom Fields', COLTMAN_TEXT_DOMAIN ),
			] );
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
		 * sanitize_email (email), wp_kses_post (textarea), sanitize_text_field (default).
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
					case 'group':
						$all_sub_fields = isset( $field['fields'] ) ? $field['fields'] : [];
						$dyn_schema     = $this->get_group_schema( $field['id'] );
						$static_sub_ids = array_column( $all_sub_fields, 'id' );
						foreach ( $dyn_schema as $df ) {
							if ( ! in_array( $df['key'], $static_sub_ids, true ) ) {
								$all_sub_fields[] = [ 'id' => $df['key'], 'type' => $df['type'], 'label' => $df['label'] ];
							}
						}
						foreach ( $all_sub_fields as $sub_field ) {
							$sub_id  = $sub_field['id'];
							$sub_val = isset( $_POST[ $sub_id ] ) ? $_POST[ $sub_id ] : null;
							if ( $sub_val === null ) continue;
							switch ( isset( $sub_field['type'] ) ? $sub_field['type'] : 'text' ) {
								case 'textarea':    update_post_meta( $post_id, $sub_id, wp_kses_post( $sub_val ) ); break;
								case 'email':       update_post_meta( $post_id, $sub_id, sanitize_email( (string) $sub_val ) ); break;
								case 'url':         update_post_meta( $post_id, $sub_id, esc_url_raw( (string) $sub_val ) ); break;
								case 'editor':      update_post_meta( $post_id, $sub_id, wp_filter_post_kses( (string) $sub_val ) ); break;
								case 'get_posts':
								case 'relationship': update_post_meta( $post_id, $sub_id, is_array( $sub_val ) ? json_encode( $sub_val ) : '[]' ); break;
								case 'get_terms':   update_post_meta( $post_id, $sub_id, ! empty( $sub_field['multiple'] ) && is_array( $sub_val ) ? json_encode( $sub_val ) : sanitize_text_field( (string) $sub_val ) ); break;
								default:            update_post_meta( $post_id, $sub_id, sanitize_text_field( (string) $sub_val ) );
							}
						}
						break;

					case 'get_terms':
						$is_multiple = ! ( isset( $field['multiple'] ) && ! $field['multiple'] );
						if ( $is_multiple ) {
							update_post_meta( $post_id, $field['id'], isset( $_POST[ $field['id'] ] ) ? json_encode( $_POST[ $field['id'] ] ) : '[]' );
						} else {
							update_post_meta( $post_id, $field['id'], isset( $_POST[ $field['id'] ] ) ? sanitize_text_field( $_POST[ $field['id'] ] ) : '' );
						}
						break;
					case 'checkbox':
						update_post_meta( $post_id, $field['id'], isset( $_POST[ $field['id'] ] ) ? sanitize_text_field( $_POST[ $field['id'] ] ) : '' );
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
							update_post_meta( $post_id, $field['id'], wp_kses_post( $_POST[ $field['id'] ] ) );
						}
						break;
					case 'color':
						if ( isset( $_POST[ $field['id'] ] ) ) {
							update_post_meta( $post_id, $field['id'], sanitize_text_field( $_POST[ $field['id'] ] ) );
						}
						break;
					case 'map':
						if ( isset( $_POST[ $field['id'] ] ) && $_POST[ $field['id'] ] !== '' ) {
							$raw  = json_decode( wp_unslash( $_POST[ $field['id'] ] ), true );
							$lat  = isset( $raw['lat'] )  ? (float) $raw['lat']  : null;
							$lng  = isset( $raw['lng'] )  ? (float) $raw['lng']  : null;
							$mzoom = isset( $raw['zoom'] ) ? (int) $raw['zoom']  : 13;
							if ( $lat !== null && $lat >= -90 && $lat <= 90 && $lng !== null && $lng >= -180 && $lng <= 180 ) {
								update_post_meta( $post_id, $field['id'], wp_json_encode( [ 'lat' => $lat, 'lng' => $lng, 'zoom' => $mzoom ] ) );
							}
						} else {
							update_post_meta( $post_id, $field['id'], '' );
						}
						break;
					case 'relationship':
						update_post_meta( $post_id, $field['id'], isset( $_POST[ $field['id'] ] ) ? json_encode( $_POST[ $field['id'] ] ) : '[]' );
						break;
					case 'repeater':
						if ( isset( $_POST[ $field['id'] ] ) && is_array( $_POST[ $field['id'] ] ) ) {
							$rows = [];
							foreach ( $_POST[ $field['id'] ] as $row ) {
								if ( ! is_array( $row ) ) continue;
								$clean = [];
								foreach ( isset( $field['sub_fields'] ) ? $field['sub_fields'] : [] as $sub ) {
									$sub_val = isset( $row[ $sub['id'] ] ) ? $row[ $sub['id'] ] : '';
									switch ( isset( $sub['type'] ) ? $sub['type'] : 'text' ) {
										case 'email':    $clean[ $sub['id'] ] = sanitize_email( (string) $sub_val );         break;
										case 'textarea': $clean[ $sub['id'] ] = wp_kses_post( (string) $sub_val ); break;
										case 'url':      $clean[ $sub['id'] ] = esc_url_raw( (string) $sub_val );             break;
										default:         $clean[ $sub['id'] ] = sanitize_text_field( (string) $sub_val );
									}
								}
								if ( array_filter( $clean ) ) {
									$rows[] = $clean;
								}
							}
							update_post_meta( $post_id, $field['id'], json_encode( $rows ) );
						}
						break;
					case 'gallery':
					case 'accordion':
					case 'list':
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
				<h3 class="description"><?php echo esc_html( $this->config['description'] ); ?></h3>
				<table class="form-table" role="presentation">
					<tbody><?php
						foreach ( $this->config['fields'] as $field ) {
							if ( isset( $field['type'] ) && $field['type'] === 'group' ) {
								$this->group_field( $field );
							} else {
							?><tr>
								<th scope="row"><?php $this->label( $field ); ?></th>
								<td>
									<?php $this->field( $field ); ?>
									<?php $this->description( $field ); ?>
								</td>
							</tr><?php
							}
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
				echo '<p class="w-full mt-1 text-sm text-gray-500 description">' . esc_html( $field['description'] ) . '</p>';
			}
		}
	

		/**
		 * Renders a group field as a single table row: label + collapse toggle in <th>,
		 * all sub-fields stacked inside <td>. Each sub-field is dispatched through field().
		 *
		 * @param array<string, mixed> $field Group field configuration.
		 * @return void
		 */
		private function get_group_schema( string $group_id ): array {
			$schema = get_option( '_coltman_group_schema_' . $group_id, [] );
			return is_array( $schema ) ? $schema : [];
		}

		private function group_field( array $field ): void {
			$group_id       = esc_attr( $field['id'] );
			$label          = isset( $field['label'] ) ? esc_html( $field['label'] ) : '';
			$static_fields  = isset( $field['fields'] ) ? $field['fields'] : [];
			$dynamic_schema = $this->get_group_schema( $field['id'] );
			$static_ids     = array_column( $static_fields, 'id' );
			$nonce          = wp_create_nonce( 'coltman_group_schema' );
			?>
			<tr>
				<th scope="row">
					<div class="coltman-group-header">
						<span class="coltman-group-label"><?php echo $label; ?></span>
						<button type="button" class="coltman-group-toggle" data-group="<?php echo $group_id; ?>" aria-expanded="true">&#9650;</button>
					</div>
				</th>
				<td>
					<?php if ( ! empty( $field['description'] ) ) : ?>
					<p class="w-full mb-2 text-sm text-gray-500 description"><?php echo $field['description']; ?></p>
					<?php endif; ?>
					<div class="coltman-group-body" id="coltman-group-<?php echo $group_id; ?>">
						<?php foreach ( $static_fields as $sub_field ) : ?>
						<div class="coltman-group-field-row">
							<?php $this->label( $sub_field ); ?>
							<?php $this->field( $sub_field ); ?>
							<?php $this->description( $sub_field ); ?>
						</div>
						<?php endforeach; ?>
						<?php foreach ( $dynamic_schema as $df ) :
							if ( in_array( $df['key'], $static_ids, true ) ) continue;
							$sub = [ 'id' => $df['key'], 'type' => $df['type'], 'label' => $df['label'] ];
						?>
						<div class="coltman-group-field-row" data-dynamic-key="<?php echo esc_attr( $df['key'] ); ?>">
							<?php $this->label( $sub ); ?>
							<?php $this->field( $sub ); ?>
						</div>
						<?php endforeach; ?>
						<div class="coltman-field-manager"
						     data-group="<?php echo $group_id; ?>"
						     data-nonce="<?php echo esc_attr( $nonce ); ?>">
							<div class="coltman-field-manager-toggle-row">
								<button type="button" class="coltman-field-manager-toggle" aria-expanded="false">&#9881; <?php esc_html_e( 'Manage fields', COLTMAN_TEXT_DOMAIN ); ?></button>
							</div>
							<div class="coltman-field-manager-panel" style="display:none">
								<div class="coltman-dynamic-fields-list">
									<?php
									$dyn_visible = array_values( array_filter( $dynamic_schema, static fn( $df ) => ! in_array( $df['key'], $static_ids, true ) ) );
									if ( empty( $dyn_visible ) ) : ?>
									<p class="coltman-no-dynamic-fields"><?php esc_html_e( 'No dynamic fields added yet.', COLTMAN_TEXT_DOMAIN ); ?></p>
									<?php else : ?>
									<?php foreach ( $dyn_visible as $df ) : ?>
									<div class="coltman-dynamic-field-item" data-key="<?php echo esc_attr( $df['key'] ); ?>">
										<span class="coltman-dynamic-field-info">
											<?php echo esc_html( $df['type'] ); ?> &middot; <?php echo esc_html( $df['label'] ); ?> <code><?php echo esc_html( $df['key'] ); ?></code>
										</span>
										<button type="button" class="coltman-remove-dynamic-field" data-key="<?php echo esc_attr( $df['key'] ); ?>">&#10005;</button>
									</div>
									<?php endforeach; endif; ?>
								</div>
								<div class="coltman-add-field-form">
									<select class="coltman-new-field-type">
										<option value="text"><?php esc_html_e( 'Text', COLTMAN_TEXT_DOMAIN ); ?></option>
										<option value="textarea"><?php esc_html_e( 'Textarea', COLTMAN_TEXT_DOMAIN ); ?></option>
										<option value="number"><?php esc_html_e( 'Number', COLTMAN_TEXT_DOMAIN ); ?></option>
										<option value="email"><?php esc_html_e( 'Email', COLTMAN_TEXT_DOMAIN ); ?></option>
										<option value="url">URL</option>
									</select>
									<input type="text" class="coltman-new-field-key" placeholder="<?php esc_attr_e( 'field_key', COLTMAN_TEXT_DOMAIN ); ?>">
									<input type="text" class="coltman-new-field-label" placeholder="<?php esc_attr_e( 'Field Label', COLTMAN_TEXT_DOMAIN ); ?>">
									<button type="button" class="coltman-add-dynamic-field">+ <?php esc_html_e( 'Add field', COLTMAN_TEXT_DOMAIN ); ?></button>
								</div>
								<p class="coltman-field-manager-note"><?php esc_html_e( '* Dynamic fields apply to all posts with this group.', COLTMAN_TEXT_DOMAIN ); ?></p>
							</div>
						</div>
					</div>
				</td>
			</tr>
			<?php
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
					echo '<div class="">' . esc_html( $field['label'] ) . '</div>';
					break;
				case 'media':
					printf(
						'<label class="" for="%s_button">%s</label>',
						esc_attr( $field['id'] ), esc_html( $field['label'] )
					);
					break;
				default:
					printf(
						'<label class="" for="%s">%s</label>',
						esc_attr( $field['id'] ), esc_html( $field['label'] )
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
				case 'color':
					$this->coltmanInputs->color( $field, $value );
					break;
				case 'repeater':
					$this->coltmanInputs->repeater( $field, $value );
					break;
				case 'relationship':
					$this->coltmanInputs->relationship( $field, $value );
					break;
				case 'map':
					$this->coltmanInputs->map( $field, $value );
					break;
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