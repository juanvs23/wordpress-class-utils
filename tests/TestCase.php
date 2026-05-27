<?php
namespace Coltman\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base test case for all Coltman Framework unit tests.
 *
 * Provides:
 *  - Automatic reset of the spy/stub/flag registry between tests.
 *  - Helper methods for inspecting private/protected members.
 *  - Helper methods for capturing echoed output.
 */
abstract class TestCase extends PHPUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset global spy, stubs and flags before every test.
        $GLOBALS['_coltman_spy']   = [];
        $GLOBALS['_coltman_stubs'] = [];
        $GLOBALS['_coltman_flags'] = [
            'is_admin'         => false,
            'metadata_exists'  => false,
            'current_user_can' => true,
        ];

        // Clean superglobals that some tests manipulate.
        $_POST = [];
        $_GET  = [];
    }

    // ── Spy helpers ───────────────────────────────────────────────────────────

    /** Returns every recorded call to $fn as an array of arg-arrays. */
    protected function spyCalls(string $fn): array
    {
        return $GLOBALS['_coltman_spy'][$fn] ?? [];
    }

    /** Returns the first recorded call to $fn, or null if never called. */
    protected function firstCall(string $fn): ?array
    {
        return $this->spyCalls($fn)[0] ?? null;
    }

    /** Assert that $fn was called at least once. */
    protected function assertCalled(string $fn, string $msg = ''): void
    {
        $this->assertNotEmpty($this->spyCalls($fn), $msg ?: "$fn was not called");
    }

    /** Assert that $fn was never called. */
    protected function assertNotCalled(string $fn, string $msg = ''): void
    {
        $this->assertEmpty($this->spyCalls($fn), $msg ?: "$fn was unexpectedly called");
    }

    // ── Stub / flag helpers ───────────────────────────────────────────────────

    /** Force a WP stub function to return $value. */
    protected function setStub(string $fn, $value): void
    {
        $GLOBALS['_coltman_stubs'][$fn] = $value;
    }

    /** Toggle a context flag (e.g. 'is_admin', 'metadata_exists'). */
    protected function setFlag(string $flag, $value): void
    {
        $GLOBALS['_coltman_flags'][$flag] = $value;
    }

    // ── Reflection helpers ────────────────────────────────────────────────────

    /** Read a private/protected property from an object. */
    protected function getProperty(object $obj, string $prop): mixed
    {
        $ref = new \ReflectionProperty($obj, $prop);
        return $ref->getValue($obj);
    }

    /** Invoke a private/protected method on an object. */
    protected function callMethod(object $obj, string $method, array $args = []): mixed
    {
        $ref = new \ReflectionMethod($obj, $method);
        return $ref->invoke($obj, ...$args);
    }

    // ── Output capture helpers ────────────────────────────────────────────────

    /** Capture the echoed output of a callable and return it as a string. */
    protected function capture(callable $fn): string
    {
        ob_start();
        $fn();
        return ob_get_clean();
    }
}
