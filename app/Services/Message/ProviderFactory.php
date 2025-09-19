<?php

namespace App\Services\Message;

use App\Exceptions\UnknownConnectorNameException;
use App\Models\MessageProvider;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;

class ProviderFactory
{
    /**
     * Create a connector instance for the given message provider.
     * Throws UnknownConnectorNameException if the provider cannot be resolved.
     * If provider->provider is a known alias (e.g. 'smtp'), it will resolve to the appropriate class.
     * If provider->provider is a FQCN, it will be attempted to be resolved from the container.
     *
     * @param MessageProvider|null $provider
     * @return ConnectorInterface
     * @throws UnknownConnectorNameException
     */
    public function create(?MessageProvider $provider): ConnectorInterface
    {
        if ($provider === null) {
            throw new UnknownConnectorNameException('null');
        }

        $identifier = $provider->provider;

        $class = match($identifier) {
            'smtp' => SMTPConnector::class,
            'log' => LogConnector::class,
            default => throw new UnknownConnectorNameException($identifier),
        };

        try {
            $instance = new $class($provider->config ?? []);

            if (! $instance instanceof ConnectorInterface) {
                throw new \RuntimeException(sprintf('Provider class %s must implement ConnectorInterface.', $class));
            }

            return $instance;
        } catch (BindingResolutionException|\Throwable $e) {
            Log::error('Failed to resolve message provider connector: ' . $e->getMessage());

            throw new UnknownConnectorNameException($identifier, $e);
        }
    }
}
