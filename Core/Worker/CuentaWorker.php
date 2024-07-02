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
        // si es una subcuenta, su cuenta padre es idcuenta, para cuentas es parent_idcuenta
        $id = $event->param('parent_idcuenta') ?? $event->param('idcuenta');
        if (false === $cuenta->loadFromCode($id)) {
            return $this->done();
        }

        // calculamos el debe y haber
        $debe = 0.0;
        $haber = 0.0;

        // obtenemos las cuentas hijas
        foreach ($cuenta->getChildren() as $child) {
            $debe += $child->debe;
            $haber += $child->haber;
        }

        // obtenemos las subcuentas
        foreach ($cuenta->getSubcuentas() as $subcuenta) {
            $debe += $subcuenta->debe;
            $haber += $subcuenta->haber;
        }

        // calculamos el saldo
        $diffDebe = abs($cuenta->debe - $debe);
        $diffHaber = abs($cuenta->haber - $haber);
        $diffSaldo = abs($cuenta->saldo - ($debe - $haber));
        if ($diffDebe >= 0.01 || $diffHaber >= 0.01 || $diffSaldo >= 0.01) {
            // actualizamos la cuenta
            $cuenta->debe = round($debe, FS_NF0);
            $cuenta->haber = round($haber, FS_NF0);
            $cuenta->saldo = round($debe - $haber, FS_NF0);
            $cuenta->save();
        }

        return $this->done();
    }
}
