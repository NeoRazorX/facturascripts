<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Almacen as DinAlmacen;
use FacturaScripts\Dinamic\Model\Producto as DinProducto;
use FacturaScripts\Dinamic\Model\Variante as DinVariante;

/**
 * The quantity in inventory of an item in a particular warehouse.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Stock extends Base\ModelClass
{

    use Base\ModelTrait;
    use Base\ProductRelationTrait;

    const MAX_DECIMALS = 3;

    /**
     * Quantity.
     *
     * @var float|int
     */
    public $cantidad;

    /**
     * Warehouse code.
     *
     * @var string
     */
    public $codalmacen;

    /**
     * Available. Is the quantity minus reserved.
     *
     * @var float|int
     */
    public $disponible;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idstock;

    /**
     * Pending receipt. Merchandise pending receipt from the supplier.
     *
     * @var float|int
     */
    public $pterecibir;

    /**
     * Reference.
     *
     * @var string
     */
    public $referencia;

    /**
     * Reserved on customer orders.
     *
     * @var float|int
     */
    public $reservada;

    /**
     * Maximum stock.
     *
     * @var float|int
     */
    public $stockmax;

    /**
     * Minimum stock.
     *
     * @var float|int
     */
    public $stockmin;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->cantidad = 0.0;
        $this->codalmacen = $this->toolBox()->appSettings()->get('default', 'codalmacen');
        $this->disponible = 0.0;
        $this->pterecibir = 0.0;
        $this->reservada = 0.0;
        $this->stockmax = 0.0;
        $this->stockmin = 0.0;
    }

    /**
     * 
     * @return bool
     */
    public function delete()
    {
        if (parent::delete()) {
            $this->cantidad = 0.0;
            $this->updateProductStock();
            return true;
        }

        return false;
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
        /// needed dependencies
        new DinAlmacen();
        new DinProducto();
        new DinVariante();

        return parent::install();
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idstock';
    }

    /**
     * 
     * @return boolean
     */
    public function save()
    {
        if (parent::save()) {
            $this->updateProductStock();
            return true;
        }

        return false;
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'stocks';
    }

    /**
     * Transfer $qty unities of stock to $toWarehouse
     *
     * @param string $toWarehouse destination warehouse
     * @param float  $qty quantity to move
     *
     * @return bool
     */
    public function transferTo(string $toWarehouse, float $qty): bool
    {
        $destination = new static();
        $where = [
            new DataBaseWhere('codalmacen', $toWarehouse),
            new DataBaseWhere('referencia', $this->referencia)
        ];
        if ($destination->loadFromCode('', $where)) {
            $destination->cantidad += $qty;
        } else {
            $destination->codalmacen = $toWarehouse;
            $destination->idproducto = $this->idproducto;
            $destination->referencia = $this->referencia;
            $destination->cantidad = $qty;
        }

        $this->cantidad -= $qty;
        return $destination->save() && $this->save();
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->cantidad = \round($this->cantidad, self::MAX_DECIMALS);
        $this->referencia = $this->toolBox()->utils()->noHtml($this->referencia);
        $this->reservada = \round($this->reservada, self::MAX_DECIMALS);
        $this->pterecibir = \round($this->pterecibir, self::MAX_DECIMALS);

        $this->disponible = $this->cantidad - $this->reservada;
        return parent::test();
    }

    /**
     * Returns the total stock of the product.
     * 
     * @param int    $idproducto
     * @param string $referencia
     * 
     * @return float
     */
    public function totalFromProduct(int $idproducto, string $referencia = '')
    {
        $sql = 'SELECT SUM(cantidad) AS total FROM ' . static::tableName()
            . ' WHERE idproducto = ' . self::$dataBase->var2str($idproducto);

        if (!empty($referencia)) {
            $sql .= ' AND referencia = ' . self::$dataBase->var2str($referencia);
        }

        $data = self::$dataBase->select($sql);
        return empty($data) ? 0.0 : \round((float) $data[0]['total'], self::MAX_DECIMALS);
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
        return $this->getProducto()->url($type);
    }

    /**
     * 
     * @return bool
     */
    protected function updateProductStock()
    {
        $total = $this->totalFromProduct($this->idproducto);
        $sql = "UPDATE " . DinProducto::tableName() . " SET stockfis = " . self::$dataBase->var2str($total)
            . ", actualizado = " . self::$dataBase->var2str(\date(self::DATETIME_STYLE))
            . " WHERE idproducto = " . self::$dataBase->var2str($this->idproducto) . ';';

        $totalVariant = $this->totalFromProduct($this->idproducto, $this->referencia);
        $sql .= "UPDATE " . DinVariante::tableName() . " SET stockfis = " . self::$dataBase->var2str($totalVariant)
            . " WHERE referencia = " . self::$dataBase->var2str($this->referencia) . ';';

        return self::$dataBase->exec($sql);
    }
}
