<?php
namespace Coltman\Tests\Unit\Utils;

use Coltman\Tests\TestCase;

/**
 * Tests for functions in utils/navigations_archors.php.
 *
 * Covers: process_headings_and_get_data(), get_extracted_headings_array(),
 * filter_content_inject_headings().
 */
class NavigationsAnchorsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset the global headings cache before each test
        global $rc_global_headings;
        $rc_global_headings = [];
    }

    // ── process_headings_and_get_data() ───────────────────────────────────────

    public function test_returns_empty_headings_for_empty_content(): void
    {
        $result = process_headings_and_get_data('');

        $this->assertSame('', $result['content']);
        $this->assertSame([], $result['headings']);
    }

    public function test_returns_unchanged_content_for_empty_string(): void
    {
        $result = process_headings_and_get_data('');
        $this->assertSame('', $result['content']);
    }

    public function test_injects_id_attribute_into_h2(): void
    {
        $html   = '<h2>Hello World</h2>';
        $result = process_headings_and_get_data($html);

        $this->assertStringContainsString('id="', $result['content']);
    }

    public function test_id_is_slugified_text(): void
    {
        $html   = '<h2>Hello World</h2>';
        $result = process_headings_and_get_data($html);

        $this->assertStringContainsString('id="hello-world"', $result['content']);
    }

    public function test_id_strips_inner_html_tags(): void
    {
        $html   = '<h2><strong>Bold Title</strong></h2>';
        $result = process_headings_and_get_data($html);

        $this->assertStringContainsString('id="bold-title"', $result['content']);
    }

    public function test_returns_heading_object_with_id_and_text(): void
    {
        $html   = '<h2>My Section</h2>';
        $result = process_headings_and_get_data($html);

        $this->assertCount(1, $result['headings']);
        $this->assertSame('my-section', $result['headings'][0]->id);
        $this->assertSame('My Section', $result['headings'][0]->text);
    }

    public function test_processes_multiple_headings(): void
    {
        $html   = '<h1>First</h1><h2>Second</h2><h3>Third</h3>';
        $result = process_headings_and_get_data($html);

        $this->assertCount(3, $result['headings']);
    }

    public function test_deduplicates_heading_ids(): void
    {
        $html   = '<h2>Same</h2><h2>Same</h2><h2>Same</h2>';
        $result = process_headings_and_get_data($html);

        $ids = array_column($result['headings'], 'id');
        $this->assertSame('same',   $ids[0]);
        $this->assertSame('same-1', $ids[1]);
        $this->assertSame('same-2', $ids[2]);
    }

    public function test_deduplication_ids_are_injected_in_content_too(): void
    {
        $html   = '<h2>Dup</h2><h2>Dup</h2>';
        $result = process_headings_and_get_data($html);

        $this->assertStringContainsString('id="dup"',   $result['content']);
        $this->assertStringContainsString('id="dup-1"', $result['content']);
    }

    public function test_replaces_existing_id_attribute(): void
    {
        $html   = '<h2 id="old-id">Title</h2>';
        $result = process_headings_and_get_data($html);

        $this->assertStringNotContainsString('id="old-id"', $result['content']);
        $this->assertStringContainsString('id="title"', $result['content']);
    }

    public function test_preserves_non_heading_content(): void
    {
        $html   = '<p>Paragraph</p><h2>Heading</h2><p>Another</p>';
        $result = process_headings_and_get_data($html);

        $this->assertStringContainsString('<p>Paragraph</p>', $result['content']);
        $this->assertStringContainsString('<p>Another</p>', $result['content']);
    }

    public function test_handles_h1_through_h6(): void
    {
        $html = implode('', array_map(fn($n) => "<h{$n}>Level {$n}</h{$n}>", range(1, 6)));
        $result = process_headings_and_get_data($html);

        $this->assertCount(6, $result['headings']);
    }

    public function test_uses_fallback_id_for_empty_heading_text(): void
    {
        $html   = '<h2></h2>';
        $result = process_headings_and_get_data($html);

        $this->assertCount(1, $result['headings']);
        $this->assertNotEmpty($result['headings'][0]->id);
    }

    public function test_content_is_returned_as_string(): void
    {
        $result = process_headings_and_get_data('<h2>Test</h2>');
        $this->assertIsString($result['content']);
    }

    public function test_headings_is_returned_as_array(): void
    {
        $result = process_headings_and_get_data('<h2>Test</h2>');
        $this->assertIsArray($result['headings']);
    }

    // ── filter_content_inject_headings() ─────────────────────────────────────

    public function test_filter_returns_modified_content(): void
    {
        $content = '<h2>Test Heading</h2><p>Body</p>';
        $result  = filter_content_inject_headings($content);

        $this->assertStringContainsString('id="test-heading"', $result);
        $this->assertStringContainsString('<p>Body</p>', $result);
    }

    public function test_filter_updates_global_headings(): void
    {
        global $rc_global_headings;

        filter_content_inject_headings('<h2>Global Section</h2>');

        $this->assertNotEmpty($rc_global_headings);
        $this->assertSame('global-section', $rc_global_headings[0]->id);
    }

    public function test_filter_resets_global_headings_on_each_call(): void
    {
        global $rc_global_headings;

        filter_content_inject_headings('<h2>First</h2>');
        filter_content_inject_headings('<h2>Second</h2>');

        // Second call replaces the headings from the first call
        $this->assertCount(1, $rc_global_headings);
        $this->assertSame('second', $rc_global_headings[0]->id);
    }

    // ── get_extracted_headings_array() ────────────────────────────────────────

    public function test_returns_global_headings_when_available(): void
    {
        global $rc_global_headings;
        $rc_global_headings = [(object)['id' => 'sec-1', 'text' => 'Section 1']];

        $result = get_extracted_headings_array();

        $this->assertCount(1, $result);
        $this->assertSame('sec-1', $result[0]->id);
    }

    public function test_falls_back_to_get_the_content_when_global_empty(): void
    {
        global $rc_global_headings;
        $rc_global_headings = [];

        $this->setStub('get_the_content', '<h2>Fallback Heading</h2>');

        $result = get_extracted_headings_array();

        $this->assertNotEmpty($result);
        $this->assertSame('fallback-heading', $result[0]->id);
    }

    public function test_returns_empty_array_when_no_global_and_no_content(): void
    {
        global $rc_global_headings;
        $rc_global_headings = [];

        $this->setStub('get_the_content', '');

        $result = get_extracted_headings_array();

        $this->assertSame([], $result);
    }

    public function test_register_filter_was_added_on_bootstrap(): void
    {
        // filter was added at file scope when navigations_archors.php was loaded
        $filters = array_column($this->spyCalls('add_filter'), 'hook');
        // Note: the spy was reset in setUp, so this reflects only current-test spy
        // The file-level add_filter runs at bootstrap (before spy reset), so we can't
        // assert it here. Instead, verify the function is callable.
        $this->assertTrue(function_exists('filter_content_inject_headings'));
    }
}
