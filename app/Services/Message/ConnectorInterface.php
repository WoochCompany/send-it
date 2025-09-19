<?php

namespace App\Services\Message;

use App\Models\Message;

abstract class ConnectorInterface
{
    public function __construct(protected array $config = [])
    {
    }

    /**
     * Get a configuration value by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function config(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Test if the configuration is valid and the connector can be used.
     *
     * @param array $config
     * @return bool
     */
    abstract public static function test(array $config): bool;

    /**
     * Send a message using connector specific implementation.
     *
     * @param Message $message
     * @return void
     */
    abstract public function send(Message $message): void;
}
