<?php

namespace Tests\Feature;

use App\Models\Configuration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DbConfigCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dbconfig_cli_set_get_forget(): void
    {
        $key = 'cli.test.key';
        $value = ['hello' => 'world'];

        // SET command (text)
        $this->artisan('db:config', ['action' => 'set', 'key' => $key, 'value' => json_encode($value)])
            ->expectsOutput('OK')
            ->assertExitCode(0);

        $this->assertDatabaseHas('configurations', ['key' => $key]);

        $model = Configuration::where('key', $key)->first();
        $this->assertNotNull($model);
        $this->assertEquals($value, $model->value);

        // GET command (text)
        $this->artisan('db:config', ['action' => 'get', 'key' => $key])
            ->expectsOutput(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->assertExitCode(0);

        // FORGET command (text)
        $this->artisan('db:config', ['action' => 'forget', 'key' => $key])
            ->expectsOutput('Deleted')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('configurations', ['key' => $key]);

        // Re-create for JSON tests
        Configuration::create(['key' => $key, 'value' => $value]);

        // SET with --json
        $this->artisan('db:config', ['action' => 'set', 'key' => $key, 'value' => json_encode($value), '--json' => true])
            ->expectsOutput(json_encode(['key' => $key, 'value' => $value, 'status' => 'ok'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->assertExitCode(0);

        // GET with --json
        $this->artisan('db:config', ['action' => 'get', 'key' => $key, '--json' => true])
            ->expectsOutput(json_encode(['key' => $key, 'value' => $value], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->assertExitCode(0);

        // FORGET with --json
        $this->artisan('db:config', ['action' => 'forget', 'key' => $key, '--json' => true])
            ->expectsOutput(json_encode(['key' => $key, 'deleted' => true], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->assertExitCode(0);
    }

    public function test_dbconfig_cli_list(): void
    {
        Configuration::create(['key' => 'a.key', 'value' => ['v' => '1']]);
        Configuration::create(['key' => 'b.key', 'value' => 'plain']);

        $this->artisan('db:config', ['action' => 'list'])
            ->assertExitCode(0);

        // basic DB assertions
        $this->assertDatabaseHas('configurations', ['key' => 'a.key']);
        $this->assertDatabaseHas('configurations', ['key' => 'b.key']);

        // list with --json
        $this->artisan('db:config', ['action' => 'list', '--json' => true])
            ->assertExitCode(0);
    }
}
