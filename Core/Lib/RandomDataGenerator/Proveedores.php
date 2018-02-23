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
 *  Generate random data for the suppliers (proveedores) file
 *
 * @author Rafael San José <info@rsanjoseo.com>
 */
class Proveedores extends AbstractRandomPeople
{
    
    public function __construct()
    {
        parent::__construct(new Model\Proveedor());
    }
    
    public function generate($num = 50) {
        
        $proveedor=$this->model;
        for ($i = 0; $i < $num; ++$i) {
            $proveedor->clear();
            $this->fillCliPro($proveedor);

            if (mt_rand(0, 9) == 0) {
                $proveedor->regimeniva = 'Exento';
            }

            $proveedor->codproveedor = $proveedor->newCode();
            if ($proveedor->save()) {
                /// añadimos direcciones
                $numDirs = mt_rand(0, 3);
                $this->direccionesProveedor($proveedor, $numDirs);

                /// Añadimos cuentas bancarias
                $numCuentas = mt_rand(0, 3);
                $this->cuentasBancoProveedor($proveedor, $numCuentas);
            } else {
                break;
            }
        }

        return $i;
    }
}