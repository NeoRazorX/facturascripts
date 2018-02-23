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
 *  Generates delivery notes to customers with random data.
 *
 * @author Rafael San José <info@rsanjoseo.com>
 */
class AlbaranesCliente extends AbstractRandomDocuments
{
    
    public function __construct()
    {
        parent::__construct(new Model\AlbaranCliente());
    }
    
    public function generate($num = 50) {
        $alb=$this->model;

        $this->shuffle($clientes, new Model\Cliente());

        $recargo = false;
        if ($clientes[0]->recargo || mt_rand(0, 4) === 0) {
            $recargo = true;
        }

        $i=0;
        while ($i < $num) {
            $alb->clear();
            $this->randomizeDocument($alb);
            $eje = $this->ejercicio->getByFecha($alb->fecha);
            if ($eje) {
                $regimeniva = $this->randomizeDocumentVenta($alb, $eje, $clientes, $i);
                if ($alb->save()) {
                    $this->randomLineas($alb, 'idalbaran', 'FacturaScripts\Dinamic\Model\LineaAlbaranCliente', $regimeniva, $recargo, -1);
                    ++$i;
                } else {
                    break;
                }
            } else {
                break;
            }
        }

        return $i;
    }
}