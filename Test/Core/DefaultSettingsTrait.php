<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\Cuenta;
use FacturaScripts\Core\Model\Ejercicio;

trait DefaultSettingsTrait
{
    protected static function installAccountingPlan()
    {
        // ¿Existe el archivo del plan contable?
        $filePath = FS_FOLDER . '/Core/Data/Codpais/ESP/defaultPlan.csv';
        if (false === file_exists($filePath)) {
            return;
        }

        // recorremos todos los ejercicios
        $cuenta = new Cuenta();
        $exerciseModel = new Ejercicio();
        foreach ($exerciseModel->all() as $exercise) {
            $where = [new DataBaseWhere('codejercicio', $exercise->codejercicio)];
            if ($cuenta->count($where) > 0) {
                // ya tiene plan contable
                continue;
            }

            // importamos el plan contable en aquellos que no tengan
            $planImport = new AccountingPlanImport();
            $planImport->importCSV($filePath, $exercise->codejercicio);
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