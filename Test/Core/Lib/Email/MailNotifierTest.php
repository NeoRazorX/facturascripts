<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\Email\MailNotifier;
use FacturaScripts\Core\Model\EmailNotification;
use FacturaScripts\Core\Tools;
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

        Tools::settingsSet('email', 'email', 'test@test.com');
        Tools::settingsSet('email', 'mailer', 'smtp');
        Tools::settingsSet('email', 'host', 'localhost');
        Tools::settingsSet('email', 'port', '1025');
        Tools::settingsSet('email', 'user', 'facturascripts');
        Tools::settingsSet('email', 'password', 'password');
        Tools::settingsSet('email', 'lowsecure', 'true');
        Tools::settingsSave();
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
        Tools::settingsSet('email', 'email', null);
        Tools::settingsSet('email', 'mailer', null);
        Tools::settingsSet('email', 'host', null);
        Tools::settingsSet('email', 'port', null);
        Tools::settingsSet('email', 'user', null);
        Tools::settingsSet('email', 'password', null);
        Tools::settingsSet('email', 'lowsecure', null);
        Tools::settingsSave();

        $response = MailNotifier::send(
            'sendmail-EmailTest',
            'test@test.com'
        );

        $this->assertFalse($response);
    }
}
