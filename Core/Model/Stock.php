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

    /**
     * Primary key.
     *
     * @var int
     */
    public $idstock;

    /**
     * Warehouse code.
     *
     * @var string
     */
    public $codalmacen;

    /**
     * Reference.
     *
     * @var string
     */
    public $referencia;

    /**
     * Name.
     *
     * @var string
     */
    public $nombre;

    /**
     * Quantity.
     *
     * @var float|int
     */
    public $cantidad;

    /**
     * Reserved.
     *
     * @var float|int
     */
    public $reservada;

    /**
     * Available.
     *
     * @var float|int
     */
    public $disponible;

    /**
     * Pending receipt.
     *
     * @var float|int
     */
    public $pterecibir;

    /**
     * Minimum stock.
     *
     * @var float|int
     */
    public $stockmin;

    /**
     * Maximum stock.
     *
     * @var float|int
     */
    public $stockmax;

    /**
     * Location.
     *
     * @var string
     */
    public $ubicacion;

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
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idstock';
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
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->nombre = '';
        $this->cantidad = 0.0;
        $this->reservada = 0.0;
        $this->disponible = 0.0;
        $this->pterecibir = 0.0;
        $this->stockmin = 0.0;
        $this->stockmax = 0.0;
    }

    /**
     * Returns the name of the store.
     *
     * @return string
     */
    public function getNombre()
    {
        $almacenModel = new Almacen();
        $almacen = $almacenModel->get($this->codalmacen);
        if ($almacen) {
            $this->nombre = $almacen->nombre;
        }

        return $this->nombre;
    }

    /**
     * Assign the amount.
     *
     * @param int $cant
     */
    public function setCantidad($cant = 0)
    {
        $this->cantidad = (float) $cant;
        $this->disponible = $this->cantidad - $this->reservada;
    }

    /**
     * Add the amount.
     *
     * @param int $cant
     */
    public function sumCantidad($cant = 0)
    {
        /// convertimos a flot por si acaso nos ha llegado un string
        $this->cantidad += (float) $cant;
        $this->disponible = $this->cantidad - $this->reservada;
    }

    /**
     * Returns the stock by reference and additionally by warehouse.
     *
     * @param string $ref
     * @param bool   $codalmacen
     *
     * @return Stock|false
     */
    public function getByReferencia($ref, $codalmacen = false)
    {
        $where = [new DataBaseWhere('referencia', $ref)];
        if ($codalmacen) {
            $where[] = new DataBaseWhere('codalmacen', $codalmacen);
        }

        foreach ($this->all($where) as $stock) {
            return $stock;
        }

        return false;
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->cantidad = round($this->cantidad, 3);
        $this->reservada = round($this->reservada, 3);
        $this->disponible = $this->cantidad - $this->reservada;
        $this->ubicacion = Utils::noHtml($this->ubicacion);

        return true;
    }

    /**
     * Returns the total stock by reference and additionally by warehouse.
     *
     * @param string $ref
     * @param bool   $codalmacen
     *
     * @return float|int
     */
    public function totalFromArticulo($ref, $codalmacen = false)
    {
        $sql = 'SELECT SUM(cantidad) AS total FROM ' . static::tableName()
            . ' WHERE referencia = ' . self::$dataBase->var2str($ref);

        if ($codalmacen) {
            $sql .= ' AND codalmacen = ' . self::$dataBase->var2str($codalmacen);
        }

        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            return round((float) $data[0]['total'], 3);
        }

        return 0;
    }
}
