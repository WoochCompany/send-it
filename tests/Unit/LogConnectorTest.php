<?php

namespace Tests\Unit;

use App\Models\Message;
use App\Services\Message\LogConnector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogConnectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_constructor_accepts_config(): void
    {
        $config = ['level' => 'debug', 'channel' => 'custom'];
        $connector = new LogConnector($config);

        $this->assertEquals('debug', $connector->config('level'));
        $this->assertEquals('custom', $connector->config('channel'));
    }

    public function test_constructor_with_empty_config(): void
    {
        $connector = new LogConnector();

        $this->assertNull($connector->config('level'));
        $this->assertNull($connector->config('channel'));
    }

    public function test_config_method_returns_default_value(): void
    {
        $connector = new LogConnector(['timeout' => 30]);

        $this->assertEquals(30, $connector->config('timeout'));
        $this->assertEquals('default', $connector->config('nonexistent', 'default'));
        $this->assertNull($connector->config('nonexistent'));
    }

    public function test_config_method_with_nested_keys(): void
    {
        $config = [
            'log' => [
                'level' => 'info',
                'formatting' => ['timestamp' => true]
            ]
        ];
        $connector = new LogConnector($config);

        $this->assertEquals('info', $connector->config('log.level'));
        $this->assertTrue($connector->config('log.formatting.timestamp'));
        $this->assertNull($connector->config('log.nonexistent'));
    }

    public function test_test_method_returns_true(): void
    {
        $this->assertTrue(LogConnector::test([]));
        $this->assertTrue(LogConnector::test(['level' => 'info']));
        $this->assertTrue(LogConnector::test(['level' => 'debug', 'channel' => 'custom']));
    }

    public function test_send_executes_without_error(): void
    {
        $connector = new LogConnector();
        $message = Message::factory()->make([
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body Content'
        ]);

        // Test that the method executes without throwing an exception
        $connector->send($message);
        $this->assertTrue(true); // If we reach here, no exception was thrown
    }

    public function test_send_handles_null_fields(): void
    {
        $connector = new LogConnector();
        $message = Message::factory()->make([
            'to' => 'test@example.com',
            'subject' => null,
            'body' => null
        ]);

        // Test that the method executes without throwing an exception
        $connector->send($message);
        $this->assertTrue(true); // If we reach here, no exception was thrown
    }

    public function test_send_handles_empty_fields(): void
    {
        $connector = new LogConnector();
        $message = Message::factory()->make([
            'to' => 'test@example.com',
            'subject' => '',
            'body' => ''
        ]);

        // Test that the method executes without throwing an exception
        $connector->send($message);
        $this->assertTrue(true); // If we reach here, no exception was thrown
    }

    public function test_send_handles_complex_message_data(): void
    {
        $connector = new LogConnector();
        $message = Message::factory()->make([
            'to' => 'recipient@example.com',
            'subject' => 'Complex Subject with Special Characters: éàç!@#$%',
            'body' => "Multi-line\nBody\nWith\nSpecial\nCharacters: éàç!@#$%"
        ]);

        // Test that the method executes without throwing an exception
        $connector->send($message);
        $this->assertTrue(true); // If we reach here, no exception was thrown
    }
}
