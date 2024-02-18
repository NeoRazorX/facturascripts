<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class EstadoDocumentoTest extends TestCase
{
    use LogErrorsTrait;

    public function testDataInstalled(): void
    {
        $status = new EstadoDocumento();
        $this->assertNotEmpty($status->all(), 'estado-documento-data-not-installed-from-csv');
    }

    public function testCreateNewStatus(): void
    {
        $status = new EstadoDocumento();
        $status->nombre = 'Test';
        $status->tipodoc = 'PresupuestoProveedor';
        $this->assertTrue($status->save(), 'estado-documento-cant-save');
        $this->assertNotNull($status->primaryColumnValue(), 'estado-documento-pk-not-stored');
        $this->assertTrue($status->exists(), 'estado-documento-cant-persist');
        $this->assertTrue($status->delete(), 'estado-documento-cant-delete');
    }

    public function testHtmlOnFields(): void
    {
        // creamos un estado con html en los campos
        $status = new EstadoDocumento();
        $status->nombre = '<test>';
        $status->tipodoc = '<test>';
        $status->generadoc = '<test>';
        $status->icon = '<test>';
        $this->assertTrue($status->save(), 'estado-documento-cant-save');

        // comprobamos que el html se ha escapado
        $this->assertEquals('&lt;test&gt;', $status->nombre, 'estado-documento-html-not-escaped');
        $this->assertEquals('&lt;test&gt;', $status->tipodoc, 'estado-documento-html-not-escaped');
        $this->assertEquals('&lt;test&gt;', $status->generadoc, 'estado-documento-html-not-escaped');
        $this->assertEquals('&lt;test&gt;', $status->icon, 'estado-documento-html-not-escaped');

        // eliminamos
        $this->assertTrue($status->delete(), 'estado-documento-cant-delete');
    }

    public function testCreateDefaultStatus(): void
    {
        // get the initial default count
        $status = new EstadoDocumento();
        $where = [new DataBaseWhere('predeterminado', true)];
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

    public function testCreateLockedStatus(): void
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

    public function testStatusCanNotHaveGenerationAndEditable(): void
    {
        $status = new EstadoDocumento();
        $status->editable = true;
        $status->generadoc = 'PedidoProveedor';
        $status->nombre = 'Generate';
        $status->tipodoc = 'PresupuestoCliente';
        $this->assertTrue($status->save(), 'estado-documento-cant-save');
        $this->assertFalse($status->editable, 'estado-documento-must-be-not-editable');

        // delete
        $this->assertTrue($status->delete(), 'estado-documento-cant-delete');
    }

    public function testCanNotCreateInvoicesWithGeneration()
    {
        $status = new EstadoDocumento();
        $status->generadoc = 'PedidoCliente';
        $status->nombre = 'Generate';
        $status->tipodoc = 'FacturaCliente';
        $this->assertFalse($status->save(), 'invalid-estado-documento-for-sales-invoice-can-save');

        $status->generadoc = 'PedidoProveedor';
        $status->tipodoc = 'FacturaProveedor';
        $this->assertFalse($status->save(), 'invalid-estado-documento-for-purchase-invoice-can-save');
    }

    /**
     * No permitir crear estados predeterminados y no editables.
     */
    public function testNonEditableDefaultNotAllowed(): void
    {
        // Crear nuevo estado predeterminado y no editable
        $status = new EstadoDocumento();
        $status->nombre = 'Test default';
        $status->predeterminado = true;
        $status->editable = false;
        $status->tipodoc = 'PresupuestoProveedor';

        // Comprobamos que no se pueda guardar un estado que sea predeterminado y no editable.
        $this->assertFalse($status->save());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
