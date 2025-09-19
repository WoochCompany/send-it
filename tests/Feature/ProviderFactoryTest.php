<?php

namespace Tests\Feature;

use App\Exceptions\UnknownConnectorNameException;
use App\Models\MessageProvider;
use App\Services\Message\ConnectorInterface;
use App\Services\Message\LogConnector;
use App\Services\Message\ProviderFactory;
use App\Services\Message\SMTPConnector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_throws_exception_when_null(): void
    {
        $factory = app(ProviderFactory::class);

        $this->expectException(UnknownConnectorNameException::class);
        $this->expectExceptionMessage('Unknown connector name: null');

        $factory->create(null);
    }

    public function test_create_resolves_alias_smtp(): void
    {
        $provider = MessageProvider::create([
            'slug' => 'smtp-test',
            'name' => 'SMTP Test',
            'provider' => 'smtp',
            'config' => ['host' => 'smtp.example.com', 'port' => 587],
        ]);

        $factory = app(ProviderFactory::class);

        $connector = $factory->create($provider);

        $this->assertInstanceOf(SMTPConnector::class, $connector);
        $this->assertEquals('smtp.example.com', $connector->config('host'));
        $this->assertEquals(587, $connector->config('port'));
        $this->assertNull($connector->config('nonexistent'));
    }

    public function test_create_throws_exception_with_unknown_provider(): void
    {
        $provider = MessageProvider::create([
            'slug' => 'unknown-test',
            'name' => 'Unknown Test',
            'config' => null,
            'provider' => 'Non\Existing\Class',
        ]);

        $factory = app(ProviderFactory::class);

        $this->expectException(UnknownConnectorNameException::class);
        $this->expectExceptionMessage('Unknown connector name: Non\Existing\Class');

        $factory->create($provider);
    }

    public function test_connector_config_method_with_default_value(): void
    {
        $provider = MessageProvider::create([
            'slug' => 'smtp-test',
            'name' => 'SMTP Test',
            'provider' => 'smtp',
            'config' => ['timeout' => 30],
        ]);

        $factory = app(ProviderFactory::class);
        $connector = $factory->create($provider);

        $this->assertEquals(30, $connector->config('timeout'));
        $this->assertEquals('default_value', $connector->config('missing_key', 'default_value'));
    }

}
