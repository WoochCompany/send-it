<?php

namespace App\Services\Message;

use App\Models\Message;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

class SMTPConnector extends ConnectorInterface
{
    /**
     * Generate a DSN string from configuration array.
     *
     * @param array $config
     * @return string
     */
    protected static function buildDsn(array $config): string
    {
        $host = $config['host'];
        $port = $config['port'] ?? 587;
        $username = $config['username'];
        $password = $config['password'];
        $encryption = $config['encryption'] ?? 'tls';

        // Build DSN based on encryption type
        if ($encryption === 'ssl') {
            $scheme = 'smtps';
        } else {
            $scheme = 'smtp';
        }

        // URL encode credentials to handle special characters
        $encodedUsername = urlencode($username);
        $encodedPassword = urlencode($password);

        $dsn = "{$scheme}://{$encodedUsername}:{$encodedPassword}@{$host}:{$port}";

        // Add encryption parameter for TLS
        if ($encryption === 'tls') {
            $dsn .= '?encryption=tls';
        }

        return $dsn;
    }

    /**
     * Test if the SMTP configuration is valid.
     *
     * @param array $config
     * @return bool
     */
    public static function test(array $config): bool
    {
        // Validate required SMTP configuration
        $requiredFields = ['host', 'port', 'username', 'password'];

        foreach ($requiredFields as $field) {
            if (empty($config[$field])) {
                return false;
            }
        }

        try {
            $dsn = static::buildDsn($config);
            $transport = Transport::fromDsn($dsn);

            // Test the connection
            $transport->start();
            $transport->stop();

            return true;
        } catch (\Throwable $e) {
            Log::error('SMTP configuration test failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a message using direct SMTP configuration.
     *
     * @param Message $message
     * @throws \RuntimeException
     */
    public function send(Message $message): void
    {
        $recipient = $message->recipient;
        $subject = $message->subject ?? '';
        $body = $message->body ?? '';

        if (empty($recipient)) {
            throw new \InvalidArgumentException('Recipient is required for SMTP send.');
        }

        // Validate SMTP configuration
        if (!static::test($this->config)) {
            throw new \RuntimeException('Invalid SMTP configuration provided.');
        }

        try {
            $dsn = static::buildDsn($this->config);
            $transport = Transport::fromDsn($dsn);

            // Create mailer
            $mailer = new Mailer($transport);

            // Create email
            $email = (new Email())
                ->from($this->config('from_email', $this->config('username')))
                ->to($recipient)
                ->subject($subject);

            // If body includes <html> tags, set as html, otherwise plain text
            if (stripos($body, '<html>') !== false) {
                $email->html($body);
            } else {
                $email->text($body);
            }

            // Send email
            $mailer->send($email);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Message could not be sent. SMTP Error: ' . $e->getMessage());
        }
    }
}
