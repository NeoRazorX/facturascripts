<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * Item sold by a supplier.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ArticuloProveedor
{

    use Base\ModelTrait {
        url as private traitURL;
    }

    /**
     * Primary key.
     *
     * @var int
     */
    public $id;

    /**
     * Reference of the article in our catalog. It may not be currently.
     *
     * @var string
     */
    public $referencia;

    /**
     * Supplier code associated.
     *
     * @var string
     */
    public $codproveedor;

    /**
     * Article reference for the supplier.
     *
     * @var string
     */
    public $refproveedor;

    /**
     * Description of the article
     *
     * @var string
     */
    public $descripcion;

    /**
     * Net price to which the supplier offers this product.
     *
     * @var float|int
     */
    public $precio;

    /**
     * Discount on the price that the supplier makes us.
     *
     * @var float|int
     */
    public $dto;

    /**
     * Tax assigned. Taxed class.
     *
     * @var string
     */
    public $codimpuesto;

    /**
     * Stock of the item in the supplier's warehouse.
     *
     * @var float|int
     */
    public $stock;

    /**
     * TRUE -> the item does not offer stock.
     *
     * @var bool
     */
    public $nostock;

    /**
     * Article barcode
     *
     * @var string
     */
    public $codbarras;

    /**
     * Part Number
     *
     * @var string
     */
    public $partnumber;

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
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'id';
    }

    /**
     * Create the necessary query to create a new provider article in the database.
     *
     * @return string
     */
    public function install()
    {
        /// forzamos la comprobación de la tabla de proveedores
        new Proveedor();

        return '';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->id = null;
        $this->referencia = null;
        $this->codproveedor = null;
        $this->refproveedor = null;
        $this->descripcion = null;
        $this->precio = 0;
        $this->dto = 0;
        $this->codimpuesto = null;
        $this->stock = 0;
        $this->nostock = true;
        $this->codbarras = null;
        $this->partnumber = null;
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->descripcion = self::noHtml($this->descripcion);

        if ($this->nostock) {
            $this->stock = 0;
        }

        if ($this->refproveedor === null || empty($this->refproveedor) || strlen($this->refproveedor) > 25) {
            $this->miniLog->alert($this->i18n->trans('supplier-reference-valid-length'));
            return false;
        }

        return true;
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     *
     * @return string
     */
    public function url($type = 'auto')
    {
        return $this->traitURL($type, 'ListArticulo&active=List');
    }

    /**
     * We apply corrections to the table.
     */
    public function fixDb()
    {
        $fixes = [
            'DELETE FROM articulosprov WHERE codproveedor NOT IN (SELECT codproveedor FROM proveedores);',
            'UPDATE articulosprov SET refproveedor = referencia WHERE refproveedor IS NULL;',
        ];
        foreach ($fixes as $sql) {
            $this->dataBase->exec($sql);
        }
    }
}
