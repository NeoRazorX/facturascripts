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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\WorkEvent;
use FacturaScripts\Core\Template\WorkerClass;
use FacturaScripts\Dinamic\Model\Join\PartidaAsiento;
use FacturaScripts\Dinamic\Model\Partida;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class PartidaWorker extends WorkerClass
{
    public function run(WorkEvent $event): bool
    {
        // cargamos la subcuenta
        $subcuentaModel = new Subcuenta();
        if (false === $subcuentaModel->loadFromCode($event['idsubcuenta'])) {
            return $this->stopPropagation();
        }

        // inicializamos las variables
        $debe = 0.0;
        $haber = 0.0;
        $saldo = 0.0;

        // leemos las partidas de la subcuenta
        $partidaAsientoModel = new PartidaAsiento();
        $where = [
            new DataBaseWhere('idsubcuenta', $subcuentaModel->idsubcuenta)
        ];
        $orderBy = ['fecha' => 'ASC', 'numero' => 'ASC', 'idpartida' => 'ASC'];
        $limit = 1000;
        $offset = 0;
        $partidas = $partidaAsientoModel->all($where, $orderBy, $offset, $limit);

        while (count($partidas) > 0) {
            foreach ($partidas as $partidaAsiento) {
                // sumamos el debe y el haber
                $debe += $partidaAsiento->debe;
                $haber += $partidaAsiento->haber;

                // calculamos y comprobamos el saldo
                $saldo += round($partidaAsiento->debe - $partidaAsiento->haber, FS_NF0);
                if ($partidaAsiento->saldo == $saldo) {
                    continue;
                }

                // cargamos la partida
                $partidaModel = new Partida();
                if (false === $partidaModel->loadFromCode($partidaAsiento->idpartida)) {
                    continue;
                }

                // actualizamos la partida
                $partidaModel->saldo = $saldo;
                $partidaModel->save();
            }

            $offset += $limit;
            $partidas = $partidaAsientoModel->all($where, $orderBy, $offset, $limit);
        }

        // actualizamos la subcuenta
        $subcuentaModel->debe = $debe;
        $subcuentaModel->haber = $haber;
        $subcuentaModel->saldo = $saldo;
        $subcuentaModel->save();

        return $this->done();
    }
}
