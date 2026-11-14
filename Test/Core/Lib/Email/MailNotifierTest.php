<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\Email\NewMail;
use FacturaScripts\Core\Model\EmailNotification;
use FacturaScripts\Core\Tools;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

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

    public function testNoPuedeEnviarConCopiasSiNoExisteNotificacion()
    {
        $response = MailNotifier::sendWithCopies(
            'nombre-de-notificacion-erroneo',
            'test@test.com',
            ['copia@test.com'],
            ['oculta@test.com']
        );

        $this->assertFalse($response);
    }

    public function testNoPuedeEnviarConCopiasSiLaNotificacionNoEstaActiva()
    {
        self::$notification->enabled = false;
        self::$notification->save();

        $response = MailNotifier::sendWithCopies(
            'sendmail-EmailTest',
            'test@test.com',
            ['copia@test.com']
        );

        $this->assertFalse($response);

        self::$notification->enabled = true;
        self::$notification->save();
    }

    public function testElCorreoIncluyeLasCopiasValidas()
    {
        $newMail = $this->newMail(
            'sendmail-EmailTest',
            'test@test.com',
            ['copia@test.com', '', 'no-es-un-email', null],
            [' oculta@test.com '],
            'Nombre de pruebas'
        );

        $this->assertNotNull($newMail);
        $this->assertEquals(['test@test.com'], $newMail->getToAddresses());
        $this->assertContains('copia@test.com', $newMail->getCCAddresses());
        $this->assertNotContains('no-es-un-email', $newMail->getCCAddresses());
        $this->assertNotContains('', $newMail->getCCAddresses());
        $this->assertContains('oculta@test.com', $newMail->getBCCAddresses());
        $this->assertNotContains('oculta@test.com', $newMail->getCCAddresses());
        $this->assertNotContains('copia@test.com', $newMail->getBCCAddresses());
    }

    public function testElCorreoSustituyeLosParametros()
    {
        $subject = self::$notification->subject;
        $body = self::$notification->body;
        self::$notification->subject = 'Hola {name}';
        self::$notification->body = 'Email: {email}. Dato: {dato}. Código: {verificode}.';
        self::$notification->save();

        $newMail = $this->newMail(
            'sendmail-EmailTest',
            'test@test.com',
            [],
            [],
            'Nombre de pruebas',
            ['dato' => 123]
        );

        self::$notification->subject = $subject;
        self::$notification->body = $body;
        self::$notification->save();

        $this->assertNotNull($newMail);
        $this->assertEquals('Hola Nombre de pruebas', $newMail->title);
        $this->assertEquals(
            'Email: test@test.com. Dato: 123. Código: ' . $newMail->verificode . '.',
            $newMail->text
        );
    }

    public function testNoHayCorreoSiNoExisteNotificacion()
    {
        $this->assertNull($this->newMail('nombre-de-notificacion-erroneo', 'test@test.com'));
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

        // con copias no válidas, vacías o nulas tampoco falla
        $response = MailNotifier::sendWithCopies(
            'sendmail-EmailTest',
            'test@test.com',
            ['copia@test.com', '', 'no-es-un-email', null],
            [' oculta@test.com ']
        );

        $this->assertFalse($response);
    }

    private function newMail(string $notificationName, string $email, array $cc = [], array $bcc = [], string $name = '', array $params = []): ?NewMail
    {
        $method = new ReflectionMethod(MailNotifier::class, 'newMail');

        return $method->invoke(null, $notificationName, $email, $cc, $bcc, $name, $params, [], [], []);
    }
}
