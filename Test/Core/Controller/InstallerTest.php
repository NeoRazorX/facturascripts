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

namespace FacturaScripts\Test\Core\Controller;

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Controller\Installer;
use FacturaScripts\Core\Tools;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class InstallerTest extends TestCase
{
    public function testConfigLineBool(): void
    {
        $this->assertSame("define('FS_TEST', true);\n", $this->configLine('configLineBool', 'FS_TEST', true));
        $this->assertSame("define('FS_TEST', true);\n", $this->configLine('configLineBool', 'FS_TEST', 'true'));
        $this->assertSame("define('FS_TEST', false);\n", $this->configLine('configLineBool', 'FS_TEST', 'false'));
        $this->assertSame("define('FS_TEST', false);\n", $this->configLine('configLineBool', 'FS_TEST', false));

        // un valor manipulado no puede inyectar código en config.php
        $this->assertSame(
            "define('FS_TEST', false);\n",
            $this->configLine('configLineBool', 'FS_TEST', 'false);phpinfo();//')
        );
    }

    public function testConfigLineInt(): void
    {
        $this->assertSame("define('FS_TEST', 31536000);\n", $this->configLine('configLineInt', 'FS_TEST', '31536000'));
        $this->assertSame("define('FS_TEST', 3306);\n", $this->configLine('configLineInt', 'FS_TEST', 3306));

        // un valor manipulado no puede inyectar código en config.php
        $this->assertSame(
            "define('FS_TEST', 0);\n",
            $this->configLine('configLineInt', 'FS_TEST', '0);phpinfo();//')
        );
    }

    public function testConfigLineString(): void
    {
        $this->assertSame("define('FS_TEST', 'abc');\n", $this->configLine('configLineString', 'FS_TEST', 'abc'));
        $this->assertSame("define('FS_TEST', '');\n", $this->configLine('configLineString', 'FS_TEST', null));

        // las comillas y barras invertidas se escapan
        $line = $this->configLine('configLineString', 'FS_TEST', "a\\');phpinfo();//");
        $this->assertSame("define('FS_TEST', 'a\\\\\\');phpinfo();//');\n", $line);

        // y la línea generada define exactamente el valor original
        $tokens = token_get_all('<?php ' . $line);
        $strings = array_filter($tokens, fn($token) => is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING);
        $this->assertCount(2, $strings);
        $this->assertSame("'a\\\\\\');phpinfo();//'", array_values($strings)[1][1]);
    }

    public function testMysqlInvalidDatabaseName(): void
    {
        if (Tools::config('db_type') !== 'mysql') {
            $this->markTestSkipped('Solo aplica a MySQL.');
        }

        // un nombre con guion no es un identificador válido sin comillas
        MiniLog::clear();
        $result = $this->invoke('testMysql', [[
            'host' => Tools::config('db_host'),
            'port' => (int)Tools::config('db_port'),
            'user' => Tools::config('db_user'),
            'pass' => Tools::config('db_pass'),
            'name' => 'fs-installer-test',
            'socket' => '',
        ]]);

        // devuelve false y lo explica, en lugar de lanzar una excepción
        $this->assertFalse($result);
        $originals = array_column(MiniLog::read('', ['critical']), 'original');
        $this->assertContains('cant-create-database', $originals);
        MiniLog::clear();
    }

    public function testPostgresqlWrongPassword(): void
    {
        if (Tools::config('db_type') !== 'postgresql') {
            $this->markTestSkipped('Solo aplica a PostgreSQL.');
        }

        // con una contraseña incorrecta no se puede conectar ni a la base de datos ni a postgres,
        // y como en el servidor web, los avisos de PHP vienen escapados como HTML
        MiniLog::clear();
        $htmlErrors = ini_set('html_errors', '1');
        try {
            $result = $this->invoke('testPostgresql', [[
                'host' => Tools::config('db_host'),
                'port' => (int)Tools::config('db_port'),
                'user' => Tools::config('db_user'),
                'pass' => Tools::config('db_pass') . '-incorrecta',
                'name' => Tools::config('db_name'),
                'pgsql-ssl' => '',
                'pgsql-endpoint' => '',
            ]]);
        } finally {
            ini_set('html_errors', $htmlErrors);
        }

        // devuelve false sin emitir avisos de PHP y explica el motivo, sin escapar
        $this->assertFalse($result);
        $logs = MiniLog::read('', ['critical']);
        $this->assertContains('cant-connect-database', array_column($logs, 'original'));
        $this->assertCount(2, $logs);
        $this->assertStringContainsString('"' . Tools::config('db_user') . '"', $logs[1]['original']);
        $this->assertStringNotContainsString('&quot;', $logs[1]['original']);
        MiniLog::clear();
    }

    private function configLine(string $method, string $name, $value): string
    {
        return $this->invoke($method, [$name, $value]);
    }

    private function invoke(string $method, array $args)
    {
        // el constructor falla si ya existe config.php, así que lo omitimos
        $installer = (new ReflectionClass(Installer::class))->newInstanceWithoutConstructor();

        return (new ReflectionMethod(Installer::class, $method))->invokeArgs($installer, $args);
    }
}
