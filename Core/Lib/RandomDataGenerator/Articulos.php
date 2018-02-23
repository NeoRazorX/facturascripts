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
 * Generate random data for the products (Articulos) file
 *
 * @author Rafael San José <info@rsanjoseo.com>
 */
class Articulos extends AbstractRandom
{
    
    public function __construct()
    {
        parent::__construct(new Model\Articulo());
    }
    
    public function generate($num = 50) {
        
        $fab = new Model\Fabricante();
        $fabricantes = $fab->all();

        $fam = new Model\Familia();
        $familias = $fam->all();

        $imp = new Model\Impuesto();
        $impuestos = $imp->all();

        $alm = new Model\Almacen();
        $almacenes = $alm->all();

        $art = $this->model;
        for ($i = 0; $i < $num; ++$i) {
            $art->clear();
            $art->descripcion = $this->descripcion();
            $art->codimpuesto = $this->getOneItem($impuestos)->codimpuesto;
            $art->setPvpIva($this->precio(1, 49, 699));
            $art->costemedio = $art->preciocoste = $this->cantidad(0, $art->pvp, $art->pvp + 1);
            $art->stockmin = mt_rand(0, 10);
            $art->stockmax = mt_rand($art->stockmin + 1, $art->stockmin + 1000);

            switch (mt_rand(0, 2)) {
                case 0:
                    $art->referencia = $art->newCode();
                    break;

                case 1:
                    $aux = explode(':', $art->descripcion);
                    if (!empty($aux)) {
                        $art->referencia = $this->txt2codigo($aux[0], 18);
                    } else {
                        $art->referencia = $art->newCode();
                    }
                    break;

                default:
                    $art->referencia = $this->randomString(10);
            }

            if (mt_rand(0, 9) > 0) {
                $art->codfabricante = $this->getOneItem($fabricantes)->codfabricante;
                $art->codfamilia = $this->getOneItem($familias)->codfamilia;
            } else {
                $art->codfabricante = null;
                $art->codfamilia = null;
            }

            $art->publico = (mt_rand(0, 3) == 0);
            $art->bloqueado = (mt_rand(0, 9) == 0);
            $art->nostock = (mt_rand(0, 9) == 0);
            $art->secompra = (mt_rand(0, 9) != 0);
            $art->sevende = (mt_rand(0, 9) != 0);

            if (!$art->save()) {
                break;
            }

            if (mt_rand(0, 2) == 0) {
                $art->sumStock($this->getOneItem($almacenes)->codalmacen, mt_rand(0, 1000));
            } else {
                $art->sumStock($this->getOneItem($almacenes)->codalmacen, mt_rand(0, 20));
            }
        }

        return $i;
    }
            
}