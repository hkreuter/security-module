<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\GraphQL\Authentication\EventSubscriber;

use OxidEsales\GraphQL\Base\Event\BeforeTokenCreation;
use OxidEsales\SecurityModule\GraphQL\Authentication\DataType\TwoFAPendingUser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Stamps the `mfa_pending` claim onto the JWT when the API login produced a TwoFAPendingUser,
 * marking it as a short-lived 2FA challenge token that the verifyTwoFactor* mutations exchange
 * for a full token once the OTP is verified.
 *
 * Safe when the optional graphql-base module is absent: implements the CORE Symfony
 * EventSubscriberInterface (always present); keys getSubscribedEvents() by
 * BeforeTokenCreation::class (a compile-time string — does NOT autoload the event class); the
 * handler's param typehint is only resolved when the event fires, which never happens without
 * graphql-base.
 */
final class BeforeTokenCreationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [BeforeTokenCreation::class => 'onBeforeTokenCreation'];
    }

    public function onBeforeTokenCreation(BeforeTokenCreation $event): void
    {
        if ($event->getUser() instanceof TwoFAPendingUser) {
            $event->withClaim('mfa_pending', true);
        }
    }
}
