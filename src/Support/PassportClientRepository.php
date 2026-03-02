<?php

namespace Webkul\Mcp\Support;

use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

class PassportClientRepository extends ClientRepository
{
    public function createAuthorizationCodeGrantClient(
        string $name,
        array $redirectUris,
        bool $confidential = true,
        mixed $user = null,
        bool $enableDeviceFlow = false,
    ): Client {
        $userId = is_object($user) && isset($user->id) ? $user->id : null;

        $client = $this->create(
            userId: $userId,
            name: $name,
            redirect: implode(',', $redirectUris),
            provider: null,
            personalAccess: false,
            password: false,
            confidential: $confidential,
        );

        $client->setAttribute('grant_types', ['authorization_code', 'refresh_token']);
        $client->setAttribute('redirect_uris', $redirectUris);

        if ($enableDeviceFlow) {
            $client->setAttribute('grant_types', ['authorization_code', 'refresh_token', 'urn:ietf:params:oauth:grant-type:device_code']);
        }

        return $client;
    }
}
