<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model;

/**
 * Artículo vendido por un proveedor.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ArticuloProveedor extends Base\Product
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var int
     */
    public $id;

    /**
     * Código del proveedor asociado.
     *
     * @var string
     */
    public $codproveedor;

    /**
     * Referencia del artículo para el proveedor.
     *
     * @var string
     */
    public $refproveedor;

    /**
     * Precio neto al que nos ofrece el proveedor este producto.
     *
     * @var float|int
     */
    public $precio;

    /**
     * Descuento sobre el precio que nos hace el proveedor.
     *
     * @var float|int
     */
    public $dto;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'articulosprov';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'id';
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        /// force the verification of the provider table
        new Proveedor();

        return '';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->precio = 0.0;
        $this->dto = 0.0;
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url($type = 'auto', $list = 'List')
    {
        return parent::url($type, 'ListArticulo?active=List');
    }
}
