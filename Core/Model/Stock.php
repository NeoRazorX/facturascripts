<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * @author Carlos García Gómez <carlos@facturascripts.com>
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
     * Código de almacén
     *
     * @var string
     */
    public $codalmacen;

    /**
     * Referéncia
     *
     * @var string
     */
    public $referencia;

    /**
     * Nombre
     *
     * @var string
     */
    public $nombre;

    /**
     * Cantidad
     *
     * @var float|int
     */
    public $cantidad;

    /**
     * Reservada
     *
     * @var float|int
     */
    public $reservada;

    /**
     * Disponible
     *
     * @var float|int
     */
    public $disponible;

    /**
     * Pendiente de recibir
     *
     * @var float|int
     */
    public $pterecibir;

    /**
     * Stock mínimo
     *
     * @var float|int
     */
    public $stockmin;

    /**
     * Stock máximo
     *
     * @var float|int
     */
    public $stockmax;

    /**
     * Cantidad última regularización
     *
     * @var float|int
     */
    public $cantidadultreg;

    /**
     * Fecha última regularización
     *
     * @var string
     */
    public $fechaultreg;

    /**
     * Hora última regularización
     *
     * @var string
     */
    public $horaultreg;

    /**
     * Ubicación
     *
     * @var string
     */
    public $ubicacion;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'stocks';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'idstock';
    }

    /**
     * Crea la consulta necesaria para crear un nuevo agente en la base de datos.
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
     * Devuelve el nombre del almacén
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
     * Asigna la cantidad
     *
     * @param int $cant
     */
    public function setCantidad($cant = 0)
    {
        $this->cantidad = (float) $cant;
        $this->disponible = $this->cantidad - $this->reservada;
    }

    /**
     * Añade la cantidad
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
     * Devuelve el stock por referencia y adicionalmente por almacén
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
     * Devuelve true si no hay errores en los valores de las propiedades del modelo.
     *
     * @return bool
     */
    public function test()
    {
        $this->cantidad = round($this->cantidad, 3);
        $this->reservada = round($this->reservada, 3);
        $this->disponible = $this->cantidad - $this->reservada;

        return true;
    }

    /**
     * Devuelve el stock total por referencia y adicionalmente por almacén
     *
     * @param string $ref
     * @param bool   $codalmacen
     *
     * @return float|int
     */
    public function totalFromArticulo($ref, $codalmacen = false)
    {
        $sql = 'SELECT SUM(cantidad) AS total FROM ' . static::tableName()
            . ' WHERE referencia = ' . $this->dataBase->var2str($ref);

        if ($codalmacen) {
            $sql .= ' AND codalmacen = ' . $this->dataBase->var2str($codalmacen);
}

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            return round((float) $data[0]['total'], 3);
        }

        return 0;
    }

    /**
     * Devuelve el stock total
     *
     * @param string $column
     *
     * @return int
     */
    public function count($column = 'idstock')
    {
        $num = 0;

        $sql = 'SELECT COUNT(idstock) AS total FROM ' . static::tableName() . ';';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            $num = (int) $data[0]['total'];
        }

        return $num;
    }

    /**
     * Devuelve el stock total por referencia
     *
     * @return int
     */
    public function countByArticulo()
    {
        return $this->count('DISTINCT referencia');
    }

    /**
     * Devuelve el stock por referencia ordenado por codalmacen
     *
     * @param string $ref
     *
     * @return self[]
     */
    public function allFromArticulo($ref)
    {
        $stocklist = array();

        $sql = 'SELECT * FROM ' . static::tableName()
            . ' WHERE referencia = ' . $this->dataBase->var2str($ref) . ' ORDER BY codalmacen ASC;';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $s) {
                $stocklist[] = new self($s);
            }
        }

        return $stocklist;
    }
}
