<?php
namespace Coltman\Tests\Unit;

use Coltman\Tests\TestCase;

/**
 * Tests for ColtmanRegisterTaxonomy.
 *
 * Covers: constructor, label building, args building, default values,
 * custom capabilities override, hook registration, register_new_taxonomy().
 */
class RegisterTaxonomyTest extends TestCase
{
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = [
            'plural_name'       => 'Event Types',
            'singular_name'     => 'Event Type',
            'item'              => 'Event Type',
            'text_domain'       => 'test-domain',
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_in_rest'      => true,
            'rest_base'         => 'event-types',
        ];
    }

    private function make(
        array      $postTypes = [],
        array|bool $rewrite   = false,
        array      $configOverrides = []
    ): \ColtmanRegisterTaxonomy {
        return new \ColtmanRegisterTaxonomy(
            array_merge($this->config, $configOverrides),
            'test_event_type',
            $postTypes,
            $rewrite
        );
    }

    // ── Constructor: stored properties ────────────────────────────────────────

    public function test_taxonomy_name_is_stored(): void
    {
        $this->assertSame(
            'test_event_type',
            $this->getProperty($this->make(), 'taxonomy_name')
        );
    }

    public function test_post_types_are_stored(): void
    {
        $this->assertSame(
            ['my_post'],
            $this->getProperty($this->make(['my_post']), 'post_types')
        );
    }

    public function test_rewrite_false_is_stored(): void
    {
        $this->assertFalse($this->getProperty($this->make(), 'rewrite'));
    }

    public function test_rewrite_array_is_stored(): void
    {
        $rewrite = ['slug' => 'event-types', 'with_front' => true];
        $this->assertSame($rewrite, $this->getProperty($this->make([], $rewrite), 'rewrite'));
    }

    // ── Constructor: labels ───────────────────────────────────────────────────

    public function test_constructor_builds_labels_array(): void
    {
        $this->assertIsArray($this->getProperty($this->make(), 'labels'));
    }

    public function test_labels_contains_all_required_keys(): void
    {
        $labels   = $this->getProperty($this->make(), 'labels');
        $required = [
            'name', 'singular_name', 'menu_name', 'all_items', 'parent_item',
            'parent_item_colon', 'new_item_name', 'add_new_item', 'edit_item',
            'update_item', 'view_item', 'separate_items_with_commas',
            'add_or_remove_items', 'choose_from_most_used', 'popular_items',
            'search_items', 'not_found', 'no_terms', 'items_list',
            'items_list_navigation',
        ];

        foreach ($required as $key) {
            $this->assertArrayHasKey($key, $labels, "Missing label key: $key");
        }
    }

    public function test_labels_name_contains_plural_name(): void
    {
        $labels = $this->getProperty($this->make(), 'labels');
        $this->assertStringContainsString('Event Types', $labels['name']);
    }

    public function test_labels_singular_name_contains_singular_name(): void
    {
        $labels = $this->getProperty($this->make(), 'labels');
        $this->assertStringContainsString('Event Type', $labels['singular_name']);
    }

    public function test_labels_search_items_contains_plural_name(): void
    {
        $labels = $this->getProperty($this->make(), 'labels');
        $this->assertStringContainsString('Event Types', $labels['search_items']);
    }

    // ── Constructor: args ─────────────────────────────────────────────────────

    public function test_args_hierarchical_is_set(): void
    {
        $args = $this->getProperty($this->make(), 'args');
        $this->assertFalse($args['hierarchical']);
    }

    public function test_args_public_is_set(): void
    {
        $args = $this->getProperty($this->make(), 'args');
        $this->assertTrue($args['public']);
    }

    public function test_args_rest_base_is_set(): void
    {
        $args = $this->getProperty($this->make(), 'args');
        $this->assertSame('event-types', $args['rest_base']);
    }

    public function test_args_rewrite_is_propagated(): void
    {
        $rewrite = ['slug' => 'types'];
        $args    = $this->getProperty($this->make([], $rewrite), 'args');
        $this->assertSame($rewrite, $args['rewrite']);
    }

    // ── Default values ────────────────────────────────────────────────────────

    public function test_show_in_menu_defaults_to_true_when_absent(): void
    {
        $args = $this->getProperty($this->make(), 'args');
        $this->assertTrue($args['show_in_menu']);
    }

    public function test_show_in_menu_uses_config_value_when_set(): void
    {
        $args = $this->getProperty($this->make([], false, ['show_in_menu' => false]), 'args');
        $this->assertFalse($args['show_in_menu']);
    }

    public function test_show_tagcloud_defaults_to_true(): void
    {
        $args = $this->getProperty($this->make(), 'args');
        $this->assertTrue($args['show_tagcloud']);
    }

    public function test_show_tagcloud_uses_config_value_when_set(): void
    {
        $args = $this->getProperty($this->make([], false, ['show_tagcloud' => false]), 'args');
        $this->assertFalse($args['show_tagcloud']);
    }

    // ── Default capabilities ──────────────────────────────────────────────────

    public function test_default_capabilities_are_applied(): void
    {
        $args = $this->getProperty($this->make(), 'args');
        $caps = $args['capabilities'];

        $this->assertSame('manage_categories', $caps['manage_terms']);
        $this->assertSame('manage_categories', $caps['edit_terms']);
        $this->assertSame('manage_categories', $caps['delete_terms']);
        $this->assertSame('edit_posts',        $caps['assign_terms']);
    }

    public function test_custom_capabilities_override_defaults(): void
    {
        $custom = ['manage_terms' => 'manage_options', 'assign_terms' => 'publish_posts'];
        $args   = $this->getProperty($this->make([], false, ['capabilities' => $custom]), 'args');
        $this->assertSame('manage_options',  $args['capabilities']['manage_terms']);
        $this->assertSame('publish_posts',   $args['capabilities']['assign_terms']);
    }

    // ── Hook registration ─────────────────────────────────────────────────────

    public function test_constructor_registers_init_hook(): void
    {
        $this->make();
        $hooks = array_column($this->spyCalls('add_action'), 'hook');
        $this->assertContains('init', $hooks);
    }

    public function test_init_callback_is_register_new_taxonomy(): void
    {
        $tax   = $this->make();
        $calls = $this->spyCalls('add_action');
        $init  = array_values(array_filter($calls, fn($c) => $c['hook'] === 'init'));
        $this->assertNotEmpty($init);
        $this->assertSame([$tax, 'register_new_taxonomy'], $init[0]['callback']);
    }

    // ── register_new_taxonomy() ───────────────────────────────────────────────

    public function test_register_new_taxonomy_calls_register_taxonomy_once(): void
    {
        $tax = $this->make(['test_post']);
        $tax->register_new_taxonomy();

        $this->assertCount(1, $this->spyCalls('register_taxonomy'));
    }

    public function test_register_new_taxonomy_passes_correct_slug(): void
    {
        $tax = $this->make(['test_post']);
        $tax->register_new_taxonomy();

        $call = $this->firstCall('register_taxonomy');
        $this->assertSame('test_event_type', $call['taxonomy']);
    }

    public function test_register_new_taxonomy_passes_post_types(): void
    {
        $tax = $this->make(['test_post', 'another_post']);
        $tax->register_new_taxonomy();

        $call = $this->firstCall('register_taxonomy');
        $this->assertSame(['test_post', 'another_post'], $call['post_types']);
    }

    public function test_register_new_taxonomy_passes_args(): void
    {
        $tax = $this->make();
        $tax->register_new_taxonomy();

        $call = $this->firstCall('register_taxonomy');
        $this->assertArrayHasKey('labels',       $call['args']);
        $this->assertArrayHasKey('capabilities', $call['args']);
        $this->assertArrayHasKey('public',       $call['args']);
    }
}
