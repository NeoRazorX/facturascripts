<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Traits;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Ejercicios;
use FacturaScripts\Core\Lib\Accounting\AccountingPlanImport;
use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Model\Cuenta;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\RegularizacionImpuesto;
use FacturaScripts\Core\Tools;

trait DefaultSettingsTrait
{
    protected static function installAccountingPlan(): void
    {
        // ¿Existe el archivo del plan contable?
        $filePath = FS_FOLDER . '/Core/Data/Codpais/ESP/defaultPlan.csv';
        if (false === file_exists($filePath)) {
            return;
        }

        // recorremos todos los ejercicios
        $cuenta = new Cuenta();
        Ejercicios::clear();
        foreach (Ejercicios::all() as $exercise) {
            // si está cerrado, lo abrimos
            if (false === $exercise->isOpened()) {
                $exercise->estado = Ejercicio::EXERCISE_STATUS_OPEN;
                $exercise->save();
            }

            // si el ejercicio no tiene 10 dígitos en las subcuentas, lo eliminamos
            if ($exercise->longsubcuenta != 10) {
                $exercise->delete();
                continue;
            }

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

    protected static function removeTaxRegularization(): void
    {
        $regularizationModel = new RegularizacionImpuesto();
        foreach ($regularizationModel->all() as $regularization) {
            $regularization->delete();
        }
    }

    protected static function setDefaultSettings(): void
    {
        $fileContent = file_get_contents(FS_FOLDER . '/Core/Data/Codpais/ESP/default.json');
        $defaultValues = json_decode($fileContent, true) ?? [];
        foreach ($defaultValues as $group => $values) {
            foreach ($values as $key => $value) {
                Tools::settingsSet($group, $key, $value);
            }
        }

        $almacenModel = new Almacen();
        $where = [new DataBaseWhere('idempresa', Tools::settings('default', 'idempresa', 1))];
        foreach ($almacenModel->all($where) as $almacen) {
            Tools::settingsSet('default', 'codalmacen', $almacen->codalmacen);
        }

        Tools::settingsSave();
    }
}
