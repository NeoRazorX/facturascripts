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

/**
 * La cantidad en inventario de un artículo en un almacén concreto.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Stock
{
    use Base\ModelTrait {
        save as private saveTrait;
    }

    /**
     * Clave primaria.
     * @var int
     */
    public $idstock;
    /**
     * TODO
     * @var string
     */
    public $codalmacen;
    /**
     * TODO
     * @var string
     */
    public $referencia;
    /**
     * TODO
     * @var string
     */
    public $nombre;
    /**
     * TODO
     * @var float
     */
    public $cantidad;
    /**
     * TODO
     * @var
     */
    public $reservada;
    /**
     * TODO
     * @var
     */
    public $disponible;
    /**
     * TODO
     * @var
     */
    public $pterecibir;
    /**
     * TODO
     * @var float
     */
    public $stockmin;
    /**
     * TODO
     * @var float
     */
    public $stockmax;
    /**
     * TODO
     * @var
     */
    public $cantidadultreg;
    /**
     * TODO
     * @var
     */
    public $ubicacion;

    /**
     * Stock constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->init(__CLASS__, 'stocks', 'idstock');
        if (is_null($data) || empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
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
        $this->ubicacion = null;
    }

    /**
     * TODO
     * @return mixed
     */
    public function getNombre()
    {
        $al0 = new Almacen();
        $almacen = $al0->get($this->codalmacen);
        if ($almacen) {
            $this->nombre = $almacen->nombre;
        }

        return $this->nombre;
    }

    /**
     * TODO
     *
     * @param int $c
     */
    public function setCantidad($c = 0)
    {
        $this->cantidad = (float)$c;

        if ($this->cantidad < 0 && !FS_STOCK_NEGATIVO) {
            $this->cantidad = 0;
        }

        $this->disponible = $this->cantidad - $this->reservada;
    }

    /**
     * TODO
     *
     * @param int $c
     */
    public function sumCantidad($c = 0)
    {
        /// convertimos a flot por si acaso nos ha llegado un string
        $this->cantidad += (float)$c;

        if ($this->cantidad < 0 && !FS_STOCK_NEGATIVO) {
            $this->cantidad = 0;
        }

        $this->disponible = $this->cantidad - $this->reservada;
    }

    /**
     * TODO
     *
     * @param string $ref
     * @param bool $codalmacen
     *
     * @return bool|Stock
     */
    public function getByReferencia($ref, $codalmacen = false)
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE referencia = ' . $this->var2str($ref) . ';';
        if ($codalmacen) {
            $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE referencia = ' . $this->var2str($ref)
                . ', codalmacen = ' . $this->var2str($codalmacen) . ';';
        }

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            return new Stock($data[0]);
        }
        return false;
    }

    /**
     * Almacena los datos del modelo en la base de datos.
     * @return bool
     */
    public function save()
    {
        $this->cantidad = round($this->cantidad, 3);
        $this->reservada = round($this->reservada, 3);
        $this->disponible = $this->cantidad - $this->reservada;

        return $this->saveTrait();
    }

    /**
     * TODO
     *
     * @param string $ref
     *
     * @return array
     */
    public function allFromArticulo($ref)
    {
        $stocklist = [];

        $sql = 'SELECT * FROM ' . $this->tableName()
            . ' WHERE referencia = ' . $this->var2str($ref) . ' ORDER BY codalmacen ASC;';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $s) {
                $stocklist[] = new Stock($s);
            }
        }

        return $stocklist;
    }

    /**
     * TODO
     *
     * @param string $ref
     * @param bool $codalmacen
     *
     * @return float|int
     */
    public function totalFromArticulo($ref, $codalmacen = false)
    {
        $num = 0;
        $sql = 'SELECT SUM(cantidad) AS total FROM ' . $this->tableName()
            . ' WHERE referencia = ' . $this->var2str($ref);

        if ($codalmacen) {
            $sql .= ' AND codalmacen = ' . $this->var2str($codalmacen);
        }

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            $num = round((float)$data[0]['total'], 3);
        }

        return $num;
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
            $num = (int)$data[0]['total'];
        }

        return $num;
    }

    /**
     * TODO
     * @return int
     */
    public function countByArticulo()
    {
        return $this->count('DISTINCT referencia');
    }

    /**
     * Aplicamos algunas correcciones a la tabla.
     */
    public function fixDb()
    {
        /**
         * Esta consulta produce un error si no hay datos erroneos, pero da igual
         */
        $sql = 'DELETE FROM stocks s WHERE NOT EXISTS '
            . '(SELECT referencia FROM articulos a WHERE a.referencia = s.referencia);';
        $this->dataBase->exec($sql);
    }

    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     * @return string
     */
    private function install()
    {
        /**
         * La tabla stocks tiene claves ajenas a artículos y almacenes,
         * por eso creamos un objeto de cada uno, para forzar la comprobación
         * de las tablas.
         */
        //new Almacen();
        //new Articulo();

        return '';
    }
}
