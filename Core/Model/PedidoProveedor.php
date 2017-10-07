<?php
/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2017  Carlos Garcia Gomez       neorazorx@gmail.com
 * Copyright (C) 2014-2015  Francesc Pineda Segarra   shawe.ewahs@gmail.com
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
     * Clave primaria.
     *
     * @var type
     */
    public $idpedido;

    /**
     * ID del albarán relacionado.
     *
     * @var type
     */
    public $idalbaran;

    /**
     * Indica si se puede editar o no.
     *
     * @var type
     */
    public $editable;

    /**
     * Si este presupuesto es la versión de otro, aquí se almacena el idpresupuesto del original.
     *
     * @var type
     */
    public $idoriginal;

    public function tableName()
    {
        return 'pedidosprov';
    }

    public function primaryColumn()
    {
        return 'idpedido';
    }

    public function clear()
    {
        $this->clearDocumentoCompra();
        $this->editable = TRUE;
    }

    public function getLineas()
    {
        $lineaModel = new LineaPedidoProveedor();
        return $lineaModel->all(new DataBaseWhere('idpedido', $this->idpedido));
    }

    public function get_versiones()
    {
        $versiones = [];

        $sql = 'SELECT * FROM ' . $this->table_name . ' WHERE idoriginal = ' . $this->var2str($this->idpedido);
        if ($this->idoriginal) {
            $sql .= ' OR idoriginal = ' . $this->var2str($this->idoriginal);
            $sql .= ' OR idpedido = ' . $this->var2str($this->idoriginal);
        }
        $sql .= 'ORDER BY fecha DESC, hora DESC;';

        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $versiones[] = new self($d);
            }
        }

        return $versiones;
    }

    /**
     * Comprueba los daros del pedido, devuelve TRUE si está todo correcto
     *
     * @return boolean
     */
    public function test()
    {
        return $this->testTrait();
    }
    
    public function fullTest()
    {
        return $this->fullTestTrait('order');
    }

    public function save()
    {
        if ($this->test()) {
            if ($this->exists()) {
                return $this->saveUpdate();
            }

            $this->newCodigo();
            return $this->saveInsert();
        }

        return FALSE;
    }

    public function cron_job()
    {
        $sql = 'UPDATE ' . $this->tableName() . ' SET idalbaran = NULL, editable = TRUE'
            . ' WHERE idalbaran IS NOT NULL AND NOT EXISTS(SELECT 1 FROM albaranesprov t1 WHERE t1.idalbaran = ' . $this->tableName() . '.idalbaran);';
        $this->db->exec($sql);
    }
}
