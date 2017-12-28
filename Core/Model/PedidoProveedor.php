<?php
/**
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2017  Carlos Garcia Gomez       <carlos@facturascripts.com>
 * Copyright (C) 2014-2015  Francesc Pineda Segarra   <shawe.ewahs@gmail.com>
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
 * Supplier order.
 */
class PedidoProveedor
{

    use Base\DocumentoCompra;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idpedido;

    /**
     * Related delivery note ID.
     *
     * @var int
     */
    public $idalbaran;

    /**
     * True if it is editable, but false.
     *
     * @var bool
     */
    public $editable;

    /**
     * If this estiamtion is the version of another, the original estimation document is stored here.
     *
     * @var int
     */
    public $idoriginal;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'pedidosprov';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'idpedido';
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
        new Serie();
        new Ejercicio();

        return '';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->clearDocumentoCompra();
        $this->editable = true;
    }

    /**
     * Returns the lines associated with the order.
     *
     * @return LineaPedidoProveedor[]
     */
    public function getLineas()
    {
        $lineaModel = new LineaPedidoProveedor();
        return $lineaModel->all([new DataBaseWhere('idpedido', $this->idpedido)]);
    }

    /**
     * Returns the versions of an order.
     *
     * @return self[]
     */
    public function getVersiones()
    {
        $versiones = [];

        $sql = 'SELECT * FROM ' . static::tableName()
            . ' WHERE idoriginal = ' . self::$dataBase->var2str($this->idpedido);
        if ($this->idoriginal) {
            $sql .= ' OR idoriginal = ' . self::$dataBase->var2str($this->idoriginal);
            $sql .= ' OR idpedido = ' . self::$dataBase->var2str($this->idoriginal);
        }
        $sql .= 'ORDER BY fecha DESC, hora DESC;';

        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $versiones[] = new self($d);
            }
        }

        return $versiones;
    }

    /**
     * Check the order data, return True if it is correct.
     *
     * @return boolean
     */
    public function test()
    {
        return $this->testTrait();
    }

    /**
     * Run a complete test of tests.
     *
     * @return bool
     */
    public function fullTest()
    {
        return $this->fullTestTrait('order');
    }

    /**
     * Execute a task with cron
     */
    public function cronJob()
    {
        $sql = 'UPDATE ' . static::tableName() . ' SET idalbaran = NULL, editable = TRUE'
            . ' WHERE idalbaran IS NOT NULL AND NOT EXISTS(SELECT 1 FROM albaranesprov t1'
            . ' WHERE t1.idalbaran = ' . static::tableName() . '.idalbaran);';
        self::$dataBase->exec($sql);
    }
}
