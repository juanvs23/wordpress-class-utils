<?php
namespace Coltman\Tests\Unit;

use Coltman\Tests\TestCase;

/**
 * Tests for ColtmanInputFields.
 *
 * Covers all public methods: checkbox, input, textarea, input_minmax, select,
 * media, gallery_input, accordion, get_posts, get_terms, editor.
 * Private methods tested via Reflection.
 */
class InputFieldsTest extends TestCase
{
    private \ColtmanInputFields $f;

    protected function setUp(): void
    {
        parent::setUp();
        $this->f = new \ColtmanInputFields();
    }

    // ── checkbox() ────────────────────────────────────────────────────────────

    public function test_checkbox_outputs_label_with_input(): void
    {
        $html = $this->capture(fn() => $this->f->checkbox(['id' => 'my_cb']));
        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('name="my_cb"', $html);
    }

    public function test_checkbox_includes_checked_attribute(): void
    {
        $html = $this->capture(fn() => $this->f->checkbox(['id' => 'cb'], 'checked'));
        $this->assertStringContainsString('checked', $html);
    }

    public function test_checkbox_includes_description_when_provided(): void
    {
        $html = $this->capture(fn() => $this->f->checkbox([
            'id'          => 'cb',
            'description' => 'My description',
        ]));
        $this->assertStringContainsString('My description', $html);
    }

    public function test_checkbox_no_description_when_absent(): void
    {
        $html = $this->capture(fn() => $this->f->checkbox(['id' => 'cb']));
        $this->assertStringNotContainsString('description', $html);
    }

    // ── input() ───────────────────────────────────────────────────────────────

    public function test_input_outputs_input_element(): void
    {
        $html = $this->capture(fn() => $this->f->input(['id' => 'my_field', 'type' => 'text'], 'hello'));
        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('type="text"', $html);
        $this->assertStringContainsString('value="hello"', $html);
        $this->assertStringContainsString('name="my_field"', $html);
    }

    public function test_input_transforms_media_type_to_text(): void
    {
        $html = $this->capture(fn() => $this->f->input(['id' => 'img', 'type' => 'media'], ''));
        $this->assertStringContainsString('type="text"', $html);
        $this->assertStringNotContainsString('type="media"', $html);
    }

    public function test_input_transforms_accordion_type_to_text(): void
    {
        $html = $this->capture(fn() => $this->f->input(['id' => 'acc', 'type' => 'accordion'], ''));
        $this->assertStringContainsString('type="text"', $html);
    }

    public function test_input_includes_class_when_provided(): void
    {
        $html = $this->capture(fn() => $this->f->input(['id' => 'f', 'type' => 'text', 'class' => 'my-class'], ''));
        $this->assertStringContainsString('my-class', $html);
    }

    public function test_input_includes_pattern_when_provided(): void
    {
        $html = $this->capture(fn() => $this->f->input(['id' => 'f', 'type' => 'text', 'pattern' => '[0-9]+'], ''));
        $this->assertStringContainsString('pattern="[0-9]+"', $html);
    }

    public function test_input_empty_value_by_default(): void
    {
        $html = $this->capture(fn() => $this->f->input(['id' => 'f', 'type' => 'text']));
        $this->assertStringContainsString('value=""', $html);
    }

    // ── textarea() ────────────────────────────────────────────────────────────

    public function test_textarea_outputs_textarea_element(): void
    {
        $html = $this->capture(fn() => $this->f->textarea(['id' => 'ta'], 'content'));
        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('name="ta"', $html);
        $this->assertStringContainsString('content', $html);
    }

    public function test_textarea_default_rows_is_five(): void
    {
        $html = $this->capture(fn() => $this->f->textarea(['id' => 'ta']));
        $this->assertStringContainsString('rows="5"', $html);
    }

    public function test_textarea_uses_custom_rows(): void
    {
        $html = $this->capture(fn() => $this->f->textarea(['id' => 'ta', 'rows' => 10]));
        $this->assertStringContainsString('rows="10"', $html);
    }

    public function test_textarea_includes_placeholder_when_set(): void
    {
        $html = $this->capture(fn() => $this->f->textarea(['id' => 'ta', 'placeholder' => 'Write here']));
        $this->assertStringContainsString('placeholder="Write here"', $html);
    }

    // ── input_minmax() ────────────────────────────────────────────────────────

    public function test_input_minmax_outputs_input(): void
    {
        $html = $this->capture(fn() => $this->f->input_minmax(['id' => 'n', 'type' => 'number'], '5'));
        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('type="number"', $html);
        $this->assertStringContainsString('value="5"', $html);
    }

    public function test_input_minmax_includes_min_attribute(): void
    {
        $html = $this->capture(fn() => $this->f->input_minmax(['id' => 'n', 'type' => 'number', 'min' => '0']));
        $this->assertStringContainsString('min="0"', $html);
    }

    public function test_input_minmax_includes_max_attribute(): void
    {
        $html = $this->capture(fn() => $this->f->input_minmax(['id' => 'n', 'type' => 'number', 'max' => '100']));
        $this->assertStringContainsString('max="100"', $html);
    }

    public function test_input_minmax_includes_step_attribute(): void
    {
        $html = $this->capture(fn() => $this->f->input_minmax(['id' => 'n', 'type' => 'number', 'step' => '0.5']));
        $this->assertStringContainsString('step="0.5"', $html);
    }

    public function test_input_minmax_omits_absent_attributes(): void
    {
        $html = $this->capture(fn() => $this->f->input_minmax(['id' => 'n', 'type' => 'number']));
        $this->assertStringNotContainsString('min=', $html);
        $this->assertStringNotContainsString('max=', $html);
        $this->assertStringNotContainsString('step=', $html);
    }

    // ── select() and select_options() ────────────────────────────────────────

    public function test_select_outputs_select_element(): void
    {
        $html = $this->capture(fn() => $this->f->select(['id' => 'sel', 'options' => []], ''));
        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('name="sel"', $html);
    }

    public function test_select_renders_string_keyed_options(): void
    {
        $html = $this->capture(fn() => $this->f->select([
            'id'      => 'sel',
            'options' => ['dog' => 'Dog', 'cat' => 'Cat'],
        ], ''));
        $this->assertStringContainsString('value="dog"', $html);
        $this->assertStringContainsString('value="cat"', $html);
        $this->assertStringContainsString('>Dog<', $html);
        $this->assertStringContainsString('>Cat<', $html);
    }

    public function test_select_renders_numeric_indexed_options(): void
    {
        $html = $this->capture(fn() => $this->f->select([
            'id'      => 'sel',
            'options' => ['Apple', 'Banana'],
        ], ''));
        $this->assertStringContainsString('value="Apple"', $html);
        $this->assertStringContainsString('>Apple<', $html);
    }

    public function test_select_marks_matching_value_as_selected(): void
    {
        $html = $this->capture(fn() => $this->f->select([
            'id'      => 'sel',
            'options' => ['a' => 'Alpha', 'b' => 'Beta'],
        ], 'b'));
        $this->assertStringContainsString('value="b"  selected="selected"', $html);
    }

    public function test_select_returns_empty_when_no_options_key(): void
    {
        $html = $this->capture(fn() => $this->f->select(['id' => 'sel'], ''));
        // <select> present but no <option>
        $this->assertStringNotContainsString('<option', $html);
    }

    public function test_select_options_with_array_option_format(): void
    {
        $html = $this->capture(fn() => $this->f->select([
            'id'      => 'sel',
            'options' => [
                ['value' => 'x', 'label' => 'X Label'],
            ],
        ], 'x'));
        $this->assertStringContainsString('value="x"', $html);
        $this->assertStringContainsString('X Label', $html);
    }

    // ── Private: select_selected() ────────────────────────────────────────────

    public function test_select_selected_returns_selected_attribute_when_true(): void
    {
        $result = $this->callMethod($this->f, 'select_selected', [true]);
        $this->assertStringContainsString('selected', $result);
    }

    public function test_select_selected_returns_empty_when_false(): void
    {
        $result = $this->callMethod($this->f, 'select_selected', [false]);
        $this->assertSame('', $result);
    }

    // ── media() ───────────────────────────────────────────────────────────────

    public function test_media_outputs_input_and_button(): void
    {
        $html = $this->capture(fn() => $this->f->media([
            'id'     => 'img_field',
            'type'   => 'media',
            'return' => 'url',
        ], 'https://example.com/img.jpg'));

        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('<button', $html);
        $this->assertStringContainsString('rwp-media-toggle', $html);
    }

    public function test_media_button_uses_default_texts(): void
    {
        $html = $this->capture(fn() => $this->f->media([
            'id'     => 'img',
            'type'   => 'media',
            'return' => 'url',
        ]));

        $this->assertStringContainsString('Select this file', $html);
        $this->assertStringContainsString('Upload', $html);
    }

    public function test_media_button_uses_custom_texts(): void
    {
        $html = $this->capture(fn() => $this->f->media([
            'id'           => 'img',
            'type'         => 'media',
            'return'       => 'url',
            'modal-button' => 'Pick It',
            'button-text'  => 'Select File',
        ]));

        $this->assertStringContainsString('Pick It', $html);
        $this->assertStringContainsString('Select File', $html);
    }

    // ── editor() ──────────────────────────────────────────────────────────────

    public function test_editor_calls_wp_editor(): void
    {
        $this->f->editor(['id' => 'my_editor'], 'initial content');

        $calls = $this->spyCalls('wp_editor');
        $this->assertCount(1, $calls);
        $this->assertSame('initial content', $calls[0]['content']);
        $this->assertSame('my_editor',       $calls[0]['editor_id']);
    }

    public function test_editor_passes_textarea_name_setting(): void
    {
        $this->f->editor(['id' => 'my_editor'], '');

        $call = $this->firstCall('wp_editor');
        $this->assertSame('my_editor', $call['settings']['textarea_name']);
    }

    // ── get_posts() ───────────────────────────────────────────────────────────

    public function test_get_posts_outputs_select_element(): void
    {
        $html = $this->capture(fn() => $this->f->get_posts([
            'id'        => 'related',
            'post_type' => 'page',
        ], '[]'));

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('name="related[]"', $html);
    }

    public function test_get_posts_uses_ajax_select(): void
    {
        $html = $this->capture(fn() => $this->f->get_posts([
            'id'        => 'related',
            'post_type' => 'page',
        ], '[]'));

        $this->assertStringContainsString('js-relationship-select', $html);
        $this->assertStringContainsString('data-post-type="page"', $html);
    }

    public function test_get_posts_prepopulates_selected_options(): void
    {
        $post            = new \stdClass();
        $post->ID        = 99;
        $post->post_title = 'Sample Page';
        $this->setStub('get_post', $post);

        $html = $this->capture(fn() => $this->f->get_posts([
            'id'        => 'related',
            'post_type' => 'page',
        ], '[99]'));

        $this->assertStringContainsString('value="99"', $html);
        $this->assertStringContainsString('Sample Page', $html);
    }

    // ── get_terms() ───────────────────────────────────────────────────────────

    public function test_get_terms_renders_ajax_select(): void
    {
        $html = $this->capture(fn() => $this->f->get_terms([
            'id'       => 'tax_field',
            'taxonomy' => 'category',
        ]));

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('js-term-select', $html);
        $this->assertStringContainsString('name="tax_field[]"', $html);  // default is multiple
        $this->assertStringContainsString('data-taxonomy="category"', $html);
        $this->assertStringContainsString('data-nonce=', $html);
    }

    public function test_get_terms_is_multiple_by_default(): void
    {
        $html = $this->capture(fn() => $this->f->get_terms([
            'id'       => 'tax_field',
            'taxonomy' => 'category',
        ]));

        $this->assertStringContainsString('multiple="multiple"', $html);
        $this->assertStringContainsString('name="tax_field[]"', $html);
        $this->assertStringNotContainsString('data-allow-clear', $html);
    }

    public function test_get_terms_single_requires_explicit_false(): void
    {
        $html = $this->capture(fn() => $this->f->get_terms([
            'id'       => 'tax_field',
            'taxonomy' => 'category',
            'multiple' => false,
        ]));

        $this->assertStringContainsString('data-allow-clear="1"', $html);
        $this->assertStringNotContainsString('multiple=', $html);
        $this->assertStringContainsString('name="tax_field"', $html);
    }

    public function test_get_terms_explicit_true_also_renders_multiple(): void
    {
        $html = $this->capture(fn() => $this->f->get_terms([
            'id'       => 'tax_field',
            'taxonomy' => 'category',
            'multiple' => true,
        ]));

        $this->assertStringContainsString('multiple="multiple"', $html);
        $this->assertStringContainsString('name="tax_field[]"', $html);
    }

    public function test_get_terms_accepts_comma_separated_taxonomies(): void
    {
        $html = $this->capture(fn() => $this->f->get_terms([
            'id'       => 'tax_field',
            'taxonomy' => 'category, post_tag',
        ]));

        $this->assertStringContainsString('data-taxonomy="category,post_tag"', $html);
    }

    public function test_get_terms_accepts_array_taxonomies(): void
    {
        $html = $this->capture(fn() => $this->f->get_terms([
            'id'       => 'tax_field',
            'taxonomy' => ['category', 'post_tag', 'tipo_de_joyeria'],
        ]));

        $this->assertStringContainsString('data-taxonomy="category,post_tag,tipo_de_joyeria"', $html);
    }

    public function test_get_terms_prepopulates_single_selected_term(): void
    {
        $term           = new \stdClass();
        $term->term_id  = 5;
        $term->name     = 'PHP';
        $this->setStub('get_term', $term);

        $html = $this->capture(fn() => $this->f->get_terms([
            'id'       => 'tax_field',
            'taxonomy' => 'category',
        ], '5'));

        $this->assertStringContainsString('value="5"', $html);
        $this->assertStringContainsString('PHP', $html);
        $this->assertStringContainsString('selected="selected"', $html);
    }

    public function test_get_terms_multiple_prepopulates_from_json(): void
    {
        $term           = new \stdClass();
        $term->term_id  = 7;
        $term->name     = 'JavaScript';
        $this->setStub('get_term', $term);

        $html = $this->capture(fn() => $this->f->get_terms([
            'id'       => 'tax_field',
            'taxonomy' => 'category',
            'multiple' => true,
        ], json_encode([7])));

        $this->assertStringContainsString('value="7"', $html);
        $this->assertStringContainsString('JavaScript', $html);
        $this->assertStringContainsString('selected="selected"', $html);
    }

    // ── gallery_input() ───────────────────────────────────────────────────────

    public function test_gallery_input_outputs_gallery_wrapper(): void
    {
        $html = $this->capture(fn() => $this->f->gallery_input(['id' => 'gallery'], ''));
        $this->assertStringContainsString('coltman-gallery', $html);
        $this->assertStringContainsString('class="gallery-data"', $html);
    }

    public function test_gallery_input_includes_add_image_button(): void
    {
        $html = $this->capture(fn() => $this->f->gallery_input(['id' => 'gallery'], ''));
        $this->assertStringContainsString('Add image', $html);
    }

    public function test_gallery_input_renders_existing_items(): void
    {
        $value = json_encode([
            (object)['item' => 'item_1', 'url' => 'https://example.com/img.jpg'],
        ]);

        $html = $this->capture(fn() => $this->f->gallery_input(['id' => 'gallery'], $value));
        $this->assertStringContainsString('https://example.com/img.jpg', $html);
        $this->assertStringContainsString('gallery-item', $html);
    }

    // ── color() ──────────────────────────────────────────────────────────────

    public function test_color_renders_text_input_with_picker_class(): void
    {
        $html = $this->capture(fn() => $this->f->color(['id' => 'bg_color'], '#ff0000'));

        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('type="text"', $html);
        $this->assertStringContainsString('coltman-color-picker', $html);
        $this->assertStringContainsString('name="bg_color"', $html);
        $this->assertStringContainsString('value="#ff0000"', $html);
    }

    public function test_color_uses_configured_default_swatch(): void
    {
        $html = $this->capture(fn() => $this->f->color(['id' => 'c', 'default' => '#336699']));
        $this->assertStringContainsString('data-default-color="#336699"', $html);
    }

    public function test_color_defaults_to_white_when_no_default_key(): void
    {
        $html = $this->capture(fn() => $this->f->color(['id' => 'c']));
        $this->assertStringContainsString('data-default-color="#ffffff"', $html);
    }

    // ── repeater() ───────────────────────────────────────────────────────────

    public function test_repeater_renders_container_and_add_button(): void
    {
        $html = $this->capture(fn() => $this->f->repeater([
            'id'         => 'my_repeater',
            'sub_fields' => [['id' => 'name', 'label' => 'Name', 'type' => 'text']],
        ], ''));

        $this->assertStringContainsString('coltman-repeater', $html);
        $this->assertStringContainsString('repeater-rows', $html);
        $this->assertStringContainsString('Add Row', $html);
    }

    public function test_repeater_renders_one_empty_row_when_value_is_empty(): void
    {
        $html = $this->capture(fn() => $this->f->repeater([
            'id'         => 'rp',
            'sub_fields' => [['id' => 'title', 'label' => 'Title', 'type' => 'text']],
        ], ''));

        $this->assertStringContainsString('repeater-row', $html);
        $this->assertStringContainsString('Row 1', $html);
        $this->assertStringContainsString('name="rp[0][title]"', $html);
    }

    public function test_repeater_renders_rows_from_json_value(): void
    {
        $value = json_encode([
            ['title' => 'First row',  'desc' => 'Alpha'],
            ['title' => 'Second row', 'desc' => 'Beta'],
        ]);
        $html = $this->capture(fn() => $this->f->repeater([
            'id'         => 'rp',
            'sub_fields' => [
                ['id' => 'title', 'label' => 'Title', 'type' => 'text'],
                ['id' => 'desc',  'label' => 'Desc',  'type' => 'textarea'],
            ],
        ], $value));

        $this->assertStringContainsString('Row 1', $html);
        $this->assertStringContainsString('Row 2', $html);
        $this->assertStringContainsString('value="First row"', $html);
        $this->assertStringContainsString('Second row', $html);
        $this->assertStringContainsString('name="rp[0][title]"', $html);
        $this->assertStringContainsString('name="rp[1][title]"', $html);
    }

    public function test_repeater_sub_field_select_renders_options(): void
    {
        $html = $this->capture(fn() => $this->f->repeater([
            'id'         => 'rp',
            'sub_fields' => [[
                'id'      => 'status',
                'label'   => 'Status',
                'type'    => 'select',
                'options' => ['a' => 'Alpha', 'b' => 'Beta'],
            ]],
        ], ''));

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('>Alpha<', $html);
        $this->assertStringContainsString('>Beta<', $html);
        $this->assertStringContainsString('name="rp[0][status]"', $html);
    }

    public function test_repeater_drag_handle_is_present(): void
    {
        $html = $this->capture(fn() => $this->f->repeater([
            'id'         => 'rp',
            'sub_fields' => [['id' => 'x', 'label' => 'X', 'type' => 'text']],
        ], ''));

        $this->assertStringContainsString('repeater-drag-handle', $html);
    }

    public function test_repeater_remove_button_is_present(): void
    {
        $html = $this->capture(fn() => $this->f->repeater([
            'id'         => 'rp',
            'sub_fields' => [['id' => 'x', 'label' => 'X', 'type' => 'text']],
        ], ''));

        $this->assertStringContainsString('removeRepeaterRow(this)', $html);
    }

    // ── relationship() ───────────────────────────────────────────────────────

    public function test_relationship_renders_select_with_ajax_attributes(): void
    {
        $html = $this->capture(fn() => $this->f->relationship([
            'id'        => 'related_posts',
            'post_type' => 'page',
        ], '[]'));

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('js-relationship-select', $html);
        $this->assertStringContainsString('name="related_posts[]"', $html);
        $this->assertStringContainsString('data-post-type="page"', $html);
        $this->assertStringContainsString('data-nonce=', $html);
        $this->assertStringContainsString('multiple="multiple"', $html);
    }

    public function test_relationship_pre_populates_selected_options(): void
    {
        $post             = new \stdClass();
        $post->ID         = 42;
        $post->post_title = 'About Us';
        $this->setStub('get_post', $post);

        $html = $this->capture(fn() => $this->f->relationship([
            'id'        => 'related_posts',
            'post_type' => 'page',
        ], json_encode([42])));

        $this->assertStringContainsString('value="42"', $html);
        $this->assertStringContainsString('About Us', $html);
        $this->assertStringContainsString('selected="selected"', $html);
    }

    public function test_relationship_uses_custom_placeholder(): void
    {
        $html = $this->capture(fn() => $this->f->relationship([
            'id'          => 'rel',
            'post_type'   => 'post',
            'placeholder' => 'Find a post…',
        ], '[]'));

        $this->assertStringContainsString('data-placeholder="Find a post…"', $html);
    }

    public function test_relationship_nonce_is_present_in_data_attribute(): void
    {
        $html = $this->capture(fn() => $this->f->relationship([
            'id'        => 'rel',
            'post_type' => 'post',
        ], '[]'));

        // wp_create_nonce stub returns 'test_nonce_coltman_relationship'
        $this->assertStringContainsString('data-nonce="test_nonce_coltman_relationship"', $html);
    }

    public function test_relationship_accepts_comma_separated_post_types(): void
    {
        $html = $this->capture(fn() => $this->f->relationship([
            'id'        => 'rel',
            'post_type' => 'post, page',
        ], '[]'));

        $this->assertStringContainsString('data-post-type="post,page"', $html);
    }

    public function test_relationship_accepts_array_post_types(): void
    {
        $html = $this->capture(fn() => $this->f->relationship([
            'id'        => 'rel',
            'post_type' => ['post', 'page', 'joyas_a_medida'],
        ], '[]'));

        $this->assertStringContainsString('data-post-type="post,page,joyas_a_medida"', $html);
    }

    // ── Map ──────────────────────────────────────────────────────────────────

    public function test_map_renders_hidden_input_with_value(): void
    {
        $value = '{"lat":40.4168,"lng":-3.7038,"zoom":12}';
        $html  = $this->capture(fn() => $this->f->map(['id' => 'location'], $value));
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('id="location"', $html);
        $this->assertStringContainsString('name="location"', $html);
        $this->assertStringContainsString(esc_attr($value), $html);
    }

    public function test_map_renders_container_with_data_attributes(): void
    {
        $html = $this->capture(fn() => $this->f->map([
            'id'   => 'loc',
            'zoom' => 15,
        ], '{"lat":51.5074,"lng":-0.1278,"zoom":15}'));

        $this->assertStringContainsString('class="coltman-map-container"', $html);
        $this->assertStringContainsString('id="map-loc"', $html);
        $this->assertStringContainsString('data-field="loc"', $html);
        $this->assertStringContainsString('data-lat="51.5074"', $html);
        $this->assertStringContainsString('data-lng="-0.1278"', $html);
        $this->assertStringContainsString('data-zoom="15"', $html);
    }

    public function test_map_renders_empty_when_no_value(): void
    {
        $html = $this->capture(fn() => $this->f->map(['id' => 'loc'], ''));
        $this->assertStringContainsString('data-lat=""', $html);
        $this->assertStringContainsString('data-lng=""', $html);
        $this->assertStringContainsString('data-zoom="13"', $html);
    }

    public function test_map_renders_lat_lng_readonly_inputs(): void
    {
        $html = $this->capture(fn() => $this->f->map([
            'id' => 'coords',
        ], '{"lat":48.8566,"lng":2.3522,"zoom":10}'));

        $this->assertStringContainsString('class="coltman-map-lat small-text"', $html);
        $this->assertStringContainsString('class="coltman-map-lng small-text"', $html);
        $this->assertStringContainsString('readonly', $html);
    }

    public function test_map_renders_clear_button(): void
    {
        $html = $this->capture(fn() => $this->f->map(['id' => 'loc'], ''));
        $this->assertStringContainsString('class="button coltman-map-clear"', $html);
    }

    public function test_map_uses_field_zoom_as_default_when_no_value(): void
    {
        $html = $this->capture(fn() => $this->f->map(['id' => 'loc', 'zoom' => 8], ''));
        $this->assertStringContainsString('data-zoom="8"', $html);
    }
}
