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
 * Generate random accounting entries.
 *
 * @author Rafael San José <info@rsanjoseo.com>
 */
class Asientos extends AbstractRandomAccounting
{
    
    public function __construct()
    {
        parent::__construct(new Model\Asiento());
    }
    
    public function generate($num = 25) {
        $asiento=$this->model;
        $partida = new Model\Partida();
        $msubcuentas=new Model\Subcuenta();
        $subcuentas=$msubcuentas->all();
        for ($i = 0; $i < $num; ++$i) {
            $ejercicio=$this->getOneItem($this->ejercicios);

            $asiento->clear();
            $asiento->codejercicio = $ejercicio->codejercicio;
            $asiento->concepto = $this->descripcion();
            $asiento->fecha = date('d-m-Y', strtotime($ejercicio->fechainicio . ' +' . mt_rand(1, 360) . ' days'));
            $asiento->importe = $this->precio(-999, 150, 99999);
            if ($asiento->save()) {
                shuffle($subcuentas);
                $lineas = mt_rand(1, 20) * 2;
                $debe = true;
                for ($linea = 0; $linea < $lineas; ++$linea) {
                    $partida->clear();
                    $partida->idasiento = $asiento->idasiento;
                    $partida->idsubcuenta = $subcuentas[$linea]->idsubcuenta;
                    $partida->codsubcuenta = $subcuentas[$linea]->codsubcuenta;
                    $partida->concepto = $asiento->concepto;
                    if ($debe) {
                        $partida->debe = $asiento->importe;
                    } else {
                        $partida->haber = $asiento->importe;
                    }

                    if ($partida->save()) {
                        $debe = !$debe;
                    }
                }
                continue;
            }

            break;
        }

        return $i;
    }
            
}