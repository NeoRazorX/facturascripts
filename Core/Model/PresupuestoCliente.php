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
 * Customer estimation.
 */
class PresupuestoCliente
{

    use Base\DocumentoVenta;

    /**
     * Primary key.
     *
     * @var integer
     */
    public $idpresupuesto;

    /**
     * Related order ID, if any.
     *
     * @var integer
     */
    public $idpedido;

    /**
     * Date on which the validity of the estimation ends.
     *
     * @var string
     */
    public $finoferta;

    /**
     * Estimation status:
     * 0 -> pending. (editable)
     * 1 -> approved. (there is a code and it is not editable)
     * 2 -> rejected. (there is no code and it is not editable)
     *
     * @var integer
     */
    public $status;

    /**
     * True if it is editable, but false.
     *
     * @var bool
     */
    public $editable;

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
        return 'presupuestoscli';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'idpresupuesto';
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
        $this->finoferta = date('d-m-Y', strtotime(date('d-m-Y') . ' +1 month'));
        $this->status = 0;
        $this->editable = true;
    }

    /**
     * Returns True if the offer date is less than the current one, but False.
     *
     * @return bool
     */
    public function finoferta()
    {
        return strtotime(date('d-m-Y')) > strtotime($this->finoferta);
    }

    /**
     * Returns the lines associated with the estimation.
     *
     * @return LineaPresupuestoCliente[]
     */
    public function getLineas()
    {
        $lineaModel = new LineaPresupuestoCliente();
        $where = [new DataBaseWhere('idpresupuesto', $this->idpresupuesto)];
        $order = ['orden' => 'DESC', 'idlinea' => 'ASC'];
        return $lineaModel->all($where, $order);
    }

    /**
     * Returns the versions of a estimation.
     *
     * @return self[]
     */
    public function getVersiones()
    {
        $versiones = [];

        $sql = 'SELECT * FROM ' . static::tableName()
            . ' WHERE idoriginal = ' . self::$dataBase->var2str($this->idpresupuesto);
        if ($this->idoriginal) {
            $sql .= ' OR idoriginal = ' . self::$dataBase->var2str($this->idoriginal);
            $sql .= ' OR idpresupuesto = ' . self::$dataBase->var2str($this->idoriginal);
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
     * Check the estimation data, return True if it is correct.
     *
     * @return boolean
     */
    public function test()
    {
        /// check that editable corresponds to the status
        if ($this->idpedido) {
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
     * Execute a task with cron
     */
    public function cronJob()
    {
        /// mark estimations approved as approved
        self::$dataBase->exec('UPDATE ' . static::tableName() . " SET status = '1', editable = FALSE"
            . " WHERE status != '1' AND idpedido IS NOT NULL;");

        /// return to the pending status the estimations with state 1 to which the order has been deleted
        self::$dataBase->exec('UPDATE ' . static::tableName() . " SET status = '0', idpedido = NULL, editable = TRUE"
            . " WHERE status = '1' AND idpedido NOT IN (SELECT idpedido FROM pedidoscli);");

        /// mark as rejected all estimations with endoffer past
        self::$dataBase->exec('UPDATE ' . static::tableName() . " SET status = '2' WHERE finoferta IS NOT NULL AND"
            . ' finoferta < ' . self::$dataBase->var2str(date('d-m-Y')) . ' AND idpedido IS NULL;');

        /// mark as rejected all non-editable estimations and without associated order
        self::$dataBase->exec("UPDATE " . static::tableName() . " SET status = '2' WHERE idpedido IS NULL AND"
            . ' editable = false;');
    }
}
