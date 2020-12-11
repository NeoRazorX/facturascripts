<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Description of FacturaProveedorProducto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class FacturaProveedorProducto extends JoinModel
{

    const DOC_TABLE = 'facturasprov';
    const MAIN_TABLE = 'lineasfacturasprov';

    /**
     * 
     * @return array
     */
    protected function getFields(): array
    {
        return [
            'avgcoste' => 'avg(' . static::MAIN_TABLE . '.pvptotal/' . static::MAIN_TABLE . '.cantidad)',
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
            . ' LEFT JOIN facturasprov ON facturasprov.idfactura = lineasfacturasprov.idfactura';
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
