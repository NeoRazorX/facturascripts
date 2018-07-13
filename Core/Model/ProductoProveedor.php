<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Utils;

/**
 * Artículo vendido por un proveedor.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ProductoProveedor extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Barcode,
     *
     * @var string
     */
    public $codbarras;

    /**
     * Currency identifier.
     *
     * @var string
     */
    public $coddivisa;

    /**
     * Tax identifier of the tax assigned.
     *
     * @var string
     */
    public $codimpuesto;

    /**
     * Supplier identifier.
     *
     * @var string
     */
    public $codproveedor;

    /**
     * Description of the product.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Discount.
     *
     * @var float|int
     */
    public $dto;

    /**
     * Primary key.
     *
     * @var int
     */
    public $id;

    /**
     * True -> do not control the stock.
     *
     * @var bool
     */
    public $nostock;

    /**
     * Product price, without tax.
     *
     * @var float|int
     */
    public $precio;

    /**
     * Product identifier or SKU in our warehouse.
     *
     * @var string
     */
    public $referencia;

    /**
     * Product identifier or SKU in supplier's warehouse.
     *
     * @var string
     */
    public $refproveedor;

    /**
     * Physical stock is supplier's warehouse.
     *
     * @var float|int
     */
    public $stockfis;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->coddivisa = AppSettings::get('default', 'coddivisa');
        $this->codimpuesto = AppSettings::get('default', 'codimpuesto');
        $this->dto = 0.0;
        $this->nostock = false;
        $this->precio = 0.0;
        $this->stockfis = 0.0;
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

        return parent::install();
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
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'productosprov';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        $this->codbarras = Utils::noHtml($this->codbarras);
        $this->descripcion = Utils::noHtml($this->descripcion);
        $this->referencia = Utils::noHtml($this->referencia);
        $this->refproveedor = Utils::noHtml($this->refproveedor);
        return parent::test();
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List')
    {
        return parent::url($type, 'ListProducto?active=List');
    }
}
