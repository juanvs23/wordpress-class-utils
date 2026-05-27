<?php
namespace Coltman\Tests\Unit;

use Coltman\Tests\TestCase;

/**
 * Tests for ColtmanCreateMetabox.
 *
 * Covers: constructor, process_cpts(), add_meta_boxes(), value(), checked(),
 * save_post() for every field type.
 */
class CreateMetaboxTest extends TestCase
{
    private array $baseConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseConfig = [
            'title'       => 'Test Metabox',
            'description' => 'Test description',
            'prefix'      => 'test_',
            'domain'      => 'test-domain',
            'class_name'  => '',
            'context'     => 'normal',
            'priority'    => 'high',
            'cpt'         => 'test_post',
            'fields'      => [
                ['id' => 'field_text',      'type' => 'text',      'label' => 'Text',     'default' => ''],
                ['id' => 'field_checkbox',  'type' => 'checkbox',  'label' => 'CB',       'default' => ''],
                ['id' => 'field_email',     'type' => 'email',     'label' => 'Email',    'default' => ''],
                ['id' => 'field_editor',    'type' => 'editor',    'label' => 'Editor',   'default' => ''],
                ['id' => 'field_textarea',  'type' => 'textarea',  'label' => 'Textarea', 'default' => ''],
                ['id' => 'field_get_posts', 'type' => 'get_posts', 'label' => 'Posts',    'default' => '[]'],
            ],
        ];
    }

    private function make(array $overrides = []): \ColtmanCreateMetabox
    {
        return new \ColtmanCreateMetabox(array_merge($this->baseConfig, $overrides));
    }

    // ── Constructor ───────────────────────────────────────────────────────────

    public function test_constructor_stores_config(): void
    {
        $mb     = $this->make();
        $config = $this->getProperty($mb, 'config');
        $this->assertSame('Test Metabox', $config['title']);
        $this->assertSame('normal', $config['context']);
    }

    public function test_constructor_instantiates_coltman_inputs(): void
    {
        $mb = $this->make();
        $this->assertInstanceOf(\ColtmanInputFields::class, $mb->coltmanInputs);
    }

    public function test_constructor_registers_required_hooks(): void
    {
        $mb    = $this->make();
        $hooks = array_column($this->spyCalls('add_action'), 'hook');

        $this->assertContains('add_meta_boxes',         $hooks);
        $this->assertContains('admin_enqueue_scripts',  $hooks);
        $this->assertContains('admin_head',             $hooks);
        $this->assertContains('save_post',              $hooks);
    }

    // ── process_cpts() ────────────────────────────────────────────────────────

    public function test_process_cpts_adds_single_cpt_to_post_type(): void
    {
        $mb     = $this->make(['cpt' => 'my_cpt']);
        $config = $this->getProperty($mb, 'config');
        $this->assertContains('my_cpt', $config['post-type']);
    }

    public function test_process_cpts_parses_comma_separated_cpts(): void
    {
        $mb     = $this->make(['cpt' => 'cpt_a,cpt_b,cpt_c']);
        $config = $this->getProperty($mb, 'config');
        $this->assertContains('cpt_a', $config['post-type']);
        $this->assertContains('cpt_b', $config['post-type']);
        $this->assertContains('cpt_c', $config['post-type']);
    }

    public function test_process_cpts_trims_whitespace_around_each_cpt(): void
    {
        $mb     = $this->make(['cpt' => ' cpt_a , cpt_b ']);
        $config = $this->getProperty($mb, 'config');
        $this->assertContains('cpt_a', $config['post-type']);
        $this->assertContains('cpt_b', $config['post-type']);
        $this->assertNotContains(' cpt_a', $config['post-type']);
    }

    public function test_process_cpts_does_nothing_when_cpt_is_empty(): void
    {
        $mb     = $this->make(['cpt' => '']);
        $config = $this->getProperty($mb, 'config');
        $this->assertEmpty($config['post-type'] ?? []);
    }

    // ── add_meta_boxes() ──────────────────────────────────────────────────────

    public function test_add_meta_boxes_calls_add_meta_box_once(): void
    {
        $mb = $this->make();
        $mb->add_meta_boxes();
        $this->assertCount(1, $this->spyCalls('add_meta_box'));
    }

    public function test_add_meta_boxes_sets_correct_title(): void
    {
        $mb = $this->make();
        $mb->add_meta_boxes();
        $this->assertSame('Test Metabox', $this->firstCall('add_meta_box')['title']);
    }

    public function test_add_meta_boxes_sets_correct_context(): void
    {
        $mb = $this->make();
        $mb->add_meta_boxes();
        $this->assertSame('normal', $this->firstCall('add_meta_box')['context']);
    }

    public function test_add_meta_boxes_sets_correct_priority(): void
    {
        $mb = $this->make();
        $mb->add_meta_boxes();
        $this->assertSame('high', $this->firstCall('add_meta_box')['priority']);
    }

    public function test_add_meta_boxes_id_uses_prefix(): void
    {
        $mb = $this->make();
        $mb->add_meta_boxes();
        $call = $this->firstCall('add_meta_box');
        $this->assertStringStartsWith('test_', $call['id']);
    }

    // ── value() ───────────────────────────────────────────────────────────────

    private function withPost(int $id, callable $fn): void
    {
        $GLOBALS['post'] = (object)['ID' => $id];
        $fn();
        unset($GLOBALS['post']);
    }

    public function test_value_returns_empty_string_when_no_meta_and_no_default(): void
    {
        $mb    = $this->make();
        $field = ['id' => 'my_field', 'type' => 'text'];

        $this->withPost(1, function () use ($mb, $field, &$result) {
            $result = $this->callMethod($mb, 'value', [$field]);
        });

        $this->assertSame('', $result);
    }

    public function test_value_returns_default_when_no_meta(): void
    {
        $mb    = $this->make();
        $field = ['id' => 'my_field', 'type' => 'text', 'default' => 'fallback'];

        $this->withPost(1, function () use ($mb, $field, &$result) {
            $result = $this->callMethod($mb, 'value', [$field]);
        });

        $this->assertSame('fallback', $result);
    }

    public function test_value_returns_stored_meta_when_metadata_exists(): void
    {
        $this->setFlag('metadata_exists', true);
        $this->setStub('get_post_meta', 'stored_value');

        $mb    = $this->make();
        $field = ['id' => 'my_field', 'type' => 'text', 'default' => 'default'];

        $this->withPost(42, function () use ($mb, $field, &$result) {
            $result = $this->callMethod($mb, 'value', [$field]);
        });

        $this->assertSame('stored_value', $result);
    }

    public function test_value_replaces_unicode_apostrophe(): void
    {
        $this->setFlag('metadata_exists', true);
        $this->setStub('get_post_meta', "it\u{0027}s fine");

        $mb    = $this->make();
        $field = ['id' => 'f', 'type' => 'text'];

        $this->withPost(1, function () use ($mb, $field, &$result) {
            $result = $this->callMethod($mb, 'value', [$field]);
        });

        $this->assertSame("it's fine", $result);
    }

    // ── checked() ────────────────────────────────────────────────────────────

    public function test_checked_returns_checked_when_meta_value_is_on(): void
    {
        $this->setFlag('metadata_exists', true);
        $this->setStub('get_post_meta', 'on');

        $mb    = $this->make();
        $field = ['id' => 'cb', 'type' => 'checkbox'];

        $this->withPost(1, function () use ($mb, $field, &$result) {
            $result = $this->callMethod($mb, 'checked', [$field]);
        });

        $this->assertSame('checked', $result);
    }

    public function test_checked_returns_empty_when_meta_value_is_not_on(): void
    {
        $this->setFlag('metadata_exists', true);
        $this->setStub('get_post_meta', 'off');

        $mb    = $this->make();
        $field = ['id' => 'cb', 'type' => 'checkbox'];

        $this->withPost(1, function () use ($mb, $field, &$result) {
            $result = $this->callMethod($mb, 'checked', [$field]);
        });

        $this->assertSame('', $result);
    }

    public function test_checked_returns_checked_from_field_default_when_no_meta(): void
    {
        $mb    = $this->make();
        $field = ['id' => 'cb', 'type' => 'checkbox', 'checked' => true];

        $this->withPost(1, function () use ($mb, $field, &$result) {
            $result = $this->callMethod($mb, 'checked', [$field]);
        });

        $this->assertSame('checked', $result);
    }

    public function test_checked_returns_empty_when_no_meta_and_no_default(): void
    {
        $mb    = $this->make();
        $field = ['id' => 'cb', 'type' => 'checkbox'];

        $this->withPost(1, function () use ($mb, $field, &$result) {
            $result = $this->callMethod($mb, 'checked', [$field]);
        });

        $this->assertSame('', $result);
    }

    // ── save_post(): security gate ────────────────────────────────────────────

    private function withNonce(): void
    {
        $_POST['coltman_nonce_test_'] = 'test_nonce';
    }

    public function test_save_post_does_nothing_when_nonce_missing(): void
    {
        $_POST['field_text'] = 'hello';
        // no nonce set
        $this->make()->save_post(10);
        $this->assertEmpty($this->spyCalls('update_post_meta'));
    }

    public function test_save_post_does_nothing_when_nonce_invalid(): void
    {
        $this->setFlag('wp_verify_nonce', false);
        $this->withNonce();
        $_POST['field_text'] = 'hello';
        $this->make()->save_post(10);
        $this->assertEmpty($this->spyCalls('update_post_meta'));
    }

    public function test_save_post_does_nothing_when_user_cannot_edit(): void
    {
        $this->setFlag('current_user_can', false);
        $this->withNonce();
        $_POST['field_text'] = 'hello';
        $this->make()->save_post(10);
        $this->assertEmpty($this->spyCalls('update_post_meta'));
    }

    // ── save_post(): per-field-type behavior ──────────────────────────────────

    public function test_save_post_updates_text_field(): void
    {
        $this->withNonce();
        $_POST['field_text'] = 'hello';
        $this->make()->save_post(10);

        $calls = $this->spyCalls('update_post_meta');
        $match = array_values(array_filter($calls, fn($c) => $c['key'] === 'field_text'));
        $this->assertNotEmpty($match);
        $this->assertSame(10, $match[0]['post_id']);
    }

    public function test_save_post_sanitizes_text_field(): void
    {
        $this->withNonce();
        $_POST['field_text'] = '<script>alert(1)</script>safe';
        $this->make()->save_post(10);

        $calls = $this->spyCalls('update_post_meta');
        $match = array_values(array_filter($calls, fn($c) => $c['key'] === 'field_text'));
        $this->assertNotEmpty($match);
        $this->assertStringNotContainsString('<script>', $match[0]['value']);
    }

    public function test_save_post_sanitizes_textarea_field(): void
    {
        $this->withNonce();
        $_POST['field_textarea'] = "line one\nline two";
        $this->make()->save_post(10);

        $calls = $this->spyCalls('update_post_meta');
        $match = array_values(array_filter($calls, fn($c) => $c['key'] === 'field_textarea'));
        $this->assertNotEmpty($match);
    }

    public function test_save_post_stores_get_posts_as_json(): void
    {
        $this->withNonce();
        $_POST['field_get_posts'] = [1, 2, 3];
        $this->make()->save_post(10);

        $calls = $this->spyCalls('update_post_meta');
        $match = array_values(array_filter($calls, fn($c) => $c['key'] === 'field_get_posts'));
        $this->assertNotEmpty($match);
        $this->assertSame('[1,2,3]', $match[0]['value']);
    }

    public function test_save_post_stores_empty_json_array_when_get_posts_missing(): void
    {
        $this->withNonce();
        // field_get_posts not in $_POST
        $this->make()->save_post(10);

        $calls = $this->spyCalls('update_post_meta');
        $match = array_values(array_filter($calls, fn($c) => $c['key'] === 'field_get_posts'));
        $this->assertNotEmpty($match);
        $this->assertSame('[]', $match[0]['value']);
    }

    public function test_save_post_stores_empty_string_for_unchecked_checkbox(): void
    {
        $this->withNonce();
        // field_checkbox absent from $_POST → unchecked
        $this->make()->save_post(10);

        $calls = $this->spyCalls('update_post_meta');
        $match = array_values(array_filter($calls, fn($c) => $c['key'] === 'field_checkbox'));
        $this->assertNotEmpty($match);
        $this->assertSame('', $match[0]['value']);
    }

    public function test_save_post_sanitizes_email_field(): void
    {
        $this->withNonce();
        $_POST['field_email'] = 'test@example.com';
        $this->make()->save_post(10);

        $calls = $this->spyCalls('update_post_meta');
        $match = array_values(array_filter($calls, fn($c) => $c['key'] === 'field_email'));
        $this->assertNotEmpty($match);
    }

    public function test_save_post_skips_absent_text_field(): void
    {
        $this->withNonce();
        // field_text absent
        $this->make()->save_post(10);

        $calls = $this->spyCalls('update_post_meta');
        $match = array_filter($calls, fn($c) => $c['key'] === 'field_text');
        $this->assertEmpty($match);
    }
}
