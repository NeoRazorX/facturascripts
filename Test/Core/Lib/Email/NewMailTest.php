<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Test\Core\Lib\Email;

use FacturaScripts\Core\Lib\Email\NewMail;
use PHPUnit\Framework\TestCase;

final class NewMailTest extends TestCase
{
    public function testCreate(): void
    {
        $mailer = NewMail::create()
            ->to('test@facturascripts.com', 'test name')
            ->subject('test subject')
            ->body('test body');

        $this->assertInstanceOf(NewMail::class, $mailer);

        $this->assertEquals('test subject', $mailer->title);
        $this->assertEquals('test body', $mailer->text);

        $this->assertCount(1, $mailer->getToAddresses());
        $this->assertContains('test@facturascripts.com', $mailer->getToAddresses());

        $this->assertEmpty($mailer->getCcAddresses());
        $this->assertEmpty($mailer->getBccAddresses());
    }

    public function testCC(): void
    {
        $mailer = NewMail::create()
            ->cc('test-cc@facturascripts.com', 'test cc name')
            ->subject('cc subject')
            ->body('cc body');

        $this->assertEmpty($mailer->getToAddresses());
        $this->assertCount(1, $mailer->getCcAddresses());
        $this->assertContains('test-cc@facturascripts.com', $mailer->getCcAddresses());
        $this->assertEmpty($mailer->getBccAddresses());
    }

    public function testBCC(): void
    {
        $mailer = NewMail::create()
            ->bcc('test-bcc@facturascripts.com', 'test bcc name')
            ->subject('bcc subject')
            ->body('bcc body');

        $this->assertEmpty($mailer->getToAddresses());
        $this->assertEmpty($mailer->getCcAddresses());
        $this->assertCount(1, $mailer->getBccAddresses());
    }
}
