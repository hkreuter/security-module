<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Infrastructure\Provider\Email;

use OxidEsales\Eshop\Core\Email;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Provider\Email\EmailAdapter;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Provider\Email\EmailFactoryInterface;
use PHPUnit\Framework\TestCase;

class EmailAdapterTest extends TestCase
{
    public function testSendCreatesEmailAndSends(): void
    {
        $email = $this->createMock(Email::class);
        $email->expects($this->once())
            ->method('setRecipient')
            ->with('user@example.com');
        $email->expects($this->once())
            ->method('setSubject');
        $email->expects($this->once())
            ->method('setBody')
            ->with($this->stringContains('123456'));
        $email->expects($this->once())
            ->method('send')
            ->willReturn(true);

        $factory = $this->createMock(EmailFactoryInterface::class);
        $factory->method('create')
            ->willReturn($email);

        $sut = new EmailAdapter($factory);
        $sut->send('user@example.com', '123456');
    }
}
