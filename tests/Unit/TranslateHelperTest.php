<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * RI.2 regression coverage.
 *
 * The contract under test: translate() renders the same fallback whether or not
 * persistence is enabled, and only writes to the tracked locale file when
 * config('translation.persist_missing_keys') is true.
 */
class TranslateHelperTest extends TestCase
{
    private string $langFile;
    private string $backup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->langFile = base_path('resources/lang/en/messages.php');
        $this->backup = file_get_contents($this->langFile);
        app()->setLocale('en');
    }

    protected function tearDown(): void
    {
        // Restore byte-for-byte: these tests must never leave the tracked file altered.
        file_put_contents($this->langFile, $this->backup);

        parent::tearDown();
    }

    private function missingKey(): string
    {
        return 'ri2_probe_' . bin2hex(random_bytes(6));
    }

    public function test_default_config_does_not_persist(): void
    {
        $this->assertFalse(
            config('translation.persist_missing_keys'),
            'persist_missing_keys must default to false so production never writes.'
        );
    }

    public function test_existing_key_returns_translation_and_does_not_write(): void
    {
        config(['translation.persist_missing_keys' => false]);
        $before = md5_file($this->langFile);

        $this->assertSame('Service Unavailable', translate('Service Unavailable'));
        $this->assertSame($before, md5_file($this->langFile));
    }

    public function test_missing_key_with_persistence_off_returns_fallback_and_leaves_file_byte_identical(): void
    {
        config(['translation.persist_missing_keys' => false]);

        $key = $this->missingKey();
        $bytesBefore = file_get_contents($this->langFile);

        $result = translate($key);

        // Same humanised fallback the old implementation produced.
        $this->assertSame(ucfirst(str_replace('_', ' ', $key)), $result);
        $this->assertSame($bytesBefore, file_get_contents($this->langFile), 'File must be untouched.');

        $reloaded = include $this->langFile;
        $this->assertArrayNotHasKey($key, $reloaded);
    }

    public function test_missing_key_with_persistence_on_writes_key_and_preserves_existing(): void
    {
        config(['translation.persist_missing_keys' => true]);

        $key = $this->missingKey();
        $countBefore = count(include $this->langFile);

        $result = translate($key);
        $reloaded = include $this->langFile;

        $this->assertSame(ucfirst(str_replace('_', ' ', $key)), $result);
        $this->assertArrayHasKey($key, $reloaded);
        $this->assertCount($countBefore + 1, $reloaded);

        // Pre-existing keys survive the rewrite.
        $this->assertArrayHasKey('Service Unavailable', $reloaded);
        $this->assertArrayHasKey('Go Home', $reloaded);
    }

    public function test_fallback_string_is_identical_whether_or_not_persistence_is_enabled(): void
    {
        $key = $this->missingKey();

        config(['translation.persist_missing_keys' => false]);
        $off = translate($key);

        config(['translation.persist_missing_keys' => true]);
        $on = translate($key . '_b');

        $this->assertSame(ucfirst(str_replace('_', ' ', $key)), $off);
        $this->assertSame(ucfirst(str_replace('_', ' ', $key . '_b')), $on);
    }

    public function test_prefixed_keys_still_short_circuit_to_trans(): void
    {
        config(['translation.persist_missing_keys' => false]);
        $bytesBefore = file_get_contents($this->langFile);

        foreach (['validation.', 'passwords.', 'pagination.', 'order_texts.'] as $prefix) {
            $key = $prefix . 'ri2_probe_missing';
            $this->assertSame(trans($key), translate($key));
        }

        $this->assertSame($bytesBefore, file_get_contents($this->langFile));
    }

    public function test_three_restored_static_keys_are_present(): void
    {
        $data = include $this->langFile;

        foreach (['Service Unavailable', 'Go Home', 'Oh no'] as $key) {
            $this->assertArrayHasKey($key, $data);
        }
    }

    public function test_503_view_does_not_create_a_key_from_an_exception_message(): void
    {
        config(['translation.persist_missing_keys' => false]);

        $message = 'ri2 arbitrary exception ' . bin2hex(random_bytes(4));
        $bytesBefore = file_get_contents($this->langFile);

        // The errors:: hint is registered by the exception handler, not during a
        // unit-test boot, so point it at the app's views plus the framework layouts.
        view()->addNamespace('errors', [
            resource_path('views/errors'),
            base_path('vendor/laravel/framework/src/Illuminate/Foundation/Exceptions/views'),
        ]);

        $rendered = view('errors::503', [
            'exception' => new \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException(null, $message),
        ])->render();

        $this->assertStringContainsString($message, $rendered);
        $this->assertSame($bytesBefore, file_get_contents($this->langFile));

        $reloaded = include $this->langFile;
        $this->assertArrayNotHasKey($message, $reloaded);
    }

    public function test_persist_language_array_is_atomic_and_leaves_no_temp_file(): void
    {
        $dir = dirname($this->langFile);
        $before = glob($dir . '/*.tmp');

        $this->assertTrue(persist_language_array($this->langFile, include $this->langFile));
        $this->assertSame($before, glob($dir . '/*.tmp'), 'No temp file may be left behind.');
    }
}
