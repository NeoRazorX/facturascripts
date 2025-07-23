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
use FacturaScripts\Core\Tools;
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

    public function testPropertiesLength(): void
    {
        // Definir los campos a validar: campo => [longitud_máxima, longitud_invalida]
        $campos = [
            'administrador'  => [100, 101],
            'apartado'       => [10, 11],
            'cifnif'         => [30, 31],
            'ciudad'         => [100, 101],
            'codpais'        => [20, 21],
            'codpostal'      => [10, 11],
            'direccion'      => [200, 201],
            'excepcioniva'   => [20, 21],
            //'email'          => [100, 101],
            'fax'            => [30, 31],
            'nombre'         => [100, 101],
            'nombrecorto'    => [32, 33],
            'provincia'      => [100, 101],
            'regimeniva'     => [20, 21],
            'telefono1'      => [30, 31],
            'telefono2'      => [30, 31],
            'tipoidfiscal'   => [25, 26],
            //'web'            => [100, 101],
        ];

        foreach ($campos as $campo => [$valido, $invalido]) {
            // Creamos un nuevo almacén
            $company = new Empresa();

            // campo obligatorio (not null)
            $company->nombre = 'Test';

            // Asignamos el valor inválido en el campo a probar
            $company->{$campo} = Tools::randomString($invalido);
            $this->assertFalse($company->save(), "can-save-empresa-bad-{$campo}");

            // Corregimos el campo y comprobamos que ahora sí se puede guardar
            $company->{$campo} = Tools::randomString($valido);
            $this->assertTrue($company->save(), "cannot-save-empresa-fixed-{$campo}");

            // Limpiar
            $this->assertTrue($company->delete(), "cannot-delete-empresa-{$campo}");
        }
    }
}
