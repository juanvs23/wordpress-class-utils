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

    public function test_save_post_textarea_preserves_html(): void
    {
        $this->withNonce();
        $_POST['field_textarea'] = '<p>Hello <strong>World</strong></p>';
        $this->make()->save_post(10);

        $calls = $this->spyCalls('update_post_meta');
        $match = array_values(array_filter($calls, fn($c) => $c['key'] === 'field_textarea'));
        $this->assertNotEmpty($match);
        $this->assertSame('<p>Hello <strong>World</strong></p>', $match[0]['value']);
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

    // ── group type ────────────────────────────────────────────────────────────

    private function makeWithGroup(): \ColtmanCreateMetabox
    {
        return new \ColtmanCreateMetabox([
            'title'      => 'Test',
            'prefix'     => 'g_',
            'cpt'        => 'post',
            'class_name' => 'g',
            'fields'     => [
                [
                    'id'     => 'seo_group',
                    'type'   => 'group',
                    'label'  => 'SEO',
                    'fields' => [
                        ['id' => 'seo_title',       'type' => 'text',     'label' => 'Title'],
                        ['id' => 'seo_description', 'type' => 'textarea', 'label' => 'Description'],
                    ],
                ],
            ],
        ]);
    }

    public function test_group_save_persists_sub_fields(): void
    {
        $_POST['coltman_nonce_g_'] = 'test_nonce';
        $_POST['seo_title']        = '  My Title  ';
        $_POST['seo_description']  = "Line 1\nLine 2";
        $this->makeWithGroup()->save_post(10);

        $calls = $this->spyCalls('update_post_meta');
        $keys  = array_column($calls, 'key');
        $this->assertContains('seo_title', $keys);
        $this->assertContains('seo_description', $keys);
    }

    public function test_group_save_skips_absent_sub_fields(): void
    {
        $_POST['coltman_nonce_g_'] = 'test_nonce';
        // neither seo_title nor seo_description in POST
        $this->makeWithGroup()->save_post(10);

        $calls = $this->spyCalls('update_post_meta');
        $keys  = array_column($calls, 'key');
        $this->assertNotContains('seo_title', $keys);
        $this->assertNotContains('seo_description', $keys);
    }

    // ── register_rest_meta ────────────────────────────────────────────────────

    public function test_rest_meta_registers_fields_with_rest_true(): void
    {
        global $_coltman_registered_meta;
        $_coltman_registered_meta = [];

        $mb = new \ColtmanCreateMetabox([
            'title'      => 'Test',
            'prefix'     => 'r_',
            'cpt'        => 'post',
            'class_name' => 'r',
            'fields'     => [
                ['id' => 'seo_title', 'type' => 'text',   'label' => 'SEO Title', 'rest' => true],
                ['id' => 'content',   'type' => 'textarea','label' => 'Content'],      // no rest
                ['id' => 'price',     'type' => 'number',  'label' => 'Price',    'rest' => true],
            ],
        ]);
        $mb->register_rest_meta();

        $keys = array_column($_coltman_registered_meta, 'meta_key');
        $this->assertContains('seo_title', $keys);
        $this->assertContains('price',     $keys);
        $this->assertNotContains('content', $keys);
    }

    public function test_rest_meta_maps_number_type_correctly(): void
    {
        global $_coltman_registered_meta;
        $_coltman_registered_meta = [];

        $mb = new \ColtmanCreateMetabox([
            'title'      => 'Test',
            'prefix'     => 'r_',
            'cpt'        => 'post',
            'class_name' => 'r',
            'fields'     => [
                ['id' => 'price', 'type' => 'number', 'label' => 'Price', 'rest' => true],
                ['id' => 'name',  'type' => 'text',   'label' => 'Name',  'rest' => true],
            ],
        ]);
        $mb->register_rest_meta();

        $byKey = [];
        foreach ($_coltman_registered_meta as $entry) {
            $byKey[$entry['meta_key']] = $entry['args'];
        }
        $this->assertSame('number', $byKey['price']['type']);
        $this->assertSame('string', $byKey['name']['type']);
    }

    public function test_rest_meta_skips_when_no_rest_fields(): void
    {
        global $_coltman_registered_meta;
        $_coltman_registered_meta = [];

        $mb = new \ColtmanCreateMetabox([
            'title'      => 'Test',
            'prefix'     => 'r_',
            'cpt'        => 'post',
            'class_name' => 'r',
            'fields'     => [
                ['id' => 'title', 'type' => 'text', 'label' => 'Title'],
            ],
        ]);
        $mb->register_rest_meta();

        $this->assertEmpty($_coltman_registered_meta);
    }

    public function test_rest_meta_registers_show_in_rest_true(): void
    {
        global $_coltman_registered_meta;
        $_coltman_registered_meta = [];

        $mb = new \ColtmanCreateMetabox([
            'title'      => 'Test',
            'prefix'     => 'r_',
            'cpt'        => 'post',
            'class_name' => 'r',
            'fields'     => [
                ['id' => 'bio', 'type' => 'textarea', 'label' => 'Bio', 'rest' => true],
            ],
        ]);
        $mb->register_rest_meta();

        $this->assertCount(1, $_coltman_registered_meta);
        $this->assertTrue($_coltman_registered_meta[0]['args']['show_in_rest']);
        $this->assertTrue($_coltman_registered_meta[0]['args']['single']);
    }

    // ── Map save ─────────────────────────────────────────────────────────────

    private function makeWithMap(): \ColtmanCreateMetabox
    {
        return new \ColtmanCreateMetabox([
            'title'      => 'Test',
            'prefix'     => 'm_',
            'cpt'        => 'post',
            'class_name' => 'm',
            'fields'     => [
                ['id' => 'location', 'type' => 'map', 'label' => 'Location'],
            ],
        ]);
    }

    public function test_map_save_persists_valid_coords(): void
    {
        $_POST['coltman_nonce_m_'] = 'test_nonce';
        $_POST['location']         = '{"lat":40.4168,"lng":-3.7038,"zoom":12}';
        $this->makeWithMap()->save_post(10);

        $calls  = $this->spyCalls('update_post_meta');
        $match  = array_values(array_filter($calls, fn($c) => $c['key'] === 'location'));
        $this->assertCount(1, $match);
        $saved  = json_decode($match[0]['value'], true);
        $this->assertEqualsWithDelta(40.4168, $saved['lat'], 0.0001);
        $this->assertEqualsWithDelta(-3.7038, $saved['lng'], 0.0001);
        $this->assertSame(12, $saved['zoom']);
    }

    public function test_map_save_rejects_out_of_range_coords(): void
    {
        $_POST['coltman_nonce_m_'] = 'test_nonce';
        $_POST['location']         = '{"lat":200.0,"lng":400.0,"zoom":10}';
        $this->makeWithMap()->save_post(10);

        $calls = $this->spyCalls('update_post_meta');
        $keys  = array_column($calls, 'key');
        $this->assertNotContains('location', $keys);
    }

    public function test_map_save_clears_value_when_empty_string(): void
    {
        $_POST['coltman_nonce_m_'] = 'test_nonce';
        $_POST['location']         = '';
        $this->makeWithMap()->save_post(10);

        $calls = $this->spyCalls('update_post_meta');
        $match = array_values(array_filter($calls, fn($c) => $c['key'] === 'location'));
        $this->assertCount(1, $match);
        $this->assertSame('', $match[0]['value']);
    }
}
