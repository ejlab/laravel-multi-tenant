<?php
namespace EJLab\Laravel\MultiTenant;

use Laravel\Passport\Bridge\RefreshTokenRepository as BaseRepository;

class RefreshTokenRepository extends BaseRepository
{
    public function persistNewRefreshToken(\League\OAuth2\Server\Entities\RefreshTokenEntityInterface $refreshTokenEntity)
    {
        $this->database = app()->make('db');
        parent::persistNewRefreshToken($refreshTokenEntity);
    }
}
