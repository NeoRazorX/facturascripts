<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2016-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib\RandomDataGenerator;

use FacturaScripts\Core\Model;

/**
 *  Generates customer orders with random data.
 *
 * @author Rafael San José <info@rsanjoseo.com>
 */
class PedidosCliente extends AbstractRandomDocuments
{

    /**
     * PedidosCliente constructor.
     */
    public function __construct()
    {
        parent::__construct(new Model\PedidoCliente());
    }

    /**
     * Generate random data.
     *
     * @param int $num
     *
     * @return int
     */
    public function generate($num = 50)
    {
        $ped = $this->model;
        $this->shuffle($clientes, new Model\Cliente());

        $generated = 0;
        while ($generated < $num) {
            $ped->clear();
            $this->randomizeDocument($ped);
            $eje = $this->ejercicio->getByFecha($ped->fecha);
            if (false === $eje) {
                break;
            }

            $recargo = ($clientes[0]->recargo || mt_rand(0, 4) === 0);
            $regimeniva = $this->randomizeDocumentVenta($ped, $eje, $clientes, $generated);
            if (mt_rand(0, 3) == 0) {
                $ped->fechasalida = date('d-m-Y', strtotime($ped->fecha . ' +' . mt_rand(1, 3) . ' months'));
            }
            if ($ped->save()) {
                $this->randomLineas($ped, 'idpedido', 'FacturaScripts\Dinamic\Model\LineaPedidoCliente', $regimeniva, $recargo);
                ++$generated;
            } else {
                break;
            }
        }
        return $generated;
    }
}
