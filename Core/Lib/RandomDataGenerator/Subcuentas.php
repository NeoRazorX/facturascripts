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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Model;

/**
 * Generates accounting sub-accounts at random.
 * It may be better to incorporate the accounting plan of your country.
 *
 * @author Rafael San José <info@rsanjoseo.com>
 */
class Subcuentas extends AbstractRandomAccounting
{
    
    public function __construct()
    {
        parent::__construct(new Model\Subcuenta());
    }
    
    public function generate($num = 50) {
        $subcuenta=$this->model;
        $mcuentas=new Model\Cuenta();
        $cuentas=$mcuentas->all();
        for ($i = 0; $i < $num; ++$i) {
            $cuenta=$this->getOneItem($cuentas);
            $subcuenta->clear();
            $subcuenta->codcuenta = $cuenta->codcuenta;
            $subcuenta->coddivisa = AppSettings::get('default', 'coddivisa');
            $subcuenta->codejercicio = $cuenta->codejercicio;
            $subcuenta->codsubcuenta = $cuenta->codcuenta . mt_rand(0, 9999);
            $subcuenta->descripcion = $this->descripcion();
            $subcuenta->idcuenta = $cuenta->idcuenta;
            if (!$subcuenta->save()) {
                break;
            }
        }

        return $i;
    }
            
}