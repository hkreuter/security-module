<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\GraphQL\EventSubscriber;

use OxidEsales\GraphQL\Base\Event\BeforeTokenCreation;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * SPIKE EXPERIMENT — validate that a subscriber to a graphql-base event compiles safely when
 * graphql-base is ABSENT. The real implementation will stamp the mfa_pending claim for a
 * TwoFAPendingUser via $event->withClaim(...).
 *
 * Safe-when-absent rationale: implements the CORE Symfony EventSubscriberInterface (always
 * present); keys getSubscribedEvents() by BeforeTokenCreation::class (a compile-time string —
 * does NOT autoload the event class); the handler's param typehint is only resolved when the
 * event fires, which never happens without graphql-base.
 */
final class BeforeTokenCreationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [BeforeTokenCreation::class => 'onBeforeTokenCreation'];
    }

    public function onBeforeTokenCreation(BeforeTokenCreation $event): void
    {
        // experiment stub — real impl: if $event->getUser() is a TwoFAPendingUser,
        // $event->withClaim('mfa_pending', true) + reduce TTL.
    }
}
