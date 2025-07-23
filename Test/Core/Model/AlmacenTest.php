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

use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class AlmacenTest extends TestCase
{
    use LogErrorsTrait;

    public function testDataInstalled(): void
    {
        // llamamos de forma estática
        $this->assertNotEmpty(Almacen::all(), 'warehouse-data-not-installed');

        // llamamos de forma dinámica
        $warehouse = new Almacen();
        $this->assertNotEmpty($warehouse->all(), 'warehouse-data-not-installed-from-csv');
    }

    public function testCreate(): void
    {
        $warehouse = new Almacen();
        $warehouse->codalmacen = 'Test';
        $warehouse->nombre = 'Test Warehouse';
        $this->assertTrue($warehouse->save(), 'warehouse-cant-save');
        $this->assertNotNull($warehouse->primaryColumnValue(), 'warehouse-not-stored');
        $this->assertTrue($warehouse->exists(), 'warehouse-cant-persist');
        $this->assertTrue($warehouse->delete(), 'warehouse-cant-delete');
    }

    public function testCreateWithNewCode(): void
    {
        $warehouse = new Almacen();
        $warehouse->nombre = 'Test Warehouse with new code';
        $this->assertTrue($warehouse->save(), 'warehouse-cant-save');
        $this->assertTrue($warehouse->delete(), 'warehouse-cant-delete');
    }

    public function testDeleteDefault(): void
    {
        $warehouse = new Almacen();
        foreach ($warehouse->all([], [], 0, 0) as $row) {
            if ($row->isDefault()) {
                $this->assertFalse($row->delete(), 'warehouse-default-cant-delete');
                break;
            }
        }
    }

    public function testPropertiesLength(): void
    {
        // Definir los campos a validar: campo => [longitud_máxima, longitud_invalida]
        $campos = [
            'apartado'   => [10, 11],
            'ciudad'     => [100, 101],
            'codpais'    => [20, 21],
            'codalmacen' => [4, 5],
            'codpostal'  => [10, 11],
            'direccion'  => [200, 201],
            'nombre'     => [100, 101],
            'provincia'  => [100, 101],
            'telefono'   => [30, 31],
        ];

        foreach ($campos as $campo => [$valido, $invalido]) {
            // Creamos un nuevo almacén
            $warehouse = new Almacen();

            // campo obligatorio (not null)
            $warehouse->nombre = 'Test Warehouse with new code';

            // Asignamos el valor inválido en el campo a probar
            $warehouse->{$campo} = Tools::randomString($invalido);
            $this->assertFalse($warehouse->save(), "can-save-almacen-bad-{$campo}");

            // Corregimos el campo y comprobamos que ahora sí se puede guardar
            $warehouse->{$campo} = Tools::randomString($valido);
            $this->assertTrue($warehouse->save(), "cannot-save-almacen-fixed-{$campo}");

            // Limpiar
            $this->assertTrue($warehouse->delete(), "cannot-delete-almacen-{$campo}");
        }
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
