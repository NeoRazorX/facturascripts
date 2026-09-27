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

use FacturaScripts\Core\Controller\Installer;
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

    private function configLine(string $method, string $name, $value): string
    {
        // el constructor falla si ya existe config.php, así que lo omitimos
        $installer = (new ReflectionClass(Installer::class))->newInstanceWithoutConstructor();

        $reflection = new ReflectionMethod(Installer::class, $method);
        $reflection->setAccessible(true);
        return $reflection->invoke($installer, $name, $value);
    }
}
