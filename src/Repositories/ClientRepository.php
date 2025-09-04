<?php

declare(strict_types=1);

namespace OpenIDConnect\Repositories;

use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use OpenIDConnect\Entities\ClientEntity;

class ClientRepository implements ClientRepositoryInterface
{
    public function getClientEntity(
        $clientIdentifier,
        $grantType = null,
        $clientSecret = null,
        $mustValidateSecret = true
    )
    {
        $client = new ClientEntity();
        $client->setIdentifier('1');
        $client->setRedirectUri('http://example.com/callback');
        $client->setName('Example');
        return $client;
    }

    public function validateClient($clientIdentifier, $clientSecret, $grantType)
    {
        return true;
    }
}
