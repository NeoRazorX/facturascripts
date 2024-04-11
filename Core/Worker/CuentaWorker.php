<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Worker;

use FacturaScripts\Core\Model\WorkEvent;
use FacturaScripts\Core\Template\WorkerClass;
use FacturaScripts\Dinamic\Model\Cuenta;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class CuentaWorker extends WorkerClass
{
    public function run(WorkEvent $event): bool
    {
        // cargamos la cuenta
        $cuenta = new Cuenta();
        if (false === $cuenta->loadFromCode($event->param('idcuenta'))) {
            return $this->done();
        }

        // calculamos el debe y haber
        $cuenta->debe = 0.0;
        $cuenta->haber = 0.0;

        // obtenemos las cuentas hijas
        foreach ($cuenta->getChildren() as $child) {
            $cuenta->debe += $child->debe;
            $cuenta->haber += $child->haber;
        }

        // obtenemos las subcuentas
        foreach ($cuenta->getSubcuentas() as $subcuenta) {
            $cuenta->debe += $subcuenta->debe;
            $cuenta->haber += $subcuenta->haber;
        }

        // calculamos el saldo
        $saldo = $cuenta->debe - $cuenta->haber;
        if (abs($cuenta->saldo - $saldo) >= 0.01) {
            // actualizamos la cuenta
            $cuenta->debe = round($cuenta->debe, FS_NF0);
            $cuenta->haber = round($cuenta->haber, FS_NF0);
            $cuenta->saldo = round($cuenta->debe - $cuenta->haber, FS_NF0);
            $cuenta->save();
        }

        return $this->done();
    }
}
