<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Utils;

/**
 * The quantity in inventory of an item in a particular warehouse.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Stock extends Base\ModelClass
{

    use Base\ModelTrait;

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
     * Location.
     *
     * @var string
     */
    public $ubicacion;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->cantidad = 0.0;
        $this->disponible = 0.0;
        $this->pterecibir = 0.0;
        $this->reservada = 0.0;
        $this->stockmax = 0.0;
        $this->stockmin = 0.0;
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
        new Almacen();
        new Articulo();

        return '';
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

    public function save()
    {
        if (parent::save()) {
            $articulo = new Articulo();
            if ($articulo->loadFromCode($this->referencia)) {
                $articulo->stockfis = $this->totalFromArticulo($this->referencia);
                return $articulo->save();
            }

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
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->cantidad = round($this->cantidad, self::MAX_DECIMALS);
        $this->reservada = round($this->reservada, self::MAX_DECIMALS);
        $this->disponible = $this->cantidad - $this->reservada;
        $this->ubicacion = Utils::noHtml($this->ubicacion);

        return true;
    }

    /**
     * Returns the total stock by reference.
     *
     * @param string $ref
     *
     * @return float
     */
    public function totalFromArticulo($ref)
    {
        $sql = 'SELECT SUM(cantidad) AS total FROM ' . static::tableName()
            . ' WHERE referencia = ' . self::$dataBase->var2str($ref);

        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            return round((float) $data[0]['total'], self::MAX_DECIMALS);
        }

        return 0.0;
    }
}
