<?php
namespace Coltman\Tests\Unit;

use Coltman\Tests\TestCase;

/**
 * Tests for ColtmanTermMeta.
 *
 * Covers: constructor (admin-gate), wpturbo_format_field(),
 * wpturbo_render_input_field() for each type, wpturbo_save_meta_fields(),
 * wpturbo_render_meta_fields(), wpturbo_edit_meta_fields().
 */
class TermMetaTest extends TestCase
{
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = [
            'taxonomy' => 'test_tax',
            'title'    => 'Test Term Fields',
            'fields'   => [
                'field_text'     => ['label' => 'Name',    'type' => 'text',     'default' => ''],
                'field_textarea' => ['label' => 'Bio',     'type' => 'textarea', 'default' => ''],
                'field_email'    => ['label' => 'Email',   'type' => 'email',    'default' => ''],
                'field_select'   => [
                    'label'   => 'Color',
                    'type'    => 'select',
                    'default' => 'red',
                    'options' => ['red' => 'Red', 'blue' => 'Blue'],
                ],
                'field_media'    => [
                    'label'   => 'Image',
                    'type'    => 'media',
                    'return'  => 'url',
                    'default' => '',
                ],
            ],
        ];
    }

    private function makeAdmin(): \ColtmanTermMeta
    {
        $this->setFlag('is_admin', true);
        return new \ColtmanTermMeta($this->config);
    }

    // ── Constructor: admin gate ───────────────────────────────────────────────

    public function test_constructor_registers_hooks_when_is_admin_true(): void
    {
        $this->makeAdmin();

        $hooks = array_column($this->spyCalls('add_action'), 'hook');
        $this->assertContains('test_tax_add_form_fields',  $hooks);
        $this->assertContains('test_tax_edit_form_fields', $hooks);
        $this->assertContains('created_test_tax',          $hooks);
        $this->assertContains('edited_test_tax',           $hooks);
        $this->assertContains('admin_enqueue_scripts',     $hooks);
        $this->assertContains('admin_head',                $hooks);
    }

    public function test_constructor_does_not_register_hooks_when_not_admin(): void
    {
        // is_admin defaults to false
        new \ColtmanTermMeta($this->config);
        $this->assertEmpty($this->spyCalls('add_action'));
    }

    public function test_constructor_stores_fields_only_when_is_admin(): void
    {
        $tm     = $this->makeAdmin();
        $fields = $this->getProperty($tm, 'fields');
        $this->assertArrayHasKey('field_text', $fields);
    }

    public function test_fields_not_set_when_not_admin(): void
    {
        $tm     = new \ColtmanTermMeta($this->config);
        $fields = $this->getProperty($tm, 'fields');
        $this->assertNull($fields);
    }

    // ── wpturbo_format_field() ────────────────────────────────────────────────

    public function test_format_field_wraps_label_and_field_in_div(): void
    {
        $tm     = $this->makeAdmin();
        $result = $tm->wpturbo_format_field('<label>Name</label>', '<input>');

        $this->assertStringContainsString('form-field', $result);
        $this->assertStringContainsString('<label>Name</label>', $result);
        $this->assertStringContainsString('<input>', $result);
    }

    public function test_format_field_includes_flex_layout(): void
    {
        $tm     = $this->makeAdmin();
        $result = $tm->wpturbo_format_field('L', 'F');
        $this->assertStringContainsString('flex items-center', $result);
    }

    // ── constructor: coltmanInputs wired ─────────────────────────────────────

    public function test_constructor_instantiates_coltman_inputs(): void
    {
        $tm = $this->makeAdmin();
        $this->assertInstanceOf(\ColtmanInputFields::class, $tm->coltmanInputs);
    }

    public function test_constructor_normalizes_field_id_from_key(): void
    {
        $tm     = $this->makeAdmin();
        $fields = $this->getProperty($tm, 'fields');
        $this->assertSame('field_text', $fields['field_text']['id']);
    }

    // ── wpturbo_render_input_field(): delegates to ColtmanInputFields ─────────

    public function test_render_input_field_text_type(): void
    {
        $tm   = $this->makeAdmin();
        $html = $tm->wpturbo_render_input_field('field_text', ['type' => 'text'], 'hello');

        // ColtmanInputFields::input() uses double quotes and class attributes
        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('name="field_text"', $html);
        $this->assertStringContainsString('value="hello"', $html);
    }

    public function test_render_input_field_textarea_type(): void
    {
        $tm   = $this->makeAdmin();
        $html = $tm->wpturbo_render_input_field('field_ta', ['type' => 'textarea'], 'bio text');

        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('name="field_ta"', $html);
        $this->assertStringContainsString('bio text', $html);
    }

    public function test_render_input_field_select_type(): void
    {
        $tm   = $this->makeAdmin();
        $html = $tm->wpturbo_render_input_field('field_sel', [
            'type'    => 'select',
            'options' => ['a' => 'Alpha', 'b' => 'Beta'],
        ], 'b');

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('value="b"', $html);
        $this->assertStringContainsString('selected="selected"', $html);
        $this->assertStringContainsString('Alpha', $html);
    }

    public function test_render_input_field_select_marks_correct_option(): void
    {
        $tm   = $this->makeAdmin();
        $html = $tm->wpturbo_render_input_field('sel', [
            'type'    => 'select',
            'options' => ['x' => 'X', 'y' => 'Y'],
        ], 'x');

        $this->assertStringContainsString('value="x"', $html);
        $selectedCount = substr_count($html, 'selected="selected"');
        $this->assertSame(1, $selectedCount);
    }

    public function test_render_input_field_media_type(): void
    {
        $tm   = $this->makeAdmin();
        $html = $tm->wpturbo_render_input_field('field_img', [
            'type'   => 'media',
            'return' => 'url',
        ], '');

        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('<button', $html);
        $this->assertStringContainsString('rwp-media-toggle', $html);
    }

    public function test_render_input_field_media_uses_coltman_text_domain_for_defaults(): void
    {
        $tm   = $this->makeAdmin();
        $html = $tm->wpturbo_render_input_field('f', ['type' => 'media', 'return' => 'url'], '');

        // ColtmanInputFields::media_button() uses 'Choose a file' (not 'Choose an image')
        $this->assertStringContainsString('Select this file', $html);
        $this->assertStringContainsString('Choose a file', $html);
        $this->assertStringContainsString('Upload', $html);
    }

    public function test_render_input_field_returns_string(): void
    {
        $tm = $this->makeAdmin();
        $this->assertIsString($tm->wpturbo_render_input_field('f', ['type' => 'text'], ''));
    }

    // ── wpturbo_save_meta_fields(): security + sanitization ───────────────────

    public function test_save_does_nothing_when_user_lacks_permission(): void
    {
        $this->setFlag('current_user_can', false);
        $_POST['field_text'] = 'My Name';
        $this->makeAdmin()->wpturbo_save_meta_fields(5);

        $this->assertEmpty($this->spyCalls('update_term_meta'));
    }

    public function test_save_updates_text_field(): void
    {
        $_POST['field_text'] = 'My Name';
        $this->makeAdmin()->wpturbo_save_meta_fields(5);

        $calls = $this->spyCalls('update_term_meta');
        $match = array_values(array_filter($calls, fn($c) => $c['key'] === 'field_text'));
        $this->assertNotEmpty($match);
        $this->assertSame(5, $match[0]['term_id']);
    }

    public function test_save_sanitizes_text_field(): void
    {
        $_POST['field_text'] = '<b>bold</b>';
        $this->makeAdmin()->wpturbo_save_meta_fields(5);

        $calls = $this->spyCalls('update_term_meta');
        $match = array_values(array_filter($calls, fn($c) => $c['key'] === 'field_text'));
        $this->assertNotEmpty($match);
        $this->assertStringNotContainsString('<b>', $match[0]['value']);
    }

    public function test_save_sanitizes_email_field(): void
    {
        $_POST['field_email'] = 'user@example.com';
        $this->makeAdmin()->wpturbo_save_meta_fields(5);

        $calls = $this->spyCalls('update_term_meta');
        $match = array_values(array_filter($calls, fn($c) => $c['key'] === 'field_email'));
        $this->assertNotEmpty($match);
    }

    public function test_save_sanitizes_textarea_field(): void
    {
        $_POST['field_textarea'] = "line one\nline two";
        $this->makeAdmin()->wpturbo_save_meta_fields(5);

        $calls = $this->spyCalls('update_term_meta');
        $match = array_values(array_filter($calls, fn($c) => $c['key'] === 'field_textarea'));
        $this->assertNotEmpty($match);
    }

    public function test_save_skips_absent_fields(): void
    {
        // No $_POST data
        $this->makeAdmin()->wpturbo_save_meta_fields(5);

        $this->assertEmpty($this->spyCalls('update_term_meta'));
    }

    // ── wpturbo_render_meta_fields(): add form ────────────────────────────────

    public function test_render_meta_fields_outputs_html_for_each_field(): void
    {
        $html = $this->capture(fn() => $this->makeAdmin()->wpturbo_render_meta_fields('test_tax'));

        // Expect a wrapper for each field
        $this->assertStringContainsString('form-field', $html);
        $this->assertStringContainsString('field_text', $html);
    }

    public function test_render_meta_fields_shows_default_value(): void
    {
        $config          = $this->config;
        $config['fields']['field_text']['default'] = 'preset';

        $this->setFlag('is_admin', true);
        $tm   = new \ColtmanTermMeta($config);
        $html = $this->capture(fn() => $tm->wpturbo_render_meta_fields('test_tax'));

        $this->assertStringContainsString('preset', $html);
    }

    // ── wpturbo_edit_meta_fields(): edit form ─────────────────────────────────

    public function test_edit_meta_fields_outputs_html(): void
    {
        $this->setStub('get_term_meta', 'stored text');

        $term = new \WP_Term(['term_id' => 3, 'name' => 'Test Term']);
        $html = $this->capture(fn() => $this->makeAdmin()->wpturbo_edit_meta_fields($term, 'test_tax'));

        $this->assertStringContainsString('form-field', $html);
    }
}
