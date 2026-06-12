<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\GraphQL\Authentication\EventSubscriber;

use DateTimeImmutable;
use OxidEsales\GraphQL\Base\Event\BeforeTokenCreation;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Settings\TwoFAShopSettingsInterface;
use OxidEsales\SecurityModule\GraphQL\Authentication\DataType\TwoFAPendingUser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Stamps the 2FA-challenge claims onto the JWT when the API login produced a TwoFAPendingUser:
 *  - `mfa_pending` marks it as a challenge token the verifyTwoFactor* mutations exchange for a
 *    full token once the OTP is verified;
 *  - `mfa_exp` gives the challenge a short, resend-independent lifetime that TwoFactorVerify
 *    enforces. The effective lifetime is `min(ApiChallengeLifetime, OtpCodeLifetime)` — the
 *    Bearer is never useful longer than the OTP itself, so we clamp here rather than letting
 *    operators misconfigure a Bearer that outlives the underlying OTP and produces confusing
 *    "expired" errors mid-verify.
 *    graphql-base bakes the JWT `exp` (default 8h) before this event and the event only lets us
 *    ADD claims (no expiry override), so we cannot shorten `exp` itself — instead we bound the
 *    challenge's only capability (the verify exchange) with our own expiry claim.
 *
 * Safe when the optional graphql-base module is absent: implements the CORE Symfony
 * EventSubscriberInterface (always present); keys getSubscribedEvents() by
 * BeforeTokenCreation::class (a compile-time string — does NOT autoload the event class); the
 * handler's param typehint is only resolved when the event fires, which never happens without
 * graphql-base. The injected settings service is a module-internal service, always present.
 */
final class BeforeTokenCreationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TwoFAShopSettingsInterface $settings,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [BeforeTokenCreation::class => 'onBeforeTokenCreation'];
    }

    public function onBeforeTokenCreation(BeforeTokenCreation $event): void
    {
        if (!$event->getUser() instanceof TwoFAPendingUser) {
            return;
        }

        $lifetime = min(
            $this->settings->getApiChallengeLifetime(),
            $this->settings->getOtpCodeLifetime(),
        );
        $challengeExpiresAt = (new DateTimeImmutable())->getTimestamp() + $lifetime;

        $event->withClaim('mfa_pending', true);
        $event->withClaim('mfa_exp', $challengeExpiresAt);
    }
}
