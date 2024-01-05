<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
        $cuentaModel = new Cuenta();
        if (false === $cuentaModel->loadFromCode($event['idcuenta'])) {
            return $this->stopPropagation();
        }

        // calculamos el debe y haber
        $cuentaModel->debe = 0.0;
        $cuentaModel->haber = 0.0;

        // obtenemos las cuentas hijas
        foreach ($cuentaModel->getChildren() as $child) {
            $cuentaModel->debe += $child->debe;
            $cuentaModel->haber += $child->haber;
        }

        // obtenemos las subcuentas
        foreach ($cuentaModel->getSubcuentas() as $subcuenta) {
            $cuentaModel->debe += $subcuenta->debe;
            $cuentaModel->haber += $subcuenta->haber;
        }

        // actualizamos la cuenta
        $cuentaModel->debe = round($cuentaModel->debe, 2);
        $cuentaModel->haber = round($cuentaModel->haber, 2);
        $cuentaModel->saldo = round($cuentaModel->debe - $cuentaModel->haber, 2);
        $cuentaModel->save();

        return $this->done();
    }
}
