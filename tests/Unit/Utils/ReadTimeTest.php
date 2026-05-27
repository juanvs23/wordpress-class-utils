<?php
namespace Coltman\Tests\Unit\Utils;

use Coltman\Tests\TestCase;

/**
 * Tests for get_estimated_reading_time() in utils/read-time.php.
 *
 * Covers: no content, single minute, plural minutes, custom WPM,
 * custom suffixes, empty/null post, minimum of 1 minute.
 */
class ReadTimeTest extends TestCase
{
    // ── Helper ────────────────────────────────────────────────────────────────

    private function makePost(string $content): object
    {
        return (object)['ID' => 1, 'post_content' => $content];
    }

    // ── No content / no post ──────────────────────────────────────────────────

    public function test_returns_one_minute_when_post_is_null(): void
    {
        $this->setStub('get_post', null);

        $result = get_estimated_reading_time(['post' => null]);

        $this->assertSame(1, $result->time);
    }

    public function test_returns_single_suffix_when_post_is_null(): void
    {
        $this->setStub('get_post', null);

        $result = get_estimated_reading_time(['post' => null, 'single_suffix' => 'minuto']);

        $this->assertSame('minuto', $result->suffix);
    }

    public function test_returns_one_minute_for_empty_post_content(): void
    {
        $this->setStub('get_post', (object)['ID' => 1, 'post_content' => '']);

        $result = get_estimated_reading_time(['post' => 1]);

        $this->assertSame(1, $result->time);
    }

    // ── Calculation ───────────────────────────────────────────────────────────

    public function test_calculates_reading_time_from_word_count(): void
    {
        // 200 words at 200 wpm = 1 minute
        $content = implode(' ', array_fill(0, 200, 'word'));
        $this->setStub('get_post', $this->makePost($content));

        $result = get_estimated_reading_time(['post' => 1]);

        $this->assertSame(1, $result->time);
    }

    public function test_rounds_up_to_nearest_minute(): void
    {
        // 201 words at 200 wpm = ceil(1.005) = 2 minutes
        $content = implode(' ', array_fill(0, 201, 'word'));
        $this->setStub('get_post', $this->makePost($content));

        $result = get_estimated_reading_time(['post' => 1]);

        $this->assertSame(2, $result->time);
    }

    public function test_minimum_reading_time_is_one_minute(): void
    {
        // Very short content — 10 words at 200 wpm = ceil(0.05) = 1
        $content = implode(' ', array_fill(0, 10, 'word'));
        $this->setStub('get_post', $this->makePost($content));

        $result = get_estimated_reading_time(['post' => 1]);

        $this->assertGreaterThanOrEqual(1, $result->time);
    }

    public function test_respects_custom_wpm(): void
    {
        // 100 words at 50 wpm = ceil(2) = 2 minutes
        $content = implode(' ', array_fill(0, 100, 'word'));
        $this->setStub('get_post', $this->makePost($content));

        $result = get_estimated_reading_time(['post' => 1, 'wpm' => 50]);

        $this->assertSame(2, $result->time);
    }

    public function test_default_wpm_is_200(): void
    {
        // 400 words at default 200 wpm = 2 minutes
        $content = implode(' ', array_fill(0, 400, 'word'));
        $this->setStub('get_post', $this->makePost($content));

        $result = get_estimated_reading_time(['post' => 1]);

        $this->assertSame(2, $result->time);
    }

    // ── Suffix selection ──────────────────────────────────────────────────────

    public function test_uses_singular_suffix_for_one_minute(): void
    {
        $content = implode(' ', array_fill(0, 200, 'word'));
        $this->setStub('get_post', $this->makePost($content));

        $result = get_estimated_reading_time(['post' => 1]);

        $this->assertSame('min', $result->suffix);
    }

    public function test_uses_plural_suffix_for_more_than_one_minute(): void
    {
        $content = implode(' ', array_fill(0, 600, 'word'));
        $this->setStub('get_post', $this->makePost($content));

        $result = get_estimated_reading_time(['post' => 1]);

        $this->assertSame('mins', $result->suffix);
    }

    public function test_custom_single_suffix(): void
    {
        $content = implode(' ', array_fill(0, 200, 'word'));
        $this->setStub('get_post', $this->makePost($content));

        $result = get_estimated_reading_time([
            'post'          => 1,
            'single_suffix' => 'minuto',
            'plural_suffix' => 'minutos',
        ]);

        $this->assertSame('minuto', $result->suffix);
    }

    public function test_custom_plural_suffix(): void
    {
        $content = implode(' ', array_fill(0, 600, 'word'));
        $this->setStub('get_post', $this->makePost($content));

        $result = get_estimated_reading_time([
            'post'          => 1,
            'single_suffix' => 'minuto',
            'plural_suffix' => 'minutos',
        ]);

        $this->assertSame('minutos', $result->suffix);
    }

    // ── Return object structure ───────────────────────────────────────────────

    public function test_returns_object_with_time_property(): void
    {
        $this->setStub('get_post', null);
        $result = get_estimated_reading_time(['post' => null]);
        $this->assertObjectHasProperty('time', $result);
    }

    public function test_returns_object_with_suffix_property(): void
    {
        $this->setStub('get_post', null);
        $result = get_estimated_reading_time(['post' => null]);
        $this->assertObjectHasProperty('suffix', $result);
    }

    public function test_time_is_integer(): void
    {
        $content = implode(' ', array_fill(0, 200, 'word'));
        $this->setStub('get_post', $this->makePost($content));

        $result = get_estimated_reading_time(['post' => 1]);

        $this->assertIsInt($result->time);
    }
}
