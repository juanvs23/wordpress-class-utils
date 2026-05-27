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
        $this->assertStringContainsString("pattern='[0-9]+'", $html);
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
        $this->assertStringContainsString("min='0'", $html);
    }

    public function test_input_minmax_includes_max_attribute(): void
    {
        $html = $this->capture(fn() => $this->f->input_minmax(['id' => 'n', 'type' => 'number', 'max' => '100']));
        $this->assertStringContainsString("max='100'", $html);
    }

    public function test_input_minmax_includes_step_attribute(): void
    {
        $html = $this->capture(fn() => $this->f->input_minmax(['id' => 'n', 'type' => 'number', 'step' => '0.5']));
        $this->assertStringContainsString("step='0.5'", $html);
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

    public function test_get_posts_disabled_when_no_posts_available(): void
    {
        $this->setStub('get_posts', []);

        $html = $this->capture(fn() => $this->f->get_posts([
            'id'        => 'related',
            'post_type' => 'page',
        ], '[]'));

        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString("Don't have posts available", $html);
    }

    public function test_get_posts_renders_options_from_results(): void
    {
        $post           = new \stdClass();
        $post->ID       = 99;
        $post->post_title = 'Sample Page';

        $this->setStub('get_posts', [$post]);

        $html = $this->capture(fn() => $this->f->get_posts([
            'id'        => 'related',
            'post_type' => 'page',
        ], '[]'));

        $this->assertStringContainsString('value="99"', $html);
        $this->assertStringContainsString('Sample Page', $html);
    }

    // ── get_terms() ───────────────────────────────────────────────────────────

    public function test_get_terms_outputs_select_element(): void
    {
        $html = $this->capture(fn() => $this->f->get_terms([
            'id'       => 'tax_field',
            'taxonomy' => 'category',
        ]));

        $this->assertStringContainsString('<select', $html);
    }

    public function test_get_terms_disabled_and_placeholder_when_no_terms(): void
    {
        $this->setStub('get_terms', []);

        $html = $this->capture(fn() => $this->f->get_terms([
            'id'       => 'tax_field',
            'taxonomy' => 'category',
        ]));

        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString("Don't have terms available", $html);
    }

    public function test_get_terms_renders_term_options(): void
    {
        $term           = new \stdClass();
        $term->term_id  = 7;
        $term->name     = 'JavaScript';

        $this->setStub('get_terms', [$term]);

        $html = $this->capture(fn() => $this->f->get_terms([
            'id'       => 'tax_field',
            'taxonomy' => 'category',
        ]));

        $this->assertStringContainsString('value="7"', $html);
        $this->assertStringContainsString('JavaScript', $html);
    }

    public function test_get_terms_adds_select_a_term_placeholder(): void
    {
        $term          = new \stdClass();
        $term->term_id = 1;
        $term->name    = 'PHP';

        $this->setStub('get_terms', [$term]);

        $html = $this->capture(fn() => $this->f->get_terms([
            'id'       => 'tax_field',
            'taxonomy' => 'category',
        ]));

        $this->assertStringContainsString('Select a term', $html);
    }

    // ── gallery_input() ───────────────────────────────────────────────────────

    public function test_gallery_input_outputs_gallery_wrapper(): void
    {
        $html = $this->capture(fn() => $this->f->gallery_input(['id' => 'gallery'], ''));
        $this->assertStringContainsString('class="gallery"', $html);
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
}
