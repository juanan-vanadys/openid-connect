<?php

declare(strict_types=1);

namespace OpenIDConnect\Laravel;

use Illuminate\Encryption\Encrypter;
use Laravel\Passport;
use Laravel\Passport\Bridge\AccessTokenRepository;
use Laravel\Passport\Bridge\ClientRepository;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Configuration;
use League\OAuth2\Server\AuthorizationServer;
use Slim\Psr7\Response;
use OpenIDConnect\ClaimExtractor;
use OpenIDConnect\Claims\ClaimSet;
use OpenIDConnect\Grant\AuthCodeGrant;
use OpenIDConnect\IdTokenResponse;

class PassportServiceProvider extends Passport\PassportServiceProvider
{
    public function register()
    {
        parent::register();

        $this->mergeConfigFrom(
            __DIR__ . '/config/openid.php',
            'openid'
        );
    }

    public function boot()
    {
        parent::boot();

        $this->publishes([
            __DIR__ . '/config/openid.php' => $this->app->configPath('openid.php'),
        ], ['openid', 'openid-config']);

        $this->loadRoutesFrom(__DIR__.'/routes/web.php');

        $tokens_can = config('openid.passport.tokens_can', null);
        if ($tokens_can) {
            Passport\Passport::tokensCan($tokens_can);
        }

        $this->registerClaimExtractor();
    }

    public function makeAuthorizationServer(): AuthorizationServer
    {
        $cryptKey = $this->makeCryptKey('private');
        $encryptionKey = app(Encrypter::class)->getKey();

        // JWT 3.4: use Key object with key contents
        $jwtConfig = Configuration::forSymmetricSigner(
            app(config('openid.signer')),
            new Key(file_get_contents($cryptKey->getKeyPath()))
        );

        $responseType = new IdTokenResponse(
            app(config('openid.repositories.identity')),
            app(ClaimExtractor::class),
            $jwtConfig,
            app(LaravelCurrentRequestService::class),
            $encryptionKey,
            config('openid.token_headers.kid', false)
        );

        return new AuthorizationServer(
            app(ClientRepository::class),
            app(AccessTokenRepository::class),
            app(config('openid.repositories.scope')),
            $cryptKey,
            $encryptionKey,
            $responseType,
        );
    }

    protected function buildAuthCodeGrant()
    {
        return new AuthCodeGrant(
            $this->app->make(Passport\Bridge\AuthCodeRepository::class),
            $this->app->make(Passport\Bridge\RefreshTokenRepository::class),
            new \DateInterval('PT10M'),
            new Response(),
            $this->app->make(LaravelCurrentRequestService::class),
        );
    }

    public function registerClaimExtractor()
    {
        $this->app->singleton(ClaimExtractor::class, function () {
            $customClaimSets = config('openid.custom_claim_sets');

            $claimSets = array_map(function ($claimSet, $name) {
                return new ClaimSet($name, $claimSet);
            }, $customClaimSets, array_keys($customClaimSets));

            return new ClaimExtractor(...$claimSets);
        });
    }
}
