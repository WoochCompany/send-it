<?php

use App\Models\Configuration;
use Illuminate\Support\Facades\Cache;

/**
 * Get or set a configuration value stored in the database.
 *
 * Usage:
 * - dbConfig('key') => returns the value or null
 * - dbConfig('key', $value) => stores the value and returns it
 *
 * Works similarly to Laravel's cache() helper for simple get/set semantics.
 * Values are stored as JSON in the `configurations` table and returned with their original types.
 *
 * @param string $key
 * @param mixed $value
 * @return mixed
 */
function dbConfig(string $key, $value = null)
{
    $cacheKey = 'db_config:' . $key;

    if (func_num_args() === 1) {
        // GET
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $row = Configuration::where('key', $key)->first();

        if (! $row) {
            return null;
        }

        $val = $row->value;
        Cache::forever($cacheKey, $val);

        return $val;
    }

    // SET
    $store = $value;

    Configuration::updateOrCreate(
        ['key' => $key],
        ['value' => $store]
    );

    Cache::forever($cacheKey, $store);

    return $store;
}

