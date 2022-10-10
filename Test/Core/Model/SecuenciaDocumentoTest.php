<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\PresupuestoCliente;
use FacturaScripts\Core\Model\SecuenciaDocumento;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class SecuenciaDocumentoTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public function testCreate()
    {
        // creamos una empresa
        $company = $this->getRandomCompany();
        $this->assertTrue($company->save(), 'company-cant-save');

        // creamos una serie
        $serie = $this->getRandomSerie();
        $this->assertTrue($serie->save(), 'serie-cant-save');

        // creamos una secuencia
        $sequence = new SecuenciaDocumento();
        $sequence->codserie = $serie->codserie;
        $sequence->idempresa = $company->idempresa;
        $sequence->longnumero = 6;
        $sequence->numero = 1;
        $sequence->patron = 'FAC{EJE}{SERIE}{NUM}';
        $sequence->tipodoc = 'FacturaCliente';
        $sequence->usarhuecos = false;
        $this->assertTrue($sequence->save(), 'document-sequence-cant-save');
        $this->assertNotNull($sequence->primaryColumnValue(), 'document-sequence-not-stored');
        $this->assertTrue($sequence->exists(), 'document-sequence-cant-persist');

        // eliminamos
        $this->assertTrue($sequence->delete(), 'document-sequence-cant-delete');
        $this->assertTrue($serie->delete(), 'document-sequence-cant-delete');
        $this->assertTrue($company->delete(), 'document-sequence-cant-delete');
    }

    public function testEstimationCustomer()
    {
        // eliminamos todas las secuencias de PresupuestoCliente
        $sequence = new SecuenciaDocumento();
        $where = [new DataBaseWhere('tipodoc', 'PresupuestoCliente')];
        foreach ($sequence->all($where, [], 0, 0) as $sec) {
            $this->assertTrue($sec->delete(), 'document-sequence-cant-delete');
        }

        // comprobamos que no hay secuencias de PresupuestoCliente
        $this->assertEmpty($sequence->all($where, [], 0, 0), 'document-sequence-not-empty');

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'customer-cant-save');

        // creamos un presupuesto y lo guardamos
        $doc = new PresupuestoCliente();
        $doc->setSubject($customer);
        $this->assertTrue($doc->save(), 'document-cant-save');

        // comprobamos que se le asigna el numero 1 al presupuesto
        $this->assertEquals(1, $doc->numero, 'document-not-one');

        // comprobamos que se creó la secuencia
        $sequence->loadFromCode('', $where);
        $this->assertTrue($sequence->exists(), 'document-sequence-not-created');

        // comprobamos que el número de inicio de la secuencia es 1
        $this->assertEquals(1, $sequence->inicio, 'document-sequence-start-not-one');

        // comprobamos que el siguiente número para la secuencia es 2
        $this->assertEquals(2, $sequence->numero, 'document-sequence-next-not-two');

        // eliminamos
        $this->assertTrue($doc->delete(), 'document-cant-delete');
        $this->assertTrue($customer->delete(), 'customer-cant-delete');
        $this->assertTrue($sequence->delete(), 'document-sequence-cant-delete');
    }

    public function testCustomNumber()
    {
        // eliminamos todas las secuencias de PresupuestoCliente
        $sequence = new SecuenciaDocumento();
        $where = [new DataBaseWhere('tipodoc', 'PresupuestoCliente')];
        foreach ($sequence->all($where, [], 0, 0) as $sec) {
            $this->assertTrue($sec->delete(), 'document-sequence-cant-delete');
        }

        // comprobamos que no hay secuencias de PresupuestoCliente
        $this->assertEmpty($sequence->all($where, [], 0, 0), 'document-sequence-not-empty');

        // creamos una secuencia
        $sequence->codserie = 'A';
        $sequence->idempresa = 1;
        $sequence->inicio = 31;
        $sequence->longnumero = 6;
        $sequence->numero = 31;
        $sequence->patron = 'PRE{EJE}{SERIE}{0NUM}';
        $sequence->tipodoc = 'PresupuestoCliente';
        $sequence->usarhuecos = false;
        $this->assertTrue($sequence->save(), 'document-sequence-cant-save');

        // comprobamos que se creó la secuencia
        $this->assertTrue($sequence->exists(), 'document-sequence-not-created');

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'customer-cant-save');

        // creamos un presupuesto y lo guardamos
        $doc = new PresupuestoCliente();
        $doc->setSubject($customer);
        $this->assertTrue($doc->save(), 'document-cant-save');

        // comprobamos que se le asigna el numero 31 al presupuesto
        $this->assertEquals(31, $doc->numero, 'document-not-thirty-one');

        // comprobamos que el código del presupuesto es PRE{EJE}{SERIE}{0NUM}
        $this->assertEquals('PRE' . date('Y') . 'A000031', $doc->codigo, 'document-bad-codigo');

        // comprobamos que el siguiente número para la secuencia es 32
        $sequence->loadFromCode('', $where);
        $this->assertEquals(32, $sequence->numero, 'document-sequence-next-not-thirty-two');

        // eliminamos
        $this->assertTrue($doc->delete(), 'document-cant-delete');
        $this->assertTrue($customer->delete(), 'customer-cant-delete');
        $this->assertTrue($sequence->delete(), 'document-sequence-cant-delete');
    }

    public function testCustomStartNumber()
    {
        // eliminamos todas las secuencias de PresupuestoCliente
        $sequence = new SecuenciaDocumento();
        $where = [new DataBaseWhere('tipodoc', 'PresupuestoCliente')];
        foreach ($sequence->all($where, [], 0, 0) as $sec) {
            $this->assertTrue($sec->delete(), 'document-sequence-cant-delete');
        }

        // comprobamos que no hay secuencias de PresupuestoCliente
        $this->assertEmpty($sequence->all($where, [], 0, 0), 'document-sequence-not-empty');

        // creamos una secuencia
        $sequence->codserie = 'A';
        $sequence->idempresa = 1;
        $sequence->inicio = 11;
        $sequence->longnumero = 6;
        $sequence->numero = 21;
        $sequence->patron = 'PRE{EJE}{SERIE}{0NUM}';
        $sequence->tipodoc = 'PresupuestoCliente';
        $sequence->usarhuecos = false;
        $this->assertTrue($sequence->save(), 'document-sequence-cant-save');

        // comprobamos que se creó la secuencia
        $this->assertTrue($sequence->exists(), 'document-sequence-not-created');

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'customer-cant-save');

        // creamos un presupuesto y lo guardamos
        $doc = new PresupuestoCliente();
        $doc->setSubject($customer);
        $this->assertTrue($doc->save(), 'document-cant-save');

        // comprobamos que se le asigna el numero 21 al presupuesto
        $this->assertEquals(21, $doc->numero, 'document-not-twenty-one');

        // comprobamos que el siguiente número para la secuencia es 22
        $sequence->loadFromCode('', $where);
        $this->assertEquals(22, $sequence->numero, 'document-sequence-next-not-twenty-two');

        // eliminamos
        $this->assertTrue($doc->delete(), 'document-cant-delete');
        $this->assertTrue($customer->delete(), 'customer-cant-delete');
        $this->assertTrue($sequence->delete(), 'document-sequence-cant-delete');
    }

    public function testFillGaps()
    {
        // eliminamos todas las secuencias de PresupuestoCliente
        $sequence = new SecuenciaDocumento();
        $where = [new DataBaseWhere('tipodoc', 'PresupuestoCliente')];
        foreach ($sequence->all($where, [], 0, 0) as $sec) {
            $this->assertTrue($sec->delete(), 'document-sequence-cant-delete');
        }

        // comprobamos que no hay secuencias de PresupuestoCliente
        $this->assertEmpty($sequence->all($where, [], 0, 0), 'document-sequence-not-empty');

        // creamos una secuencia
        $sequence->codserie = 'A';
        $sequence->idempresa = 1;
        $sequence->inicio = 7;
        $sequence->longnumero = 6;
        $sequence->numero = 14;
        $sequence->patron = 'PRE{EJE}{SERIE}{0NUM}';
        $sequence->tipodoc = 'PresupuestoCliente';
        $sequence->usarhuecos = true;
        $this->assertTrue($sequence->save(), 'document-sequence-cant-save');

        // comprobamos que se creó la secuencia
        $this->assertTrue($sequence->exists(), 'document-sequence-not-created');

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'customer-cant-save');

        // creamos un presupuesto y lo guardamos
        $doc = new PresupuestoCliente();
        $doc->setSubject($customer);
        $this->assertTrue($doc->save(), 'document-cant-save');

        // comprobamos que se le asigna el numero 7 al presupuesto
        $this->assertEquals(7, $doc->numero, 'document-not-seven');

        // comprobamos que el siguiente número para la secuencia es 8
        $sequence->loadFromCode('', $where);
        $this->assertEquals(8, $sequence->numero, 'document-sequence-next-not-eight');

        // eliminamos
        $this->assertTrue($doc->delete(), 'document-cant-delete');
        $this->assertTrue($customer->delete(), 'customer-cant-delete');
        $this->assertTrue($sequence->delete(), 'document-sequence-cant-delete');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
