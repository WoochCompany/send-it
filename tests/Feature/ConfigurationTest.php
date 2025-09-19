<?php

namespace Tests\Feature;

use App\Models\Configuration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dbConfig_get_and_set_and_cache_behaviour(): void
    {
        $key = 'site.name';
        $value = ['name' => 'SendIt'];

        // SET
        $returned = dbConfig($key, $value);
        $this->assertEquals($value, $returned);

        // DB row exists
        $this->assertDatabaseHas('configurations', ['key' => $key]);

        $model = Configuration::where('key', $key)->first();
        $this->assertNotNull($model);
        $this->assertEquals($value, $model->value);

        // GET should return the same value (from cache or db)
        $got = dbConfig($key);
        $this->assertEquals($value, $got);

        // Modify DB directly to ensure cached value is returned by dbConfig()
        DB::table('configurations')->where('key', $key)->update(['value' => json_encode(['name' => 'Changed'])]);

        $gotAfterDbChange = dbConfig($key);
        $this->assertEquals($value, $gotAfterDbChange, 'dbConfig should return cached value even if DB row changed');
    }
}

