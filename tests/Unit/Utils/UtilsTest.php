<?php
namespace Coltman\Tests\Unit\Utils;

use Coltman\Tests\TestCase;

/**
 * Tests for utility functions in utils/utils.php.
 *
 * Covers: coltman_trim_content_text_fn(), formaturltext().
 */
class UtilsTest extends TestCase
{
    // ── coltman_trim_content_text_fn() ────────────────────────────────────────

    public function test_trim_delegates_to_wp_trim_words(): void
    {
        $text   = 'one two three four five six seven eight nine ten eleven twelve thirteen fourteen fifteen sixteen';
        $result = coltman_trim_content_text_fn($text, 3, '...');

        $this->assertSame('one two three...', $result);
    }

    public function test_trim_returns_full_text_when_shorter_than_limit(): void
    {
        $text   = 'short text';
        $result = coltman_trim_content_text_fn($text, 10, '...');

        $this->assertStringContainsString('short text', $result);
    }

    public function test_trim_default_limit_is_fifteen_words(): void
    {
        // 16 words → should trim
        $words  = array_fill(0, 16, 'word');
        $text   = implode(' ', $words);
        $result = coltman_trim_content_text_fn($text);

        // default is 15 words + default ellipsis
        $this->assertStringNotContainsString('word word word word word word word word word word word word word word word word', $result);
    }

    public function test_trim_default_ellipsis_is_triple_dot(): void
    {
        $words  = array_fill(0, 20, 'word');
        $result = coltman_trim_content_text_fn(implode(' ', $words), 5);

        $this->assertStringEndsWith('...', $result);
    }

    public function test_trim_custom_ellipsis(): void
    {
        $words  = array_fill(0, 10, 'word');
        $result = coltman_trim_content_text_fn(implode(' ', $words), 3, ' [more]');

        $this->assertStringEndsWith('[more]', $result);
    }

    // ── formaturltext() ───────────────────────────────────────────────────────

    public function test_formaturltext_replaces_spaces_with_plus(): void
    {
        $result = formaturltext('hello world');
        $this->assertSame('hello+world', $result);
    }

    public function test_formaturltext_removes_accents(): void
    {
        $result = formaturltext('café');
        $this->assertStringNotContainsString('é', $result);
        $this->assertStringContainsString('caf', $result);
    }

    public function test_formaturltext_removes_special_characters(): void
    {
        $result = formaturltext('hello! world?');
        $this->assertStringNotContainsString('!', $result);
        $this->assertStringNotContainsString('?', $result);
    }

    public function test_formaturltext_keeps_alphanumerics(): void
    {
        $result = formaturltext('abc 123');
        $this->assertStringContainsString('abc', $result);
        $this->assertStringContainsString('123', $result);
    }

    public function test_formaturltext_handles_multiple_spaces(): void
    {
        $result = formaturltext('hello   world');
        // Multiple spaces become multiple plus signs which are then collapsed
        $this->assertStringNotContainsString('   ', $result);
        $this->assertStringContainsString('hello', $result);
        $this->assertStringContainsString('world', $result);
    }

    public function test_formaturltext_handles_empty_string(): void
    {
        $result = formaturltext('');
        $this->assertSame('', $result);
    }

    public function test_formaturltext_handles_numeric_string(): void
    {
        $result = formaturltext('12345');
        $this->assertSame('12345', $result);
    }

    public function test_formaturltext_lowercasing_not_applied(): void
    {
        // formaturltext does NOT lowercase — it only handles accents, spaces, specials
        $result = formaturltext('Hello World');
        $this->assertStringContainsString('Hello', $result);
    }
}
