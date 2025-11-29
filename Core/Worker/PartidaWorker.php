<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\WorkEvent;
use FacturaScripts\Core\Template\WorkerClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Join\PartidaAsiento;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class PartidaWorker extends WorkerClass
{
    public function run(WorkEvent $event): bool
    {
        // detenemos la generación de eventos
        $this->preventNewEvents(['Model.Partida.Save']);

        // cargamos la subcuenta
        $subcuenta = new Subcuenta();
        if (false === $subcuenta->load($event->param('idsubcuenta'))) {
            return $this->done();
        }

        // inicializamos las variables
        $debe = 0.0;
        $haber = 0.0;
        $saldo = 0.0;

        // leemos las partidas de la subcuenta
        $partidaAsientoModel = new PartidaAsiento();
        $where = [new DataBaseWhere('idsubcuenta', $subcuenta->idsubcuenta)];
        $orderBy = ['fecha' => 'ASC', 'numero' => 'ASC', 'idpartida' => 'ASC'];
        $limit = 1000;
        $offset = 0;
        $partidasAsientos = $partidaAsientoModel->all($where, $orderBy, $offset, $limit);

        while (count($partidasAsientos) > 0) {
            foreach ($partidasAsientos as $line) {
                // sumamos el debe y el haber
                $debe += $line->debe;
                $haber += $line->haber;

                // calculamos y comprobamos el saldo
                $saldo += $line->debe - $line->haber;
                if (abs($line->saldo - $saldo) < 0.009) {
                    continue;
                }

                // actualizamos la partida
                $partida = $line->getPartida();
                $partida->saldo = Tools::round($saldo);
                $partida->disableAdditionalTest(true);
                $partida->save();
            }

            // seguimos leyendo las partidas
            $offset += $limit;
            $partidasAsientos = $partidaAsientoModel->all($where, $orderBy, $offset, $limit);
        }

        // actualizamos la subcuenta
        $diffDebe = abs($subcuenta->debe - $debe);
        $diffHaber = abs($subcuenta->haber - $haber);
        $diffSaldo = abs($subcuenta->saldo - $saldo);
        if ($diffDebe >= 0.009 || $diffHaber >= 0.009 || $diffSaldo >= 0.009) {
            $subcuenta->debe = Tools::round($debe);
            $subcuenta->haber = Tools::round($haber);
            $subcuenta->saldo = Tools::round($saldo);
            $subcuenta->disableAdditionalTest(true);
            $subcuenta->save();
        }

        return $this->done();
    }
}
