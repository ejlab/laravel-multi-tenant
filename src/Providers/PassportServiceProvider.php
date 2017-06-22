<?php

namespace EJLab\Laravel\MultiTenant\Providers;

use Laravel\Passport\PassportServiceProvider as BaseProvider;
use EJLab\Laravel\MultiTenant\RefreshTokenRepository;

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
}