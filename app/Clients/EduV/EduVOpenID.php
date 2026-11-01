<?php

namespace App\Clients\EduV;

use Jumbojett\OpenIDConnectClient;

class EduVOpenID
{
  // The OpenID Connect client instance
  public $client;

  // The constructor initializes the OpenID Connect client with the provided configuration
  public function __construct()
  {
    $clientId = config('clients.eduv.oidc.client.id');
    $clientSecret = config('clients.eduv.oidc.client.secret');
    $oidcProvider = config('clients.eduv.oidc.provider');

    // Create the OpenID Connect client instance
    $client = new OpenIDConnectClient(
      $oidcProvider,
      $clientId,
      $clientSecret
    );

    // Override the default endpoints if needed
    $client->providerConfigParam([
      // 'token_endpoint' => 'https://example-idp.com/oauth/token',
    ]);

    $this->client = $client;
  }

  public function getAccessToken()
  {
    return $this->client->requestClientCredentialsToken()->access_token;
  }
}
