<?php

namespace App\Console\Commands;

use App\Models\Configuration;
use Illuminate\Console\Command;

class DbConfigCommand extends Command
{
    protected $signature = 'db:config {action : get|set|forget|list} {key?} {value?} {--json : Return output as JSON} {--time : Include created_at and updated_at in output}';

    protected $description = 'Manage database-backed configuration (get|set|forget|list).';

    public function handle(): int
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'get':
                return $this->handleGet();
            case 'set':
                return $this->handleSet();
            case 'forget':
                return $this->handleForget();
            case 'list':
                return $this->handleList();
            default:
                $this->error("Unknown action: {$action}");
                return self::FAILURE;
        }
    }

    protected function sendJson($data): void
    {
        $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function isJsonRequested(): bool
    {
        return (bool) $this->option('json');
    }

    protected function isTimeRequested(): bool
    {
        return (bool) $this->option('time');
    }

    protected function handleGet(): int
    {
        $key = $this->argument('key');

        if (! $key) {
            $this->error('Key is required for get.');
            return self::INVALID;
        }

        $row = Configuration::where('key', $key)->first();

        if (! $row) {
            if ($this->isJsonRequested()) {
                $payload = ['key' => $key, 'value' => null];
                if ($this->isTimeRequested()) {
                    $payload['created_at'] = null;
                    $payload['updated_at'] = null;
                }
                $this->sendJson($payload);
            } else {
                $this->line('null');
            }

            return self::SUCCESS;
        }

        if ($this->isJsonRequested()) {
            $payload = ['key' => $key, 'value' => $row->value];
            if ($this->isTimeRequested()) {
                $payload['created_at'] = $row->created_at?->toDateTimeString();
                $payload['updated_at'] = $row->updated_at?->toDateTimeString();
            }
            $this->sendJson($payload);
        } else {
            $this->line(json_encode($row->value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return self::SUCCESS;
    }

    protected function handleSet(): int
    {
        $key = $this->argument('key');
        $value = $this->argument('value');

        if (! $key || $value === null) {
            $this->error('Key and value are required for set.');
            return self::INVALID;
        }

        // Attempt to decode JSON value to allow structured values, otherwise store as string
        $decoded = json_decode($value, true);
        $store = $decoded === null && json_last_error() !== JSON_ERROR_NONE ? $value : $decoded;

        $config = Configuration::updateOrCreate(['key' => $key], ['value' => $store]);

        // Update cache via model saved listener in AppServiceProvider

        if ($this->isJsonRequested()) {
            $payload = ['key' => $key, 'value' => $config->value, 'status' => 'ok'];
            if ($this->isTimeRequested()) {
                $payload['created_at'] = $config->created_at?->toDateTimeString();
                $payload['updated_at'] = $config->updated_at?->toDateTimeString();
            }
            $this->sendJson($payload);
        } else {
            $this->info('OK');
        }

        return self::SUCCESS;
    }

    protected function handleForget(): int
    {
        $key = $this->argument('key');

        if (! $key) {
            $this->error('Key is required for forget.');
            return self::INVALID;
        }

        $deleted = Configuration::where('key', $key)->delete();

        if ($this->isJsonRequested()) {
            $this->sendJson(['key' => $key, 'deleted' => (bool) $deleted]);
        } else {
            $this->info($deleted ? 'Deleted' : 'Not found');
        }

        return self::SUCCESS;
    }

    protected function handleList(): int
    {
        $rows = Configuration::orderBy('key')->get(['key', 'value', 'created_at', 'updated_at']);

        if ($rows->isEmpty()) {
            if ($this->isJsonRequested()) {
                $this->sendJson([]);
            } else {
                $this->line('No configurations.');
            }

            return self::SUCCESS;
        }

        if ($this->isJsonRequested()) {
            $payload = $rows->map(function ($r) {
                $item = ['key' => $r->key, 'value' => $r->value];
                if ($this->isTimeRequested()) {
                    $item['created_at'] = $r->created_at?->toDateTimeString();
                    $item['updated_at'] = $r->updated_at?->toDateTimeString();
                }
                return $item;
            })->values()->toArray();

            $this->sendJson($payload);

            return self::SUCCESS;
        }

        // Table output: include time columns if requested
        $headers = ['Key', 'Value'];
        if ($this->isTimeRequested()) {
            $headers[] = 'Created At';
            $headers[] = 'Updated At';
        }

        $rowsForTable = $rows->map(function ($r) {
            $row = [$r->key, is_array($r->value) ? json_encode($r->value, JSON_UNESCAPED_UNICODE) : (string) $r->value];
            if ($this->isTimeRequested()) {
                $row[] = $r->created_at?->toDateTimeString();
                $row[] = $r->updated_at?->toDateTimeString();
            }
            return $row;
        })->toArray();

        $this->table($headers, $rowsForTable);

        return self::SUCCESS;
    }
}

