<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Token;

use Fouladgar\OTP\Contracts\AbstractTokenRepository;
use Fouladgar\OTP\Contracts\OTPNotifiable;
use Illuminate\Contracts\Cache\Repository as Cache;

class CacheTokenRepository extends AbstractTokenRepository
{
    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var string
     */
    private $prefix;

    public function __construct(Cache $cache, int $expires, int $tokenLength, string $prefix)
    {
        parent::__construct($expires, $tokenLength);

        $this->cache = $cache;
        $this->prefix = $prefix;
    }

    public function deleteExisting(OTPNotifiable $user): bool
    {
        return $this->cache->forget($this->getSignatureKey($user->getMobileForOTPNotification()));
    }

    public function exists(OTPNotifiable $user, string $token): bool
    {
        $signature = $this->getSignatureKey($user->getMobileForOTPNotification());

        return $this->cache->has($signature) &&
            $this->cache->get($signature)['token'] === $token;
    }

    protected function save(string $mobile, string $token): bool
    {
        return $this->cache->add(
            $this->getSignatureKey($mobile),
            $this->getPayload($mobile, $token),
            now()->addMinutes($this->expires)
        );
    }

    protected function getSignatureKey($mobile): string
    {
        return $this->prefix.$mobile;
    }
}
