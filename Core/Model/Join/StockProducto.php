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
use FacturaScripts\Dinamic\Model\Producto;

/**
 * Model Stock with Producto data
 *
 * @author Raul Jimenez                     <raul.jimenez@nazcanetworks.com>
 * @author Jose Antonio Cuello Principal    <yopli2000@gmail.com>
 * @author Carlos García Gómez              <carlos@facturascripts.com>
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
        $this->setMasterModel(new Producto());
    }

    /**
     * List of fields or columns to select clausule.
     * 
     * @return array
     */
    protected function getFields(): array
    {
        return [
            'cantidad' => 'stocks.cantidad',
            'codalmacen' => 'stocks.codalmacen',
            'codfabricante' => 'productos.codfabricante',
            'codfamilia' => 'productos.codfamilia',
            'coste' => 'variantes.coste',
            'descripcion' => 'productos.descripcion',
            'disponible' => 'stocks.disponible',
            'idproducto' => 'stocks.idproducto',
            'idstock' => 'stocks.idstock',
            'precio' => 'variantes.precio',
            'pterecibir' => 'stocks.pterecibir',
            'referencia' => 'stocks.referencia',
            'reservada' => 'stocks.reservada',
            'stockmax' => 'stocks.stockmax',
            'stockmin' => 'stocks.stockmin',
            'total' => 'sum(stocks.cantidad*variantes.coste)'
        ];
    }

    /**
     * 
     * @return string
     */
    protected function getGroupFields(): string
    {
        return 'stocks.referencia, stocks.codalmacen';
    }

    /**
     * 
     * @return string
     */
    protected function getSQLFrom(): string
    {
        return 'stocks'
            . ' LEFT JOIN variantes ON variantes.referencia = stocks.referencia'
            . ' LEFT JOIN productos ON productos.idproducto = variantes.idproducto';
    }

    /**
     * 
     * @return array
     */
    protected function getTables(): array
    {
        return ['productos', 'stocks', 'variantes'];
    }
}
