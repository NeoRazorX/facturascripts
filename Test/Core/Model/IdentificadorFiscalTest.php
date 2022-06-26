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
use FacturaScripts\Core\Model\IdentificadorFiscal;
use FacturaScripts\Test\Core\LogErrorsTrait;
use FacturaScripts\Test\Core\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class IdentificadorFiscalTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public function testDataInstalled()
    {
        $identificador = new IdentificadorFiscal();
        $this->assertNotEmpty($identificador->all(), 'identificador-fiscal-data-not-installed-from-csv');
    }

    public function testCreate()
    {
        // creamos un identificador fiscal
        $identificador = new IdentificadorFiscal();
        $identificador->tipoidfiscal = 'test';
        $this->assertTrue($identificador->save(), 'cant-save-identificador-fiscal');

        // lo borramos
        $this->assertTrue($identificador->delete(), 'cant-delete-identificador-fiscal');
    }

    public function testCantCreateWithoutTipo()
    {
        $identificador = new IdentificadorFiscal();
        $this->assertFalse($identificador->save(), 'cant-save-identificador-fiscal');
    }

    public function testHtmlOnFields()
    {
        // creamos un identificador fiscal con html
        $identificador = new IdentificadorFiscal();
        $identificador->tipoidfiscal = '<test>';
        $identificador->codeid = '<test>';
        $this->assertFalse($identificador->save(), 'can-save-with-html');
    }

    public function testValidateCIF()
    {
        $this->validate('T1234', 'P4698162G', 'CIF');
    }

    public function testValidateNIF()
    {
        $this->validate('T1234', '36155837K', 'NIF');
    }

    public function testValidateDNI()
    {
        $this->validate('T1234', '25296158E', 'DNI');
    }

    protected function validate(string $fiscalKo, string $fiscalOk, string $fiscalId)
    {
        $identificador = new IdentificadorFiscal();
        $where = [new DataBaseWhere('tipoidfiscal', $fiscalId)];
        $identificador->loadFromCode('', $where);
        $identificador->validar = true;
        $this->assertTrue($identificador->save(), 'identificador-fiscal-cant-save');

        // creamos el cliente
        $customer = $this->getRandomCustomer();
        $customer->tipoidfiscal = $fiscalId;
        $customer->cifnif = $fiscalKo;
        $this->assertFalse($customer->save(), 'can-save-customer-with-' . strtolower($fiscalId));
        $customer->cifnif = $fiscalOk;
        $this->assertTrue($customer->save(), 'cant-save-customer-with-' . strtolower($fiscalId));

        // creamos el contacto
        $contact = $this->getRandomContact();
        $contact->tipoidfiscal = $fiscalId;
        $contact->cifnif = $fiscalKo;
        $this->assertFalse($contact->save(), 'can-save-contact-with-' . strtolower($fiscalId));
        $contact->cifnif = $fiscalOk;
        $this->assertTrue($contact->save(), 'cant-save-contact-with-' . strtolower($fiscalId));

        // creamos la empresa
        $company = $this->getRandomCompany();
        $company->tipoidfiscal = $fiscalId;
        $company->cifnif = $fiscalKo;
        $this->assertFalse($company->save(), 'can-save-company-with-' . strtolower($fiscalId));
        $company->cifnif = $fiscalOk;
        $this->assertTrue($company->save(), 'cant-save-contact-with-' . strtolower($fiscalId));

        // creamos el proveedor
        $supplier = $this->getRandomSupplier();
        $supplier->tipoidfiscal = $fiscalId;
        $supplier->cifnif = $fiscalKo;
        $this->assertFalse($supplier->save(), 'can-save-supplier-with-' . strtolower($fiscalId));
        $supplier->cifnif = $fiscalOk;
        $this->assertTrue($supplier->save(), 'cant-save-supplier-with-' . strtolower($fiscalId));

        // dejamos el CIF como estaba
        $identificador->validar = false;
        $this->assertTrue($identificador->save(), 'identificador-fiscal-cant-save');

        // eliminamos
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
        $this->assertTrue($contact->delete(), 'cant-contact-customer');
        $this->assertTrue($company->delete(), 'cant-company-customer');
        $this->assertTrue($supplier->delete(), 'cant-supplier-customer');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
