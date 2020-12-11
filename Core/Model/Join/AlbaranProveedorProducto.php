<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Description of AlbaranProveedorProducto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class AlbaranProveedorProducto extends FacturaProveedorProducto
{

    const DOC_TABLE = 'albaranesprov';
    const MAIN_TABLE = 'lineasalbaranesprov';

    /**
     * 
     * @return string
     */
    protected function getSQLFrom(): string
    {
        return static::MAIN_TABLE . ''
            . ' LEFT JOIN variantes ON ' . static::MAIN_TABLE . '.referencia = variantes.referencia'
            . ' LEFT JOIN productos ON variantes.idproducto = productos.idproducto'
            . ' LEFT JOIN albaranesprov ON albaranesprov.idalbaran = lineasalbaranesprov.idalbaran';
    }
}
