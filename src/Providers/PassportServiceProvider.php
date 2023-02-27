<?php

namespace EJLab\Laravel\MultiTenant\Providers;

use Laravel\Passport\PassportServiceProvider as BaseProvider;
use EJLab\Laravel\MultiTenant\RefreshTokenRepository;
use EJLab\Laravel\MultiTenant\TokenGuard;
use Illuminate\Auth\RequestGuard;
use League\OAuth2\Server\ResourceServer;
use Laravel\Passport\TokenRepository;
use Laravel\Passport\ClientRepository;
use League\OAuth2\Server\Grant\RefreshTokenGrant;

use Auth;

class PassportServiceProvider extends BaseProvider
{
    /**
     * Create and configure a Password grant instance.
     *
     * @return PasswordGrant
     */
    protected function makePasswordGrant()
    {
        $grant = new \League\OAuth2\Server\Grant\PasswordGrant(
            $this->app->make(\Laravel\Passport\Bridge\UserRepository::class),
            $this->app->make(RefreshTokenRepository::class)
        );

        $grant->setRefreshTokenTTL(\Laravel\Passport\Passport::refreshTokensExpireIn());

        return $grant;
    }
    /**
     * Create and configure a Refresh Token grant instance.
     *
     * @return \League\OAuth2\Server\Grant\RefreshTokenGrant
     */
    protected function makeRefreshTokenGrant()
    {   
        $repository = $this->app->make(RefreshTokenRepository::class);

        return tap(new RefreshTokenGrant($repository), function ($grant) {
            $grant->setRefreshTokenTTL(\Laravel\Passport\Passport::refreshTokensExpireIn());
        });
    }

    /**
     * Make an instance of the token guard.
     *
     * @param  array  $config
     * @return \Illuminate\Auth\RequestGuard
     */
    protected function makeGuard(array $config)
    {
        return new RequestGuard(function ($request) use ($config) {
            return (new TokenGuard(
                $this->app->make(ResourceServer::class),
                Auth::createUserProvider($config['provider']),
                $this->app->make(TokenRepository::class),
                $this->app->make(ClientRepository::class),
                $this->app->make('encrypter')
            ))->user($request);
        }, $this->app['request']);
    }

}