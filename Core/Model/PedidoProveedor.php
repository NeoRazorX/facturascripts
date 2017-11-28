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
 * Pedido de proveedor
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
     * ID del albarán relacionado.
     *
     * @var int
     */
    public $idalbaran;

    /**
     * True si es editable, sino false
     *
     * @var bool
     */
    public $editable;

    /**
     * Si este presupuesto es la versión de otro, aquí se almacena el idpresupuesto del original.
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
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'idpedido';
    }

    /**
     * Crea la consulta necesaria para crear un nuevo agente en la base de datos.
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
     * Devuelve las líneas asociadas al pedido.
     *
     * @return LineaPedidoProveedor[]
     */
    public function getLineas()
    {
        $lineaModel = new LineaPedidoProveedor();
        return $lineaModel->all(new DataBaseWhere('idpedido', $this->idpedido));
    }

    /**
     * Devuelve las versiones de un pedido
     *
     * @return self[]
     */
    public function getVersiones()
    {
        $versiones = [];

        $sql = 'SELECT * FROM ' . static::tableName()
            . ' WHERE idoriginal = ' . $this->dataBase->var2str($this->idpedido);
        if ($this->idoriginal) {
            $sql .= ' OR idoriginal = ' . $this->dataBase->var2str($this->idoriginal);
            $sql .= ' OR idpedido = ' . $this->dataBase->var2str($this->idoriginal);
        }
        $sql .= 'ORDER BY fecha DESC, hora DESC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $versiones[] = new self($d);
            }
        }

        return $versiones;
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        return $this->testTrait();
    }

    /**
     * Ejecuta un test completo de pruebas
     *
     * @return bool
     */
    public function fullTest()
    {
        return $this->fullTestTrait('order');
    }

    /**
     * Ejecuta una tarea con cron
     */
    public function cronJob()
    {
        $sql = 'UPDATE ' . static::tableName() . ' SET idalbaran = NULL, editable = TRUE'
            . ' WHERE idalbaran IS NOT NULL AND NOT EXISTS(SELECT 1 FROM albaranesprov t1'
            . ' WHERE t1.idalbaran = ' . static::tableName() . '.idalbaran);';
        $this->dataBase->exec($sql);
    }
}
