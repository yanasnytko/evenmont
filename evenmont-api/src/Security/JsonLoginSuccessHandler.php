<?php

namespace App\Security;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;

class JsonLoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwt,
        private RefreshTokenGeneratorInterface $refreshGenerator,
        private RefreshTokenManagerInterface $refreshManager,
        private LoggerInterface $logger, // autowire "logger"
    ) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse
    {
        try {
            $user = $token->getUser();

            // 1) Access token
            $accessToken = $this->jwt->create($user);

            // 2) Refresh token (30 jours)
            $ttl = 60 * 60 * 24 * 30;
            $refresh = $this->refreshGenerator->createForUserWithTtl($user, $ttl);
            $this->refreshManager->save($refresh);

            // 3) Réponse JSON + cookie HttpOnly
            $resp = new JsonResponse([
                'token' => $accessToken,
                'user'  => [
                    'email' => method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : null,
                    'roles' => method_exists($user, 'getRoles') ? $user->getRoles() : [],
                ],
            ]);

            $isHttps = $request->isSecure();
            $cookie = Cookie::create(
                'refresh_token',
                $refresh->getRefreshToken(),
                new DateTimeImmutable("+{$ttl} seconds"),
                '/',
                null,
                $isHttps ? true : false, // secure only if HTTPS
                true,                    // HttpOnly
                false,
                Cookie::SAMESITE_LAX
            );
            $resp->headers->setCookie($cookie);

            return $resp;
        } catch (\Throwable $e) {
            $this->logger->error('Login success handler failed', [
                'exception' => $e::class,
                'message'   => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            return new JsonResponse([
                'error'   => 'login_handler_failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
