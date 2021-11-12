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

namespace FacturaScripts\Test\Core;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\Accounting\AccountingPlanImport;
use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\Cuenta;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Model\Proveedor;

trait BusinessDocsTrait
{
    protected static function getCustomer(string $name, string $cif): Cliente
    {
        $customer = new Cliente();
        $where = [new DataBaseWhere('nombre', $name)];
        $customer->loadFromCode('', $where);

        $customer->cifnif = $cif;
        $customer->nombre = $name;
        return $customer;
    }

    protected static function getProduct(string $ref): Producto
    {
        $product = new Producto();
        $where = [new DataBaseWhere('referencia', $ref)];
        $product->loadFromCode('', $where);

        $product->descripcion = $product->referencia = $ref;
        $product->nostock = false;
        $product->secompra = true;
        $product->sevende = true;
        return $product;
    }

    protected static function getSupplier(string $name, string $cif): Proveedor
    {
        $supplier = new Proveedor();
        $where = [new DataBaseWhere('nombre', $name)];
        $supplier->loadFromCode('', $where);

        $supplier->cifnif = $cif;
        $supplier->nombre = $name;
        return $supplier;
    }

    protected static function installAccountingPlan()
    {
        // Is there a default accounting plan?
        $filePath = FS_FOLDER . '/Core/Data/Codpais/ESP/defaultPlan.csv';
        if (false === file_exists($filePath)) {
            return;
        }

        // Does an accounting plan already exist?
        $cuenta = new Cuenta();
        if ($cuenta->count() > 0) {
            return;
        }

        $exerciseModel = new Ejercicio();
        foreach ($exerciseModel->all() as $exercise) {
            $planImport = new AccountingPlanImport();
            $planImport->importCSV($filePath, $exercise->codejercicio);
            return;
        }
    }

    protected static function setDefaultSettings()
    {
        $appSettings = new AppSettings();
        $fileContent = file_get_contents(FS_FOLDER . '/Core/Data/Codpais/ESP/default.json');
        $defaultValues = json_decode($fileContent, true) ?? [];
        foreach ($defaultValues as $group => $values) {
            foreach ($values as $key => $value) {
                $appSettings->set($group, $key, $value);
            }
        }

        $almacenModel = new Almacen();
        foreach ($almacenModel->all() as $almacen) {
            $appSettings->set('default', 'codalmacen', $almacen->codalmacen);
        }

        $appSettings->save();
    }
}