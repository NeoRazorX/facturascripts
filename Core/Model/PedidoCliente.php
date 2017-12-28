<?php
/**
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2017    Carlos Garcia Gomez        <carlos@facturascripts.com>
 * Copyright (C) 2014         Francesc Pineda Segarra    <shawe.ewahs@gmail.com>
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
 * Customer order.
 */
class PedidoCliente
{

    use Base\DocumentoVenta;

    /**
     * Primary key.
     *
     * @var integer
     */
    public $idpedido;

    /**
     * Related delivery note ID.
     *
     * @var integer
     */
    public $idalbaran;

    /**
     * Order status:
     * 0 -> pending. (editable)
     * 1 -> approved. (there is an idalbaran and it is not editable)
     * 2 -> rejected. (there is no idalbaran and it is not editable)
     *
     * @var integer
     */
    public $status;

    /**
     * True if it is editable, but false
     *
     * @var bool
     */
    public $editable;

    /**
     * Expected date of departure of the material.
     *
     * @var string
     */
    public $fechasalida;

    /**
     * If this estimation is the version of another, the original estimation document is stored here.
     *
     * @var integer
     */
    public $idoriginal;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'pedidoscli';
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
        $this->clearDocumentoVenta();
        $this->status = 0;
        $this->editable = true;
        $this->fechasalida = null;
        $this->idoriginal = null;
    }

    /**
     * Returns the lines associated with the order.
     *
     * @return LineaPedidoCliente[]
     */
    public function getLineas()
    {
        $lineaModel = new LineaPedidoCliente();
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

        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE idoriginal = ' . self::$dataBase->var2str($this->idpedido);
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
        /// we check that editable corresponds to the status
        if ($this->idalbaran) {
            $this->status = 1;
            $this->editable = false;
        } elseif ($this->status == 0) {
            $this->editable = true;
        } elseif ($this->status == 2) {
            $this->editable = false;
        }

        return $this->testTrait();
    }

    /**
     * Remove the order from the database.
     * Returns False in case of failure.
     *
     * @return boolean
     */
    public function delete()
    {
        if (self::$dataBase->exec('DELETE FROM ' . static::tableName() . ' WHERE idpedido = ' . self::$dataBase->var2str($this->idpedido) . ';')) {
            /// we modify the related budget
            self::$dataBase->exec('UPDATE presupuestoscli SET idpedido = NULL, editable = TRUE,'
                . ' status = 0 WHERE idpedido = ' . self::$dataBase->var2str($this->idpedido) . ';');

            return true;
        }

        return false;
    }

    /**
     * Execute a task with cron
     */
    public function cronJob()
    {
        /// mark estimations approved as approved with idpedido
        self::$dataBase->exec('UPDATE ' . static::tableName() . " SET status = '1', editable = FALSE"
            . " WHERE status != '1' AND idalbaran IS NOT NULL;");

        /// return to the pending status the orders with status 1 to which the delivery note has been erased
        self::$dataBase->exec('UPDATE ' . static::tableName() . " SET status = '0', idalbaran = NULL, editable = TRUE "
            . "WHERE status = '1' AND idalbaran NOT IN (SELECT idalbaran FROM albaranescli);");

        /// mark as rejected all non-editable budgets and without associated order
        self::$dataBase->exec('UPDATE ' . static::tableName() . " SET status = '2' WHERE idalbaran IS NULL AND"
            . ' editable = false;');
    }
}
