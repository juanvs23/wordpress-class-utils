<?php
namespace Coltman\Tests\Unit;

use Coltman\Tests\TestCase;

/**
 * Tests for ColtmanCreateUserMeta.
 *
 * Covers: constructor, get_user_meta_value(), get_checked(),
 * save_user_meta() for every field type, add_user_meta_section().
 */
class UserMetaTest extends TestCase
{
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();
        // Nonce is verified against user_id=1 in save tests
        $_POST['coltman_user_meta_nonce'] = 'test_nonce';

        $this->config = [
            'title'       => 'Extra User Fields',
            'description' => 'Additional profile data',
            'fields'      => [
                ['id' => 'bio',        'type' => 'textarea', 'label' => 'Biography'],
                ['id' => 'website',    'type' => 'url',      'label' => 'Website'],
                ['id' => 'age',        'type' => 'number',   'label' => 'Age'],
                ['id' => 'newsletter', 'type' => 'checkbox', 'label' => 'Newsletter'],
                ['id' => 'contact',    'type' => 'email',    'label' => 'Contact Email'],
                ['id' => 'notes',      'type' => 'editor',   'label' => 'Notes'],
                ['id' => 'posts', 'type' => 'get_posts', 'label' => 'Related Posts', 'post_type' => 'post'],
            ],
        ];
    }

    private function make(): \ColtmanCreateUserMeta
    {
        return new \ColtmanCreateUserMeta($this->config);
    }

    // ── Constructor ───────────────────────────────────────────────────────────

    public function test_constructor_stores_config(): void
    {
        $um = $this->make();
        $this->assertSame('Extra User Fields', $this->getProperty($um, 'config')['title']);
    }

    public function test_constructor_instantiates_coltman_inputs(): void
    {
        $um = $this->make();
        $this->assertInstanceOf(\ColtmanInputFields::class, $um->coltmanInputs);
    }

    public function test_constructor_registers_profile_hooks(): void
    {
        $this->make();
        $hooks = array_column($this->spyCalls('add_action'), 'hook');

        $this->assertContains('show_user_profile',      $hooks);
        $this->assertContains('edit_user_profile',      $hooks);
        $this->assertContains('personal_options_update', $hooks);
        $this->assertContains('edit_user_profile_update', $hooks);
        $this->assertContains('admin_enqueue_scripts',  $hooks);
    }

    // ── get_user_meta_value() ─────────────────────────────────────────────────

    public function test_get_user_meta_value_returns_stored_value(): void
    {
        $this->setStub('get_user_meta', 'stored bio');

        $um    = $this->make();
        $field = ['id' => 'bio', 'type' => 'textarea'];
        $value = $this->callMethod($um, 'get_user_meta_value', [42, $field]);

        $this->assertSame('stored bio', $value);
    }

    public function test_get_user_meta_value_falls_back_to_default(): void
    {
        $this->setStub('get_user_meta', '');

        $um    = $this->make();
        $field = ['id' => 'bio', 'type' => 'textarea', 'default' => 'default text'];
        $value = $this->callMethod($um, 'get_user_meta_value', [42, $field]);

        $this->assertSame('default text', $value);
    }

    public function test_get_user_meta_value_returns_empty_when_no_default(): void
    {
        $this->setStub('get_user_meta', '');

        $um    = $this->make();
        $field = ['id' => 'bio', 'type' => 'textarea'];
        $value = $this->callMethod($um, 'get_user_meta_value', [42, $field]);

        $this->assertSame('', $value);
    }

    // ── get_checked() ─────────────────────────────────────────────────────────

    public function test_get_checked_returns_checked_for_on_value(): void
    {
        $this->setStub('get_user_meta', 'on');

        $um     = $this->make();
        $result = $this->callMethod($um, 'get_checked', [1, ['id' => 'nl', 'type' => 'checkbox']]);
        $this->assertSame('checked', $result);
    }

    public function test_get_checked_returns_checked_for_one_string(): void
    {
        $this->setStub('get_user_meta', '1');

        $um     = $this->make();
        $result = $this->callMethod($um, 'get_checked', [1, ['id' => 'nl', 'type' => 'checkbox']]);
        $this->assertSame('checked', $result);
    }

    public function test_get_checked_returns_empty_for_zero(): void
    {
        $this->setStub('get_user_meta', '0');

        $um     = $this->make();
        $result = $this->callMethod($um, 'get_checked', [1, ['id' => 'nl', 'type' => 'checkbox']]);
        $this->assertSame('', $result);
    }

    public function test_get_checked_returns_checked_from_field_default(): void
    {
        $this->setStub('get_user_meta', '');

        $um     = $this->make();
        $result = $this->callMethod($um, 'get_checked', [
            1,
            ['id' => 'nl', 'type' => 'checkbox', 'checked' => true],
        ]);
        $this->assertSame('checked', $result);
    }

    public function test_get_checked_returns_empty_when_no_value_and_no_default(): void
    {
        $this->setStub('get_user_meta', '');

        $um     = $this->make();
        $result = $this->callMethod($um, 'get_checked', [1, ['id' => 'nl', 'type' => 'checkbox']]);
        $this->assertSame('', $result);
    }

    // ── save_user_meta() ──────────────────────────────────────────────────────

    public function test_save_returns_false_when_user_cannot_edit(): void
    {
        $this->setFlag('current_user_can', false);

        $result = $this->make()->save_user_meta(1);
        $this->assertFalse($result);
        $this->assertNotCalled('update_user_meta');
    }

    public function test_save_sanitizes_textarea_field(): void
    {
        $_POST['bio'] = "Line 1\nLine 2";
        $this->make()->save_user_meta(5);

        $calls = $this->spyCalls('update_user_meta');
        $match = array_values(array_filter($calls, fn($c) => $c['key'] === 'bio'));
        $this->assertNotEmpty($match);
        $this->assertSame(5, $match[0]['user_id']);
    }

    public function test_save_textarea_preserves_html(): void
    {
        $_POST['bio'] = '<p>Hello <em>World</em></p>';
        $this->make()->save_user_meta(5);

        $calls = $this->spyCalls('update_user_meta');
        $match = array_values(array_filter($calls, fn($c) => $c['key'] === 'bio'));
        $this->assertNotEmpty($match);
        $this->assertSame('<p>Hello <em>World</em></p>', $match[0]['value']);
    }

    public function test_save_sanitizes_email_field(): void
    {
        $_POST['contact'] = 'user@example.com';
        $this->make()->save_user_meta(5);

        $calls = $this->spyCalls('update_user_meta');
        $match = array_values(array_filter($calls, fn($c) => $c['key'] === 'contact'));
        $this->assertNotEmpty($match);
    }

    public function test_save_encodes_get_posts_as_json(): void
    {
        $_POST['posts'] = [10, 20, 30];
        $this->make()->save_user_meta(5);

        $calls = $this->spyCalls('update_user_meta');
        $match = array_values(array_filter($calls, fn($c) => $c['key'] === 'posts'));
        $this->assertNotEmpty($match);
        $this->assertSame('[10,20,30]', $match[0]['value']);
    }

    public function test_save_stores_empty_json_when_get_posts_not_posted(): void
    {
        // 'posts' not in $_POST
        $this->make()->save_user_meta(5);

        $calls = $this->spyCalls('update_user_meta');
        $match = array_values(array_filter($calls, fn($c) => $c['key'] === 'posts'));
        $this->assertNotEmpty($match);
        $this->assertSame('[]', $match[0]['value']);
    }

    public function test_save_applies_wp_filter_post_kses_to_editor_field(): void
    {
        $_POST['notes'] = '<p>Hello <script>bad</script></p>';
        $this->make()->save_user_meta(5);

        $calls = $this->spyCalls('update_user_meta');
        $match = array_values(array_filter($calls, fn($c) => $c['key'] === 'notes'));
        $this->assertNotEmpty($match);
    }

    public function test_save_sanitizes_url_field(): void
    {
        $_POST['website'] = 'https://example.com';
        $this->make()->save_user_meta(5);

        $calls = $this->spyCalls('update_user_meta');
        $match = array_values(array_filter($calls, fn($c) => $c['key'] === 'website'));
        $this->assertNotEmpty($match);
        $this->assertStringContainsString('example.com', $match[0]['value']);
    }

    public function test_save_sanitizes_default_field_as_text(): void
    {
        $_POST['age'] = '  25  ';
        $this->make()->save_user_meta(5);

        $calls = $this->spyCalls('update_user_meta');
        $match = array_values(array_filter($calls, fn($c) => $c['key'] === 'age'));
        $this->assertNotEmpty($match);
        // sanitize_text_field trims
        $this->assertSame('25', $match[0]['value']);
    }

    // ── add_user_meta_section() ───────────────────────────────────────────────

    public function test_add_user_meta_section_outputs_title(): void
    {
        $user = new \WP_User(1);
        $html = $this->capture(fn() => $this->make()->add_user_meta_section($user));
        $this->assertStringContainsString('Extra User Fields', $html);
    }

    public function test_add_user_meta_section_outputs_description(): void
    {
        $user = new \WP_User(1);
        $html = $this->capture(fn() => $this->make()->add_user_meta_section($user));
        $this->assertStringContainsString('Additional profile data', $html);
    }

    public function test_add_user_meta_section_outputs_form_table(): void
    {
        $user = new \WP_User(1);
        $html = $this->capture(fn() => $this->make()->add_user_meta_section($user));
        $this->assertStringContainsString('<table class="form-table">', $html);
    }

    public function test_add_user_meta_section_no_description_when_absent(): void
    {
        $config          = $this->config;
        unset($config['description']);

        $um   = new \ColtmanCreateUserMeta($config);
        $user = new \WP_User(1);
        $html = $this->capture(fn() => $um->add_user_meta_section($user));

        $this->assertStringNotContainsString('class="description"', $html);
    }
}
