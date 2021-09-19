<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Base\JoinModel;

/**
 * Description of FacturaClienteProducto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class FacturaClienteProducto extends JoinModel
{

    const DOC_TABLE = 'facturascli';
    const MAIN_TABLE = 'lineasfacturascli';

    /**
     * 
     * @return array
     */
    protected function getFields(): array
    {
        return [
            'avgbeneficio' => 'sum(' . static::MAIN_TABLE . '.pvptotal) / sum(' . static::MAIN_TABLE . '.cantidad) - variantes.coste',
            'avgprecio' => 'sum(' . static::MAIN_TABLE . '.pvptotal) / sum(' . static::MAIN_TABLE . '.cantidad)',
            'cantidad' => 'sum(' . static::MAIN_TABLE . '.cantidad)',
            'codalmacen' => static::DOC_TABLE . '.codalmacen',
            'codfabricante' => 'productos.codfabricante',
            'codfamilia' => 'productos.codfamilia',
            'coste' => 'variantes.coste',
            'descripcion' => 'productos.descripcion',
            'idproducto' => static::MAIN_TABLE . '.idproducto',
            'precio' => 'variantes.precio',
            'referencia' => static::MAIN_TABLE . '.referencia',
            'stockfis' => 'variantes.stockfis'
        ];
    }
    /**
     * List of type of fields or columns in select clausule.
     *
     * @return array
     */
    protected function getFieldsType(): array {
        return [
            'avgbeneficio' => 'double',
            'avgprecio' => 'double',
            'cantidad' => 'double',
             static::DOC_TABLE . '.codalmacen'=> 'string',
            'productos.codfabricante' => 'string',
            'productos.codfamilia' => 'string',
            'variantes.coste' => 'string',
            'productos.descripcion' => 'string',
            static::MAIN_TABLE . '.idproducto' => 'integer',
            'variantes.precio' => 'double',
            static::MAIN_TABLE . '.referencia' => 'string',
            'variantes.stockfis' => 'double'
        ];
    }

    /**
     * 
     * @return string
     */
    protected function getGroupFields(): string
    {
        return static::DOC_TABLE . '.codalmacen, ' . static::MAIN_TABLE . '.idproducto, ' . static::MAIN_TABLE . '.referencia';
    }

    /**
     * 
     * @return string
     */
    protected function getSQLFrom(): string
    {
        return static::MAIN_TABLE . ''
            . ' LEFT JOIN variantes ON ' . static::MAIN_TABLE . '.referencia = variantes.referencia'
            . ' LEFT JOIN productos ON variantes.idproducto = productos.idproducto'
            . ' LEFT JOIN facturascli ON facturascli.idfactura = lineasfacturascli.idfactura';
    }

    /**
     * 
     * @return array
     */
    protected function getTables(): array
    {
        return [static::MAIN_TABLE, 'productos', 'variantes'];
    }
}
