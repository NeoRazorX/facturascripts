<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Model;

use FacturaScripts\Core\Model\FormatoDocumento;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class FormatoDocumentoTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public function testCreate(): void
    {
        // creamos una empresa
        $company = $this->getRandomCompany();
        $this->assertTrue($company->save());

        // creamos una serie
        $serie = $this->getRandomSerie();
        $this->assertTrue($serie->save());

        // creamos un formato de documento
        $formato = new FormatoDocumento();
        $formato->nombre = 'Test Formato';
        $formato->titulo = 'Test Título';
        $formato->texto = 'Test texto';
        $formato->tipodoc = 'FacturaCliente';
        $formato->idempresa = $company->idempresa;
        $formato->codserie = $serie->codserie;
        $this->assertTrue($formato->save());

        // comprobamos que existe en la base de datos
        $this->assertTrue($formato->exists());

        // comprobamos valores por defecto
        $this->assertTrue($formato->autoaplicar);
        $this->assertNotNull($formato->id);

        // eliminamos
        $this->assertTrue($formato->delete());
        $this->assertTrue($serie->delete());
        $this->assertTrue($company->delete());
    }

    public function testCreateHtml(): void
    {
        // creamos una empresa
        $company = $this->getRandomCompany();
        $this->assertTrue($company->save());

        // creamos un formato con html en los campos
        $formato = new FormatoDocumento();
        $formato->nombre = '<script/>';
        $formato->titulo = '<b>Test</b>';
        $formato->texto = '<p>Test</p>';
        $formato->tipodoc = 'FacturaCliente';
        $formato->idempresa = $company->idempresa;
        $this->assertTrue($formato->save());

        // comprobamos que el html ha sido escapado
        $this->assertEquals(Tools::noHtml('<script/>'), $formato->nombre);
        $this->assertEquals(Tools::noHtml('<b>Test</b>'), $formato->titulo);
        $this->assertEquals(Tools::noHtml('<p>Test</p>'), $formato->texto);

        // eliminamos
        $this->assertTrue($formato->delete());
        $this->assertTrue($company->delete());
    }

    public function testCreateWithoutName(): void
    {
        // creamos una empresa
        $company = $this->getRandomCompany();
        $this->assertTrue($company->save());

        // creamos un formato sin nombre pero con título
        $formato = new FormatoDocumento();
        $formato->titulo = 'Test Título Sin Nombre';
        $formato->texto = 'Test texto';
        $formato->tipodoc = 'FacturaCliente';
        $formato->idempresa = $company->idempresa;
        $this->assertTrue($formato->save());

        // comprobamos que el nombre se ha asignado automáticamente desde el título
        $this->assertEquals(Tools::noHtml('Test Título Sin Nombre'), $formato->nombre);

        // eliminamos
        $this->assertTrue($formato->delete());
        $this->assertTrue($company->delete());
    }

    public function testClear(): void
    {
        // creamos un formato y llamamos a clear
        $formato = new FormatoDocumento();
        $formato->clear();

        // comprobamos valores por defecto después del clear
        $this->assertTrue($formato->autoaplicar);
    }

    public function testDefaultCompany(): void
    {
        // creamos un formato sin especificar empresa
        $formato = new FormatoDocumento();
        $formato->nombre = 'Test Formato Default';
        $formato->titulo = 'Test Título Default';
        $formato->texto = 'Test texto';
        $formato->tipodoc = 'FacturaCliente';
        $this->assertTrue($formato->save());

        // comprobamos que se ha asignado la empresa por defecto
        $defaultCompanyId = Tools::settings('default', 'idempresa');
        $this->assertEquals($defaultCompanyId, $formato->idempresa);

        // eliminamos
        $this->assertTrue($formato->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
