<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\Accounting;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Partida;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Reclasifica partidas: mueve una o varias partidas a otra subcuenta del
 * mismo ejercicio, opcionalmente reescribiendo el concepto, y actualiza los
 * saldos de las subcuentas de origen y de destino.
 *
 * Evita tener que borrar y recrear asientos (o editar la base de datos a
 * mano) cuando hay que corregir la subcuenta de apuntes ya contabilizados.
 *
 * @author Santiago Lopez <santilh@gmail.com>
 */
class PartidaReclassifier
{
    /**
     * Mueve las partidas indicadas a la subcuenta de destino. Todas las
     * partidas deben pertenecer al mismo ejercicio que la subcuenta de
     * destino, el ejercicio debe estar abierto y los asientos deben ser
     * editables. La operación es transaccional: o se mueven todas o ninguna.
     *
     * @param int[] $idpartidas
     * @param Subcuenta $newSubaccount
     * @param string $newConcept concepto nuevo para las líneas movidas (vacío = mantener)
     *
     * @return bool
     */
    public static function move(array $idpartidas, Subcuenta $newSubaccount, string $newConcept = ''): bool
    {
        if (empty($idpartidas)) {
            Tools::log()->warning('no-selected-item');
            return false;
        }

        if (false === $newSubaccount->exists()) {
            Tools::log()->warning('subaccount-not-found', ['%subAccountCode%' => $newSubaccount->codsubcuenta]);
            return false;
        }

        $db = new DataBase();
        $db->beginTransaction();

        // subcuentas de origen, para actualizar sus saldos al final
        $oldSubaccounts = [];

        foreach ($idpartidas as $idpartida) {
            $partida = new Partida();
            if (false === $partida->load($idpartida)) {
                Tools::log()->warning('record-not-found');
                $db->rollback();
                return false;
            }

            // la subcuenta de destino debe ser del mismo ejercicio que el asiento
            $asiento = $partida->getAccountingEntry();
            if ($asiento->codejercicio !== $newSubaccount->codejercicio) {
                Tools::log()->warning('cant-change-accounting-entry-exercise');
                $db->rollback();
                return false;
            }

            $oldSubaccounts[$partida->idsubcuenta] = $partida->idsubcuenta;

            $partida->setAccount($newSubaccount);
            if ($newConcept !== '') {
                $partida->concepto = $newConcept;
            }

            // save() comprueba que el ejercicio está abierto y el asiento es editable
            if (false === $partida->save()) {
                $db->rollback();
                return false;
            }
        }

        $db->commit();

        // actualizamos los saldos de las subcuentas de origen y destino
        foreach ($oldSubaccounts as $idsubcuenta) {
            $oldSubaccount = new Subcuenta();
            if ($oldSubaccount->load($idsubcuenta)) {
                $oldSubaccount->updateBalance();
            }
        }
        $newSubaccount->updateBalance();

        return true;
    }
}
