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

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Lib\Email\MailNotifier;
use FacturaScripts\Core\Model\EmailNotification;
use PHPUnit\Framework\TestCase;

class MailNotifierTest extends TestCase
{
    /**
     * @var EmailNotification
     */
    private static $notification;

    public static function setUpBeforeClass(): void
    {
        $database = new DataBase();
        $database->connect();

        self::$notification = new EmailNotification();
        self::$notification->name = 'sendmail-EmailTest';
        self::$notification->body = 'Cuerpo del correo electrónico de pruebas';
        self::$notification->subject = 'Asunto del correo electrónico de pruebas';
        self::$notification->enabled = true;
        self::$notification->save();

        $appSettings = ToolBox::appSettings();
        $appSettings->set('email', 'email', 'test@test.com');
        $appSettings->set('email', 'mailer', 'smtp');
        $appSettings->set('email', 'host', 'localhost');
        $appSettings->set('email', 'port', '1025');
        $appSettings->set('email', 'user', 'facturascripts');
        $appSettings->set('email', 'password', 'password');
        $appSettings->set('email', 'lowsecure', 'true');
        $appSettings->save();
    }

    public function testNoPuedeEnviarEmailSiNoExisteNotificacion()
    {
        $response = MailNotifier::send(
            'nombre-de-notificacion-erroneo',
            'test@test.com'
        );

        $this->assertFalse($response);
    }

    public function testNoPuedeEnviarEmailSiLaNotificacionNoEstaActiva()
    {
        self::$notification->enabled = false;
        self::$notification->save();

        $response = MailNotifier::send(
            'sendmail-EmailTest',
            'test@test.com'
        );

        $this->assertFalse($response);

        self::$notification->enabled = true;
        self::$notification->save();
    }

    public function testNoPuedeEnviarEmailSiNoEstaConfigurado()
    {
        $appSettings = ToolBox::appSettings();
        $appSettings->set('email', 'email', null);
        $appSettings->set('email', 'mailer', null);
        $appSettings->set('email', 'host', null);
        $appSettings->set('email', 'port', null);
        $appSettings->set('email', 'user', null);
        $appSettings->set('email', 'password', null);
        $appSettings->set('email', 'lowsecure', null);
        $appSettings->save();

        $response = MailNotifier::send(
            'sendmail-EmailTest',
            'test@test.com'
        );

        $this->assertFalse($response);
    }
}
