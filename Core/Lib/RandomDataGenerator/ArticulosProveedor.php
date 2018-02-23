<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2016-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * Associate products to suppliers at random
 *
 * @author Rafael San José <info@rsanjoseo.com>
 */
class ArticulosProveedor extends AbstractRandom
{

    public function __construct()
    {
        parent::__construct(new Model\ArticuloProveedor());
    }

    public function generate($num = 50)
    {
        $this->shuffle($articulos, new Model\Articulo());
        $this->shuffle($proveedores, new Model\Proveedor());
        $art = $this->model;

        for ($generated = 0; $generated < $num; ++$generated) {
            if (!isset($articulos[$generated])) {
                break;
            }

            $art->clear();
            $art->referencia = $articulos[$generated]->referencia;
            $art->refproveedor = (string) mt_rand(1, 99999999);
            $art->descripcion = $this->descripcion();
            $art->codimpuesto = $articulos[$generated]->codimpuesto;
            $art->codproveedor = $proveedores[$generated]->codproveedor;
            $art->precio = $this->precio(1, 49, 699);
            $art->dto = mt_rand(0, 80);
            $art->nostock = (mt_rand(0, 2) == 0);
            $art->stockfis = mt_rand(0, 10);
            if (!$art->save()) {
                break;
            }
        }

        return $generated;
    }
}
