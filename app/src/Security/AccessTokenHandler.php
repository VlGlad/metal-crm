<?php

namespace App\Security;

use App\Repository\ApiTokenRepository;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private readonly ApiTokenRepository $apiTokenRepository,
    ) {
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $apiToken = $this->apiTokenRepository->findValidToken($accessToken);

        if (!$apiToken || !$apiToken->isValid() || !$apiToken->getUser()->isActive()) {
            throw new BadCredentialsException('Invalid API token.');
        }

        return new UserBadge(
            $apiToken->getUser()->getUserIdentifier()
        );
    }
}
