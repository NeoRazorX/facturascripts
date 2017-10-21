<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

/**
 * La cantidad en inventario de un artículo en un almacén concreto.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Stock
{

    use Base\ModelTrait;

    /**
     * Clave primaria.
     *
     * @var int
     */
    public $idstock;

    /**
     * TODO
     *
     * @var string
     */
    public $codalmacen;

    /**
     * TODO
     *
     * @var string
     */
    public $referencia;

    /**
     * TODO
     *
     * @var string
     */
    public $nombre;

    /**
     * TODO
     *
     * @var float
     */
    public $cantidad;

    /**
     * TODO
     *
     * @var
     */
    public $reservada;

    /**
     * TODO
     *
     * @var
     */
    public $disponible;

    /**
     * TODO
     *
     * @var
     */
    public $pterecibir;

    /**
     * TODO
     *
     * @var float
     */
    public $stockmin;

    /**
     * TODO
     *
     * @var float
     */
    public $stockmax;

    /**
     * TODO
     *
     * @var
     */
    public $cantidadultreg;
    public $fechaultreg;
    public $horaultreg;

    /**
     * TODO
     *
     * @var
     */
    public $ubicacion;

    public function tableName()
    {
        return 'stocks';
    }

    public function primaryColumn()
    {
        return 'idstock';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->idstock = null;
        $this->codalmacen = null;
        $this->referencia = null;
        $this->nombre = '';
        $this->cantidad = 0;
        $this->reservada = 0;
        $this->disponible = 0;
        $this->pterecibir = 0;
        $this->stockmin = 0;
        $this->stockmax = 0;
        $this->cantidadultreg = 0;
        $this->fechaultreg = null;
        $this->horaultreg = null;
        $this->ubicacion = null;
    }

    /**
     * TODO
     *
     * @return mixed
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
     * TODO
     *
     * @param int $cant
     */
    public function setCantidad($cant = 0)
    {
        $this->cantidad = (float) $cant;

        if ($this->cantidad < 0 && !FS_STOCK_NEGATIVO) {
            $this->cantidad = 0;
        }

        $this->disponible = $this->cantidad - $this->reservada;
    }

    /**
     * TODO
     *
     * @param int $cant
     */
    public function sumCantidad($cant = 0)
    {
        /// convertimos a flot por si acaso nos ha llegado un string
        $this->cantidad += (float) $cant;

        if ($this->cantidad < 0 && !FS_STOCK_NEGATIVO) {
            $this->cantidad = 0;
        }

        $this->disponible = $this->cantidad - $this->reservada;
    }

    /**
     * TODO
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

    public function test()
    {
        $this->cantidad = round($this->cantidad, 3);
        $this->reservada = round($this->reservada, 3);
        $this->disponible = $this->cantidad - $this->reservada;

        return true;
    }

    /**
     * TODO
     *
     * @param string $ref
     * @param bool   $codalmacen
     *
     * @return float|int
     */
    public function totalFromArticulo($ref, $codalmacen = false)
    {
        $sql = 'SELECT SUM(cantidad) AS total FROM ' . $this->tableName()
            . ' WHERE referencia = ' . $this->var2str($ref);

        if ($codalmacen) {
            $sql .= ' AND codalmacen = ' . $this->var2str($codalmacen);
        }

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            return round((float) $data[0]['total'], 3);
        }

        return 0;
    }

    /**
     * TODO
     *
     * @param string $column
     *
     * @return int
     */
    public function count($column = 'idstock')
    {
        $num = 0;

        $sql = 'SELECT COUNT(idstock) AS total FROM ' . $this->tableName() . ';';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            $num = (int) $data[0]['total'];
        }

        return $num;
    }

    /**
     * TODO
     *
     * @return int
     */
    public function countByArticulo()
    {
        return $this->count('DISTINCT referencia');
    }
}
