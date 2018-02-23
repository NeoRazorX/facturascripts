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
 *  Generates delivery notes to suppliers with random data.
 *
 * @author Rafael San José <info@rsanjoseo.com>
 */
class AlbaranesProveedor extends AbstractRandomDocuments
{
    
    public function __construct()
    {
        parent::__construct(new Model\AlbaranProveedor());
    }
    
    public function generate($num = 50) {
        $alb=$this->model;
        $this->shuffle($proveedores, new Model\Proveedor());
        
        $recargo = false;
        if (mt_rand(0, 4) === 0) {
            $recargo = true;
        }

        $i=0;
        while ($i < $num) {
            $alb->clear();
            $this->randomizeDocument($alb);
            $eje = $this->ejercicio->getByFecha($alb->fecha);
            if ($eje) {
                $regimeniva = $this->randomizeDocumentCompra($alb, $eje, $proveedores, $i);
                if ($alb->save()) {
                    $this->randomLineas($alb, 'idalbaran', 'FacturaScripts\Dinamic\Model\LineaAlbaranProveedor', $regimeniva, $recargo, 1);
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