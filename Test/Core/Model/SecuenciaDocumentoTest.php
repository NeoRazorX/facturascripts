<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\DataSrc\Ejercicios;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\PresupuestoCliente;
use FacturaScripts\Core\Model\SecuenciaDocumento;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class SecuenciaDocumentoTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function tearDownAfterClass(): void
    {
        // eliminamos todos los ejercicios
        $exercise = new Ejercicio();
        foreach ($exercise->all([], [], 0, 0) as $ex) {
            $ex->delete();
        }

        // eliminamos todos los clientes
        $customer = new Cliente();
        foreach ($customer->all([], [], 0, 0) as $cus) {
            $cus->delete();
        }
    }

    public function testCreate(): void
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

    public function testCantCreateEmptyOrInvalid(): void
    {
        // creamos una empresa
        $company = $this->getRandomCompany();
        $this->assertTrue($company->save(), 'company-cant-save');

        // creamos una serie
        $serie = $this->getRandomSerie();
        $this->assertTrue($serie->save(), 'serie-cant-save');

        // intentamos crear una secuencia sin patrón
        $sequence = new SecuenciaDocumento();
        $sequence->codserie = $serie->codserie;
        $sequence->idempresa = $company->idempresa;
        $sequence->longnumero = 6;
        $sequence->numero = 1;
        $sequence->patron = '';
        $sequence->tipodoc = 'FacturaCliente';
        $sequence->usarhuecos = false;
        $this->assertFalse($sequence->save(), 'document-sequence-cant-save');

        // intentamos asignar un patrón inválido
        $sequence->patron = 'TEST';
        $this->assertFalse($sequence->save(), 'document-sequence-cant-save');

        // eliminamos
        $this->assertTrue($serie->delete(), 'document-sequence-cant-delete');
        $this->assertTrue($company->delete(), 'document-sequence-cant-delete');
    }

    public function testEstimationCustomer(): void
    {
        // eliminamos todas las secuencias de PresupuestoCliente
        $sequence = new SecuenciaDocumento();
        $this->deleteSequences($sequence);

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
        $where = [new DataBaseWhere('tipodoc', 'PresupuestoCliente')];
        $sequence->loadFromCode('', $where);
        $this->assertTrue($sequence->exists(), 'document-sequence-not-created');

        // comprobamos que el número de inicio de la secuencia es 1
        $this->assertEquals(1, $sequence->inicio, 'document-sequence-start-not-one');

        // comprobamos que el siguiente número para la secuencia es 2
        $this->assertEquals(2, $sequence->numero, 'document-sequence-next-not-two');

        // eliminamos
        $this->assertTrue($doc->delete(), 'document-cant-delete');
        $this->assertTrue($sequence->delete(), 'document-sequence-cant-delete');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'address-cant-delete');
        $this->assertTrue($customer->delete(), 'customer-cant-delete');
    }

    public function testCustomNumber(): void
    {
        // eliminamos todas las secuencias de PresupuestoCliente
        $sequence = new SecuenciaDocumento();
        $this->deleteSequences($sequence);

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
        $this->assertEquals('PRE' . $doc->codejercicio . 'A000031', $doc->codigo, 'document-bad-codigo');

        // comprobamos que el siguiente número para la secuencia es 32
        $where = [new DataBaseWhere('tipodoc', 'PresupuestoCliente')];
        $sequence->loadFromCode('', $where);
        $this->assertEquals(32, $sequence->numero, 'document-sequence-next-not-thirty-two');

        // eliminamos
        $this->assertTrue($doc->delete(), 'document-cant-delete');
        $this->assertTrue($sequence->delete(), 'document-sequence-cant-delete');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'address-cant-delete');
        $this->assertTrue($customer->delete(), 'customer-cant-delete');
    }

    public function testCustomStartNumber(): void
    {
        // eliminamos todas las secuencias de PresupuestoCliente
        $sequence = new SecuenciaDocumento();
        $this->deleteSequences($sequence);

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
        $where = [new DataBaseWhere('tipodoc', 'PresupuestoCliente')];
        $sequence->loadFromCode('', $where);
        $this->assertEquals(22, $sequence->numero, 'document-sequence-next-not-twenty-two');

        // eliminamos
        $this->assertTrue($doc->delete(), 'document-cant-delete');
        $this->assertTrue($sequence->delete(), 'document-sequence-cant-delete');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'address-cant-delete');
        $this->assertTrue($customer->delete(), 'customer-cant-delete');
    }

    public function testPriority(): void
    {
        // eliminamos todas las secuencias de PresupuestoCliente
        $sequence = new SecuenciaDocumento();
        $this->deleteSequences($sequence);

        // creamos una secuencia que empiece en el 13, para todos los ejercicios
        $sequence->codserie = 'A';
        $sequence->idempresa = 1;
        $sequence->inicio = 13;
        $sequence->longnumero = 6;
        $sequence->numero = 13;
        $sequence->patron = 'PRE{EJE}{SERIE}{0NUM}';
        $sequence->tipodoc = 'PresupuestoCliente';
        $sequence->usarhuecos = false;
        $this->assertTrue($sequence->save(), 'document-sequence-cant-save');

        // obtenemos el ejercicio para el año 2015
        $exercise = new Ejercicio();
        $this->assertTrue($exercise->loadFromDate('01-01-2015'), 'exercise-cant-load');

        // creamos una secuencia que empiece en el 17, para el ejercicio 2018
        $sequence2 = new SecuenciaDocumento();
        $sequence2->codserie = 'A';
        $sequence2->idempresa = 1;
        $sequence2->inicio = 17;
        $sequence2->longnumero = 6;
        $sequence2->numero = 17;
        $sequence2->patron = 'PRE{EJE}{SERIE}{0NUM}';
        $sequence2->tipodoc = 'PresupuestoCliente';
        $sequence2->usarhuecos = false;
        $sequence2->codejercicio = $exercise->codejercicio;
        $this->assertTrue($sequence2->save(), 'document-sequence-cant-save');

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'customer-cant-save');

        // creamos un presupuesto y lo guardamos
        $doc = new PresupuestoCliente();
        $doc->setSubject($customer);
        $doc->setDate('05-01-2015', '00:00:00');
        $this->assertTrue($doc->save(), 'document-cant-save');

        // comprobamos que se le asigna el numero 17 al presupuesto
        $this->assertEquals(17, $doc->numero, 'document-not-seventeen');

        // eliminamos
        $this->assertTrue($doc->delete(), 'document-cant-delete');
        $this->assertTrue($sequence->delete(), 'document-sequence-cant-delete');
        $this->assertTrue($sequence2->delete(), 'document-sequence-cant-delete');
        $this->assertTrue($exercise->delete(), 'exercise-cant-delete');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'address-cant-delete');
        $this->assertTrue($customer->delete(), 'customer-cant-delete');
    }

    public function testFillGapsFirst(): void
    {
        // eliminamos todas las secuencias de PresupuestoCliente
        $sequence = new SecuenciaDocumento();
        $this->deleteSequences($sequence);

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
        $where = [new DataBaseWhere('tipodoc', 'PresupuestoCliente')];
        $sequence->loadFromCode('', $where);
        $this->assertEquals(8, $sequence->numero, 'document-sequence-next-not-eight');

        // eliminamos
        $this->assertTrue($doc->delete(), 'document-cant-delete');
        $this->assertTrue($sequence->delete(), 'document-sequence-cant-delete');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'address-cant-delete');
        $this->assertTrue($customer->delete(), 'customer-cant-delete');
    }

    public function testFillGapsMiddle(): void
    {
        // eliminamos todas las secuencias de PresupuestoCliente
        $sequence = new SecuenciaDocumento();
        $this->deleteSequences($sequence);

        // creamos una secuencia
        $sequence->codserie = 'A';
        $sequence->idempresa = 1;
        $sequence->inicio = 1;
        $sequence->longnumero = 6;
        $sequence->numero = 1;
        $sequence->patron = 'PRE{EJE}{SERIE}{0NUM}';
        $sequence->tipodoc = 'PresupuestoCliente';
        $sequence->usarhuecos = true;
        $this->assertTrue($sequence->save(), 'document-sequence-cant-save');

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'customer-cant-save');

        // creamos el primer presupuesto
        $doc = new PresupuestoCliente();
        $doc->setSubject($customer);
        $this->assertTrue($doc->save(), 'document-cant-save');
        $this->assertEquals(1, $doc->numero, 'document-not-one');

        // creamos el segundo presupuesto
        $doc2 = new PresupuestoCliente();
        $doc2->setSubject($customer);
        $this->assertTrue($doc2->save(), 'document-cant-save');
        $this->assertEquals(2, $doc2->numero, 'document-not-two');

        // creamos el tercer presupuesto
        $doc3 = new PresupuestoCliente();
        $doc3->setSubject($customer);
        $this->assertTrue($doc3->save(), 'document-cant-save');
        $this->assertEquals(3, $doc3->numero, 'document-not-three');

        // eliminamos el segundo presupuesto
        $this->assertTrue($doc2->delete(), 'document-cant-delete');

        // creamos un cuarto presupuesto
        $doc4 = new PresupuestoCliente();
        $doc4->setSubject($customer);
        $this->assertTrue($doc4->save(), 'document-cant-save');
        $this->assertEquals(2, $doc4->numero, 'document-not-two');

        // eliminamos
        $this->assertTrue($doc->delete(), 'document-cant-delete');
        $this->assertTrue($doc3->delete(), 'document-cant-delete');
        $this->assertTrue($doc4->delete(), 'document-cant-delete');
        $this->assertTrue($sequence->delete(), 'document-sequence-cant-delete');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'address-cant-delete');
        $this->assertTrue($customer->delete(), 'customer-cant-delete');
    }

    public function testFillGapsEnd(): void
    {
        // eliminamos todas las secuencias de PresupuestoCliente
        $sequence = new SecuenciaDocumento();
        $this->deleteSequences($sequence);

        // creamos una secuencia
        $sequence->codserie = 'A';
        $sequence->idempresa = 1;
        $sequence->inicio = 1;
        $sequence->longnumero = 6;
        $sequence->numero = 1;
        $sequence->patron = 'PRE{EJE}{SERIE}{0NUM}';
        $sequence->tipodoc = 'PresupuestoCliente';
        $sequence->usarhuecos = true;
        $this->assertTrue($sequence->save(), 'document-sequence-cant-save');

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'customer-cant-save');

        // creamos el primer presupuesto
        $doc = new PresupuestoCliente();
        $doc->setSubject($customer);
        $this->assertTrue($doc->save(), 'document-cant-save');
        $this->assertEquals(1, $doc->numero, 'document-not-one');

        // creamos el segundo presupuesto
        $doc2 = new PresupuestoCliente();
        $doc2->setSubject($customer);
        $this->assertTrue($doc2->save(), 'document-cant-save');
        $this->assertEquals(2, $doc2->numero, 'document-not-two');

        // eliminamos el segundo presupuesto
        $this->assertTrue($doc2->delete(), 'document-cant-delete');

        // creamos un tercer presupuesto
        $doc3 = new PresupuestoCliente();
        $doc3->setSubject($customer);
        $this->assertTrue($doc3->save(), 'document-cant-save');
        $this->assertEquals(2, $doc3->numero, 'document-not-two');

        // eliminamos
        $this->assertTrue($doc->delete(), 'document-cant-delete');
        $this->assertTrue($doc3->delete(), 'document-cant-delete');
        $this->assertTrue($sequence->delete(), 'document-sequence-cant-delete');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'address-cant-delete');
        $this->assertTrue($customer->delete(), 'customer-cant-delete');
    }

    public function testFillGapsExercises(): void
    {
        // eliminamos todas las secuencias de PresupuestoCliente
        $sequence = new SecuenciaDocumento();
        $this->deleteSequences($sequence);

        // creamos una secuencia que empiece en el 13, para todos los ejercicios
        $sequence->codserie = 'A';
        $sequence->idempresa = 1;
        $sequence->inicio = 13;
        $sequence->longnumero = 6;
        $sequence->numero = 13;
        $sequence->patron = 'PRE{EJE}{SERIE}{0NUM}';
        $sequence->tipodoc = 'PresupuestoCliente';
        $sequence->usarhuecos = true;
        $this->assertTrue($sequence->save(), 'document-sequence-cant-save');

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'customer-cant-save');

        // creamos un presupuesto el 28-01-2018
        $doc = new PresupuestoCliente();
        $doc->setSubject($customer);
        $doc->setDate('28-01-2018', '00:00:00');
        $this->assertTrue($doc->save(), 'document-cant-save');
        $this->assertEquals(13, $doc->numero, 'document-not-thirteen');

        // creamos un presupuesto el 12-02-2018
        $doc2 = new PresupuestoCliente();
        $doc2->setSubject($customer);
        $doc2->setDate('12-02-2018', '00:00:00');
        $this->assertTrue($doc2->save(), 'document-cant-save');
        $this->assertEquals(14, $doc2->numero, 'document-not-fourteen');

        // creamos un presupuesto el 17-02-2018
        $doc3 = new PresupuestoCliente();
        $doc3->setSubject($customer);
        $doc3->setDate('17-02-2018', '00:00:00');
        $this->assertTrue($doc3->save(), 'document-cant-save');
        $this->assertEquals(15, $doc3->numero, 'document-not-fifteen');

        // eliminamos el presupuesto 14
        $this->assertTrue($doc2->delete(), 'document-cant-delete');

        // creamos un presupuesto el 07-01-2019
        $doc4 = new PresupuestoCliente();
        $doc4->setSubject($customer);
        $doc4->setDate('07-01-2019', '00:00:00');
        $this->assertTrue($doc4->save(), 'document-cant-save');

        // comprobamos que se le ha asignado el número 16 al presupuesto y no el 14, ya que es otro ejercicio
        $this->assertEquals(16, $doc4->numero, 'document-not-sixteen');

        // creamos un presupuesto el 12-01-2019
        $doc5 = new PresupuestoCliente();
        $doc5->setSubject($customer);
        $doc5->setDate('12-01-2019', '00:00:00');
        $this->assertTrue($doc5->save(), 'document-cant-save');
        $this->assertEquals(17, $doc5->numero, 'document-not-seventeen');

        // eliminamos
        $this->assertTrue($doc5->delete(), 'document-cant-delete');
        $this->assertTrue($doc4->delete(), 'document-cant-delete');
        $this->assertTrue($doc3->delete(), 'document-cant-delete');
        $this->assertTrue($doc->delete(), 'document-cant-delete');
        $this->assertTrue($sequence->delete(), 'document-sequence-cant-delete');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'address-cant-delete');
        $this->assertTrue($customer->delete(), 'customer-cant-delete');
    }

    public function testWithExercisesAndWithout(): void
    {
        // eliminamos todas las secuencias de PresupuestoCliente
        $sequence = new SecuenciaDocumento();
        $this->deleteSequences($sequence);

        // creamos el ejercicio 2018
        $exercise = Ejercicios::get('2018');
        if (!$exercise->exists()) {
            $exercise->codejercicio = '2018';
            $exercise->idempresa = 1;
            $exercise->fechainicio = '01-01-2018';
            $exercise->fechafin = '31-12-2018';
            $this->assertTrue($exercise->save(), 'exercise-cant-save');
        }

        // creamos una secuencia que empiece en el 25, para el ejercicio 2018
        $sequence->codejercicio = $exercise->codejercicio;
        $sequence->codserie = 'A';
        $sequence->idempresa = 1;
        $sequence->inicio = 25;
        $sequence->longnumero = 6;
        $sequence->numero = 25;
        $sequence->patron = '{EJE2}-{SERIE}{0NUM}';
        $sequence->tipodoc = 'PresupuestoCliente';
        $sequence->usarhuecos = true;
        $this->assertTrue($sequence->save(), 'document-sequence-cant-save');

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'customer-cant-save');

        // creamos un presupuesto el 28-03-2018
        $doc = new PresupuestoCliente();
        $doc->setSubject($customer);
        $doc->setDate('28-03-2018', '00:00:00');
        $this->assertTrue($doc->save(), 'document-cant-save');
        $this->assertEquals(25, $doc->numero, 'document-not-thirteen');

        // creamos otra secuencia sin ejercicio
        $sequence2 = new SecuenciaDocumento();
        $sequence2->codserie = 'A';
        $sequence2->idempresa = 1;
        $sequence2->inicio = 1;
        $sequence2->longnumero = 6;
        $sequence2->numero = 1;
        $sequence2->patron = 'PRE{EJE2}{SERIE}{0NUM}';
        $sequence2->tipodoc = 'PresupuestoCliente';
        $sequence2->usarhuecos = true;
        $this->assertTrue($sequence2->save(), 'document-sequence-cant-save');

        // creamos un presupuesto hoy
        $doc2 = new PresupuestoCliente();
        $doc2->setSubject($customer);
        $doc2->setDate(Tools::date(), '00:00:00');
        $this->assertTrue($doc2->save(), 'document-cant-save');

        // debe haberle asignado el número 1 y mantenido la fecha de hoy
        $this->assertEquals(1, $doc2->numero, 'document-not-one');
        $this->assertEquals(Tools::date(), $doc2->fecha, 'document-bad-date');

        // eliminamos
        $this->assertTrue($doc2->delete(), 'document-cant-delete');
        $this->assertTrue($doc->delete(), 'document-cant-delete');
        $this->assertTrue($sequence->delete(), 'document-sequence-cant-delete');
        $this->assertTrue($sequence2->delete(), 'document-sequence-cant-delete');
        $this->assertTrue($exercise->delete(), 'exercise-cant-delete');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'address-cant-delete');
        $this->assertTrue($customer->delete(), 'customer-cant-delete');
    }

    public function testCodeTooLong(): void
    {
        // eliminamos todas las secuencias de PresupuestoCliente
        $sequence = new SecuenciaDocumento();
        $this->deleteSequences($sequence);

        // creamos una secuencia muy larga
        $sequence->codserie = 'A';
        $sequence->idempresa = 1;
        $sequence->inicio = 1;
        $sequence->longnumero = 20;
        $sequence->numero = 1;
        $sequence->patron = 'PRESUPUESTO-{EJE}-{SERIE}-{0NUM}-{FECHA}';
        $sequence->tipodoc = 'PresupuestoCliente';
        $sequence->usarhuecos = false;

        // comprobar que no se puede guardar
        $this->assertFalse($sequence->save());
    }

    private function deleteSequences(SecuenciaDocumento $sequence): void
    {
        $where = [new DataBaseWhere('tipodoc', 'PresupuestoCliente')];
        foreach ($sequence->all($where, [], 0, 0) as $sec) {
            $this->assertTrue($sec->delete(), 'document-sequence-cant-delete');
        }
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
