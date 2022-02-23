<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Lib;

use FacturaScripts\Core\Lib\CodePatterns;
use FacturaScripts\Core\Model\PedidoCliente;
use FacturaScripts\Core\Model\Producto;
use PHPUnit\Framework\TestCase;

final class CodePatternsTest extends TestCase
{
    public function testDefault()
    {
        $order = new PedidoCliente();
        $order->codejercicio = '2021';
        $order->codserie = 'A';
        $order->numero = '1';

        $code = CodePatterns::trans('PED{EJE}{SERIE}{NUM}', $order);
        $this->assertEquals('PED2021A1', $code, 'different-code');
    }

    public function testZeroNum()
    {
        $order = new PedidoCliente();
        $order->codejercicio = '2021';
        $order->codserie = 'A';
        $order->numero = '22';

        $code = CodePatterns::trans('PED{EJE}{SERIE}{0NUM}', $order, ['long' => 6]);
        $this->assertEquals('PED2021A000022', $code, 'different-code');
    }

    public function testEje2()
    {
        $order = new PedidoCliente();
        $order->codejercicio = '2022';
        $order->codserie = 'A';
        $order->numero = '555';

        $code = CodePatterns::trans('PED{EJE2}{SERIE}{0NUM}', $order, ['long' => 6]);
        $this->assertEquals('PED22A000555', $code, 'different-code');
    }

    public function testZeroSerie()
    {
        $order = new PedidoCliente();
        $order->codejercicio = '2022';
        $order->codserie = 'C';
        $order->numero = '9999';

        $code = CodePatterns::trans('{EJE2}{0SERIE}{0NUM}', $order, ['long' => 6]);
        $this->assertEquals('220C009999', $code, 'different-code');
    }

    public function testAnyoMesDiaNum()
    {
        $order = new PedidoCliente();
        $order->codejercicio = '2022';
        $order->codserie = 'C';
        $order->fecha = '23-11-2021';
        $order->numero = '777';

        $code = CodePatterns::trans('{ANYO}-{MES}-{DIA}-{NUM}', $order);
        $this->assertEquals('2021-11-23-777', $code, 'different-code');
    }

    public function testNombreMesNum()
    {
        $order = new PedidoCliente();
        $order->codejercicio = '2020';
        $order->codserie = 'A';
        $order->fecha = '23-03-2021';
        $order->numero = '123';

        $code = CodePatterns::trans('{SERIE}-{NOMBREMES}-{NUM}', $order);
        $this->assertEquals('A-Marzo-123', $code, 'different-code');
    }

    public function testDateNum()
    {
        $order = new PedidoCliente();
        $order->codejercicio = '2020';
        $order->codserie = 'A';
        $order->fecha = '02-03-2021';
        $order->hora = '11:22:33';
        $order->numero = '87';

        $code = CodePatterns::trans('{SERIE}{NUM}-{FECHA}-{HORA}', $order);
        $this->assertEquals('A87-02-03-2021-11:22:33', $code, 'different-code');
    }

    public function testDateTimeNum()
    {
        $order = new PedidoCliente();
        $order->codejercicio = '2020';
        $order->codserie = 'Z';
        $order->fecha = '07-07-2020';
        $order->hora = '15:16:17';
        $order->numero = '88';

        $code = CodePatterns::trans('{SERIE}{NUM}-{FECHAHORA}', $order);
        $this->assertEquals('Z88-07-07-2020 15:16:17', $code, 'different-code');
    }

    public function testFilters()
    {
        $order = new PedidoCliente();
        $order->codejercicio = '2020';
        $order->codserie = 'Z';
        $order->numero = '63';

        $code1 = CodePatterns::trans('pEd{EJE}{SERIE}{NUM}ccc|M', $order);
        $this->assertEquals('PED2020Z63CCC', $code1, 'upper-fail');

        $code2 = CodePatterns::trans('pEd{EJE}{SERIE}{NUM}ccc|m', $order);
        $this->assertEquals('ped2020z63ccc', $code2, 'lower-fail');

        $code3 = CodePatterns::trans('pEd{EJE}{SERIE}{NUM}ccc|P', $order);
        $this->assertEquals('PEd2020Z63ccc', $code3, 'uc-first-fail');
    }

    public function testNoBusinessDoc()
    {
        $product = new Producto();
        $product->actualizado = '03-04-2021 11:33:55';
        $product->codfamilia = 'J';
        $product->referencia = '999';

        $code = CodePatterns::trans('{SERIE}{NUM}-{FECHA}', $product, [
            'fecha' => 'actualizado',
            'numero' => 'referencia',
            'serie' => 'codfamilia'
        ]);
        $this->assertEquals('J999-03-04-2021', $code, 'different-code');
    }
}
