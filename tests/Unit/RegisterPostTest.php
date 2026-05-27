<?php
namespace Coltman\Tests\Unit;

use Coltman\Tests\TestCase;

/**
 * Tests for ColtmanRegisterPost.
 *
 * Covers: constructor, label building, args building, hook registration,
 * register_new_post_type().
 */
class RegisterPostTest extends TestCase
{
    private array $labelArgs;
    private array $args;

    protected function setUp(): void
    {
        parent::setUp();

        $this->labelArgs = [
            'name'   => 'Events',
            'item'   => 'Event',
            'domain' => 'test-domain',
        ];

        $this->args = [
            'description'         => 'A list of events',
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-calendar',
            'can_export'          => true,
            'has_archive'         => true,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'capability_type'     => 'post',
            'show_in_rest'        => true,
            'rest_base'           => 'events',
            'map_meta_cap'        => true,
        ];
    }

    private function make(
        array $supports    = [],
        array $taxonomies  = [],
        array|bool $rewrite = false
    ): \ColtmanRegisterPost {
        return new \ColtmanRegisterPost(
            $this->labelArgs,
            'test_event',
            $this->args,
            $supports,
            $taxonomies,
            $rewrite
        );
    }

    // ── Constructor: post_name ─────────────────────────────────────────────────

    public function test_constructor_stores_post_name(): void
    {
        $post = $this->make();
        $this->assertSame('test_event', $this->getProperty($post, 'post_name'));
    }

    // ── Constructor: labels ───────────────────────────────────────────────────

    public function test_constructor_builds_labels_array(): void
    {
        $labels = $this->getProperty($this->make(), 'labels');
        $this->assertIsArray($labels);
    }

    public function test_labels_contains_all_required_keys(): void
    {
        $labels = $this->getProperty($this->make(), 'labels');

        $required = [
            'name', 'singular_name', 'menu_name', 'name_admin_bar',
            'archives', 'attributes', 'parent_item_colon', 'all_items',
            'add_new_item', 'add_new', 'new_item', 'edit_item', 'update_item',
            'view_item', 'view_items', 'search_items', 'not_found',
            'not_found_in_trash', 'featured_image', 'set_featured_image',
            'remove_featured_image', 'use_featured_image', 'insert_into_item',
            'uploaded_to_this_item', 'items_list', 'items_list_navigation',
            'filter_items_list',
        ];

        foreach ($required as $key) {
            $this->assertArrayHasKey($key, $labels, "Missing label key: $key");
        }
    }

    public function test_labels_name_contains_plural_name(): void
    {
        $labels = $this->getProperty($this->make(), 'labels');
        $this->assertStringContainsString('Events', $labels['name']);
    }

    public function test_labels_name_admin_bar_contains_singular_name(): void
    {
        $labels = $this->getProperty($this->make(), 'labels');
        $this->assertStringContainsString('Event', $labels['name_admin_bar']);
    }

    public function test_labels_all_items_contains_plural_name(): void
    {
        $labels = $this->getProperty($this->make(), 'labels');
        $this->assertStringContainsString('Events', $labels['all_items']);
    }

    public function test_labels_add_new_item_contains_item_name(): void
    {
        $labels = $this->getProperty($this->make(), 'labels');
        $this->assertStringContainsString('Event', $labels['add_new_item']);
    }

    // ── Constructor: args ─────────────────────────────────────────────────────

    public function test_constructor_builds_args_array(): void
    {
        $args = $this->getProperty($this->make(), 'args');
        $this->assertIsArray($args);
    }

    public function test_args_label_equals_plural_name(): void
    {
        $args = $this->getProperty($this->make(), 'args');
        $this->assertSame('Events', $args['label']);
    }

    public function test_args_description_is_set(): void
    {
        $args = $this->getProperty($this->make(), 'args');
        $this->assertSame('A list of events', $args['description']);
    }

    public function test_args_public_is_set(): void
    {
        $args = $this->getProperty($this->make(), 'args');
        $this->assertTrue($args['public']);
    }

    public function test_args_rest_base_is_set(): void
    {
        $args = $this->getProperty($this->make(), 'args');
        $this->assertSame('events', $args['rest_base']);
    }

    public function test_args_labels_key_is_the_built_labels_array(): void
    {
        $post   = $this->make();
        $args   = $this->getProperty($post, 'args');
        $labels = $this->getProperty($post, 'labels');
        $this->assertSame($labels, $args['labels']);
    }

    public function test_args_supports_is_set(): void
    {
        $supports = ['title', 'editor', 'thumbnail'];
        $args     = $this->getProperty($this->make($supports), 'args');
        $this->assertSame($supports, $args['supports']);
    }

    public function test_args_taxonomies_is_set(): void
    {
        $taxonomies = ['category', 'post_tag'];
        $args       = $this->getProperty($this->make([], $taxonomies), 'args');
        $this->assertSame($taxonomies, $args['taxonomies']);
    }

    public function test_args_rewrite_bool_false(): void
    {
        $args = $this->getProperty($this->make(), 'args');
        $this->assertFalse($args['rewrite']);
    }

    public function test_args_rewrite_array_is_stored(): void
    {
        $rewrite = ['slug' => 'events', 'with_front' => false];
        $args    = $this->getProperty($this->make([], [], $rewrite), 'args');
        $this->assertSame($rewrite, $args['rewrite']);
    }

    // ── Hook registration ─────────────────────────────────────────────────────

    public function test_constructor_registers_init_hook(): void
    {
        $this->make();
        $hooks = array_column($this->spyCalls('add_action'), 'hook');
        $this->assertContains('init', $hooks);
    }

    public function test_init_hook_callback_is_register_new_post_type(): void
    {
        $post  = $this->make();
        $calls = $this->spyCalls('add_action');
        $init  = array_values(array_filter($calls, fn($c) => $c['hook'] === 'init'));
        $this->assertNotEmpty($init);
        $this->assertSame([$post, 'register_new_post_type'], $init[0]['callback']);
    }

    // ── register_new_post_type() ───────────────────────────────────────────────

    public function test_register_new_post_type_calls_register_post_type_once(): void
    {
        $post = $this->make();
        $post->register_new_post_type();

        $this->assertCount(1, $this->spyCalls('register_post_type'));
    }

    public function test_register_new_post_type_passes_correct_post_type_slug(): void
    {
        $post = $this->make();
        $post->register_new_post_type();

        $call = $this->firstCall('register_post_type');
        $this->assertSame('test_event', $call['post_type']);
    }

    public function test_register_new_post_type_passes_built_args(): void
    {
        $post = $this->make();
        $post->register_new_post_type();

        $call = $this->firstCall('register_post_type');
        $this->assertArrayHasKey('label',       $call['args']);
        $this->assertArrayHasKey('labels',      $call['args']);
        $this->assertArrayHasKey('public',      $call['args']);
        $this->assertArrayHasKey('description', $call['args']);
    }
}
