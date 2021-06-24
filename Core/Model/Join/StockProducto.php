<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model\Join;

use FacturaScripts\Dinamic\Model\Base\JoinModel;
use FacturaScripts\Dinamic\Model\Producto as DinProducto;

/**
 * Model Stock with Producto data
 *
 * @author Raul Jimenez  <raul.jimenez@nazcanetworks.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class StockProducto extends JoinModel
{

    /**
     * Class constructor.
     * Set master model for controller actions.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->setMasterModel( new DinProducto() );
    }

    /**
     * List of fields or columns to select clausule
     */
    protected function getFields(): array
    {
        return [
            'codalmacen' => 'stocks.codalmacen',
            'cantidad' => 'stocks.cantidad',
            'disponible' => 'stocks.disponible',
            'idproducto' => 'stocks.idproducto',
            'idstock' => 'stocks.idstock',
            'pterecibir' => 'stocks.pterecibir',
            'referencia' => 'stocks.referencia',
            'reservada' => 'stocks.reservada',
            'stockmax' => 'stocks.stockmax',
            'stockmin' => 'stocks.stockmin',
            'descripcion' => 'productos.descripcion',
        ];
    }

    /**
     * List of tables related to from clausule
     */
    protected function getSQLFrom(): string
    {
        return 'stocks'
            . ' LEFT JOIN productos on productos.idproducto = stocks.idproducto'
        ;
    }

    /**
     * List of tables required for the execution of the view.
     */
    protected function getTables(): array
    {
        return [
            'productos',
            'stocks',
        ];
    }
}