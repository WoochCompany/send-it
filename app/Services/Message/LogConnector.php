<?php

namespace App\Services\Message;

use App\Models\Message;
use Illuminate\Support\Facades\Log;

class LogConnector extends ConnectorInterface
{
    /**
     * Test if the Log configuration is valid.
     *
     * @param array $config
     * @return bool
     */
    public static function test(array $config): bool
    {
        // Log connector is always available as it uses Laravel's logging system
        return true;
    }

    /**
     * Send a message to the logs.
     *
     * @param Message $message
     */
    public function send(Message $message): void
    {
        Log::info('LogConnector sending message', [
            'to' => $message->recipient,
            'subject' => $message->subject,
            'body' => $message->body,
        ]);
    }
}
