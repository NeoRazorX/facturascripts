<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017       Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 * Copyright (C) 2017-2018  Carlos Garcia Gomez     <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Model\Empresa;
use FacturaScripts\Test\Core\CustomTest;

/**
 * @covers \Empresa
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
final class EmpresaTest extends CustomTest
{

    protected function setUp(): void
    {
        $this->model = new Empresa();
    }

    public function testCreate()
    {
        $company = new Empresa();
        $company->nombre = 'Empresa 1';
        $this->assertTrue($company->save(), 'company-cant-save');
        $this->assertNotNull($company->primaryColumnValue(), 'company-not-stored');
        $this->assertTrue($company->exists(), 'company-cant-persist');

        $warehouse = new Almacen();
        $where = [new DataBaseWhere('idempresa', $company->idempresa)];
        $warehouse->loadFromCode('', $where);
        $this->assertTrue($warehouse->exists(), 'warehouse-cant-persist');
        $this->assertEquals($warehouse->idempresa, $company->idempresa, 'company-warehouse-bad-idempresa');

        // eliminamos
        $this->assertTrue($company->delete(), 'can-not-delete-company');
    }
}
