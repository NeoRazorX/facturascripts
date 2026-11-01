<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core;

use FacturaScripts\Core\CrashReport;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class CrashReportTest extends TestCase
{
    use LogErrorsTrait;

    /** @var array */
    private $cookies;

    /** @var array */
    private $server;

    public function testDeployButtonsForAdminSession(): void
    {
        $this->skipIfDeployActionsDisabled();

        $user = new User();
        $user->nick = 'test_crash_' . mt_rand(1, 9999);
        $this->assertTrue($user->setPassword('test-crash-1234'), 'can-not-set-password');
        $user->admin = true;
        $logkey = $user->newLogkey('192.168.1.10');
        $this->assertTrue($user->save(), 'can-not-save-user');

        // un administrador con la sesión válida ve los botones
        $_COOKIE = ['fsNick' => $user->nick, 'fsLogkey' => $logkey];
        $this->assertTrue(CrashReport::canShowDeployButtons(), 'admin-session-without-buttons');

        // pero no con otra logkey
        $_COOKIE['fsLogkey'] = 'bad-logkey';
        $this->assertFalse(CrashReport::canShowDeployButtons(), 'bad-logkey-with-buttons');

        // ni si no es administrador
        $user->admin = false;
        $this->assertTrue($user->save(), 'can-not-save-user');
        $_COOKIE['fsLogkey'] = $logkey;
        $this->assertFalse(CrashReport::canShowDeployButtons(), 'non-admin-with-buttons');

        // ni si está desactivado
        $user->admin = true;
        $user->enabled = false;
        $this->assertTrue($user->save(), 'can-not-save-user');
        $this->assertFalse(CrashReport::canShowDeployButtons(), 'disabled-user-with-buttons');

        $this->assertTrue($user->delete(), 'can-not-delete-user');
    }

    public function testDeployButtonsForLocalRequest(): void
    {
        $this->skipIfDeployActionsDisabled();

        foreach (['127.0.0.1', '::1'] as $ip) {
            $_SERVER['REMOTE_ADDR'] = $ip;
            $this->assertTrue(CrashReport::canShowDeployButtons(), 'local-request-without-buttons: ' . $ip);
        }

        // si pasa por un proxy, la petición no es local aunque REMOTE_ADDR sea 127.0.0.1
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.7';
        $this->assertFalse(CrashReport::canShowDeployButtons(), 'proxied-request-with-buttons');
    }

    public function testDeprecationDoesNotCrash(): void
    {
        // como hace symfony/deprecation-contracts: un aviso silenciado que queda en error_get_last()
        // dejamos que lo procese el manejador de PHP y no el de PHPUnit
        error_clear_last();
        set_error_handler(static function (): bool {
            return false;
        });
        @trigger_error('Not passing a value for the "parent" argument of macro "showMenu" is deprecated.', E_USER_DEPRECATED);
        restore_error_handler();
        $this->assertSame(E_USER_DEPRECATED, error_get_last()['type'] ?? null, 'deprecation-not-registered');

        // shutdown() no debe mostrar la pantalla de error
        ob_start();
        CrashReport::shutdown();
        $output = ob_get_clean();
        error_clear_last();

        $this->assertSame('', $output, 'deprecation-shows-crash-report');
    }

    public function testErrorHashIgnoresStackTrace(): void
    {
        $file = FS_FOLDER . '/Core/Base/DataBase/MysqlEngine.php';
        $message = 'Connection refused';
        $info1 = CrashReport::getErrorInfo(2002, $message . "\nStack trace:\n#0 " . FS_FOLDER
            . "/Core/Kernel.php(267): FacturaScripts\\Core\\Kernel::runController('/')\n#1 {main}", $file, 201);
        $info2 = CrashReport::getErrorInfo(2002, $message . "\r\nStack trace:\r\n#0 " . FS_FOLDER
            . "/index.php(49): FacturaScripts\\Core\\Kernel::init()\r\n#1 {main}", $file, 201);
        $this->assertSame($info1['hash'], $info2['hash'], 'stack-trace-changes-hash');

        // pero sí cambia con el mensaje, el código, el archivo o la línea
        $hashes = [
            $info1['hash'],
            CrashReport::getErrorInfo(2002, 'Too many connections', $file, 201)['hash'],
            CrashReport::getErrorInfo(1, $message, $file, 201)['hash'],
            CrashReport::getErrorInfo(2002, $message, FS_FOLDER . '/Core/Base/DataBase.php', 201)['hash'],
            CrashReport::getErrorInfo(2002, $message, $file, 202)['hash'],
        ];
        $this->assertSame($hashes, array_unique($hashes), 'different-errors-same-hash');
    }

    public function testErrorInfoNormalizesWindowsPaths(): void
    {
        $folder = FS_FOLDER;
        $winFolder = str_replace('/', '\\', FS_FOLDER);
        $message = "Uncaught Error: Class \"FacturaScripts\\Dinamic\\Model\\User\" not found in %s:375\n"
            . "Stack trace:\n#0 %s(632): FacturaScripts\\Core\\Base\\Controller->auth()\n#1 {main}\n  thrown";

        $linux = CrashReport::getErrorInfo(1, sprintf(
            $message,
            $folder . '/Core/Base/Controller.php',
            $folder . '/Core/Kernel.php'
        ), $folder . '/Core/Base/Controller.php', 375);
        $windows = CrashReport::getErrorInfo(1, sprintf(
            $message,
            $winFolder . '\\Core\\Base\\Controller.php',
            $winFolder . '\\Core\\Kernel.php'
        ), $winFolder . '\\Core\\Base\\Controller.php', 375);

        // las rutas quedan relativas y con /, pero los namespaces no cambian
        $this->assertSame('/Core/Base/Controller.php', $windows['file'], 'windows-file-not-normalized');
        $this->assertSame($linux['message'], $windows['message'], 'windows-message-not-normalized');
        $this->assertStringContainsString('in /Core/Base/Controller.php:375', $windows['message']);
        $this->assertStringContainsString('#0 /Core/Kernel.php(632)', $windows['message']);
        $this->assertStringContainsString('"FacturaScripts\\Dinamic\\Model\\User"', $windows['message']);
        $this->assertSame($linux['hash'], $windows['hash'], 'windows-path-changes-hash');

        // con separadores mezclados, como las plantillas de Twig en Windows
        $mixed = CrashReport::getErrorInfo(0, 'Template error', $winFolder . '/Dinamic/View/Test.html.twig', 23);
        $this->assertSame('/Dinamic/View/Test.html.twig', $mixed['file'], 'mixed-file-not-normalized');

        // las rutas de fuera de la instalación no se tocan
        $outside = CrashReport::getErrorInfo(1, 'Error', $folder . '-other/Core/Test.php', 1);
        $this->assertSame($folder . '-other/Core/Test.php', $outside['file'], 'outside-file-changed');
    }

    public function testFatalErrorTypes(): void
    {
        // los errores fatales muestran la pantalla de error
        foreach ([E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR] as $type) {
            $this->assertTrue(CrashReport::isFatalError($type), 'not-fatal-error-type: ' . $type);
        }

        // los avisos y deprecaciones no, tampoco los de usuario (trigger_error)
        $types = [
            E_WARNING, E_NOTICE, E_DEPRECATED, E_CORE_WARNING,
            E_USER_WARNING, E_USER_NOTICE, E_USER_DEPRECATED,
        ];
        foreach ($types as $type) {
            $this->assertFalse(CrashReport::isFatalError($type), 'fatal-error-type: ' . $type);
        }
    }

    public function testNoDeployButtonsForAttacker(): void
    {
        $this->skipIfDeployActionsDisabled();

        // cookies de login inventadas
        $_COOKIE = ['fsNick' => 'admin', 'fsLogkey' => 'fake'];
        $this->assertFalse(CrashReport::canShowDeployButtons(), 'fake-cookies-with-buttons');

        // cabecera Host manipulada
        $_COOKIE = [];
        $_SERVER['HTTP_HOST'] = 'localhost';
        $this->assertFalse(CrashReport::canShowDeployButtons(), 'host-localhost-with-buttons');

        // cookies que no son texto
        $_COOKIE = ['fsNick' => ['admin'], 'fsLogkey' => ['fake']];
        $this->assertFalse(CrashReport::canShowDeployButtons(), 'array-cookies-with-buttons');
    }

    protected function setUp(): void
    {
        $this->cookies = $_COOKIE;
        $this->server = $_SERVER;

        // partimos de una petición remota, sin cookies ni proxy
        $_COOKIE = [];
        $_SERVER['REMOTE_ADDR'] = '203.0.113.5';
        unset($_SERVER['HTTP_CF_CONNECTING_IP'], $_SERVER['HTTP_FORWARDED'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_X_REAL_IP']);
    }

    protected function tearDown(): void
    {
        $_COOKIE = $this->cookies ?? [];
        $_SERVER = $this->server ?? $_SERVER;

        $this->logErrors();
    }

    private function skipIfDeployActionsDisabled(): void
    {
        if (Tools::config('disable_deploy_actions', false)) {
            $this->markTestSkipped('deploy-actions-disabled');
        }
    }
}
