<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\EstadoDocumento;
use PHPUnit\Framework\TestCase;

final class EstadoDocumentoTest extends TestCase
{
    public function testCreateNewStatus()
    {
        $status = new EstadoDocumento();
        $status->nombre = 'Test';
        $status->tipodoc = 'PresupuestoProveedor';
        $this->assertTrue($status->save(), 'estado-documento-cant-save');
        $this->assertNotNull($status->primaryColumnValue(), 'estado-documento-pk-not-stored');
        $this->assertTrue($status->exists(), 'estado-documento-cant-persist');
        $this->assertTrue($status->delete(), 'estado-documento-cant-delete');
    }

    public function testCreateDefaultStatus()
    {
        // get the initial default count
        $status = new EstadoDocumento();
        $where = [
            new DataBaseWhere('predeterminado', true)
        ];
        $defaultsCount = $status->count($where);

        // create a new default status
        $name = 'Test default';
        $type = 'PresupuestoProveedor';
        $status->nombre = $name;
        $status->predeterminado = true;
        $status->tipodoc = $type;
        $this->assertTrue($status->save(), 'estado-documento-cant-save');

        // find the default on the database
        $where2 = [
            new DataBaseWhere('predeterminado', true),
            new DataBaseWhere('tipodoc', $type)
        ];
        $this->assertEquals(1, $status->count($where2), 'estado-documento-more-than-one-default');
        foreach ($status->all($where2) as $sta) {
            $this->assertEquals($status->idestado, $sta->idestado, 'estado-documento-not-the-right-default');
        }

        // check the defaults count did not change
        $this->assertEquals($defaultsCount, $status->count($where), 'estado-documento-defaults-count-changed');

        // remove the default status
        $this->assertTrue($status->delete(), 'estado-documento-cant-delete');

        // check the defaults count did not change
        $this->assertEquals($defaultsCount, $status->count($where), 'estado-documento-defaults-count-changed-2');
    }

    public function testCreateLockedStatus()
    {
        $status = new EstadoDocumento();
        $status->bloquear = true;
        $status->nombre = 'Test';
        $status->tipodoc = 'PresupuestoProveedor';
        $this->assertTrue($status->save(), 'estado-documento-cant-save');
        $this->assertFalse($status->delete(), 'estado-documento-lock-can-delete');

        // change properties
        $status->editable = false;
        $this->assertFalse($status->save(), 'estado-documento-lock-cant-save');

        // unlock
        $status->bloquear = false;
        $this->assertTrue($status->save(), 'estado-documento-cant-unlock');

        // delete
        $this->assertTrue($status->delete(), 'estado-documento-cant-delete');
    }
}
