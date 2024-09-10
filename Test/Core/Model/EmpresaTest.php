<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use PHPUnit\Framework\TestCase;

final class EmpresaTest extends TestCase
{
    public function testCreate(): void
    {
        // creamos una empresa
        $company = new Empresa();
        $company->nombre = 'Empresa 1';
        $this->assertTrue($company->save(), 'company-cant-save');
        $this->assertNotNull($company->primaryColumnValue(), 'company-not-stored');
        $this->assertTrue($company->exists(), 'company-cant-persist');

        // comprobamos que se ha creado un almacén asociado
        $warehouse = new Almacen();
        $where = [new DataBaseWhere('idempresa', $company->idempresa)];
        $this->assertTrue($warehouse->loadFromCode('', $where), 'warehouse-not-found');

        // eliminamos
        $this->assertTrue($company->delete(), 'can-not-delete-company');

        // el almacén también se ha eliminado
        $this->assertFalse($warehouse->exists(), 'warehouse-still-exists');
    }
}
