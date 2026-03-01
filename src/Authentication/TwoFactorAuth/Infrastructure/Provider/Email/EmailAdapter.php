<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Provider\Email;

class EmailAdapter implements EmailAdapterInterface
{
    public function __construct(
        private readonly EmailFactoryInterface $emailFactory,
    ) {
    }

    public function send(string $email, string $code): void
    {
        $mailer = $this->emailFactory->create();
        $mailer->setRecipient($email);
        $mailer->setSubject('Your verification code');
        $mailer->setBody(sprintf('Your verification code is: %s', $code));
        $mailer->send();
    }
}
