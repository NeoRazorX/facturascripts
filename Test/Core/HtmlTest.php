<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Html;
use FacturaScripts\Core\Tools;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

/**
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
final class HtmlTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        Html::addPath('Test', FS_FOLDER . '/Test/__files');
    }

    public function testTemplate()
    {
        $expected = "testTemplate\n"
            . Tools::config('lang') . "\n"
            . Tools::config('test123', '123') . "\n"
            . Tools::money(123.45) . "\n"
            . Tools::number(123.45) . "\n"
            . Tools::lang()->trans('save') . "\n"
            . Tools::bytes(0, 0) . "\n"
            . Tools::bytes(1, 1) . "\n"
            . Tools::bytes(2, 2) . "\n"
            . Tools::bytes(1025, 1) . "\n"
            . Tools::bytes(1048577, 0) . "\n"
            . Tools::bytes(1073741825);

        $html = Html::render('@Test/testTemplate.html.twig');
        $this->assertEquals($expected, $html, 'html-not-equal-for-testTemplate');
    }

    public function testCustomFunction()
    {
        Html::addFunction(new TwigFunction('testCustomFunction', function () {
            return 'testCustomFunction';
        }));
        $html = Html::render('@Test/testCustomFunction.html.twig');
        $this->assertEquals('testCustomFunction', $html, 'html-not-equal-for-testCustomFunction');
    }
}
