<?php
/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2017    Carlos Garcia Gomez        neorazorx@gmail.com
 * Copyright (C) 2014         Francesc Pineda Segarra    shawe.ewahs@gmail.com
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
 * Pedido de cliente
 */
class PedidoCliente
{
    use Base\DocumentoVenta;

    /**
     * Clave primaria.
     *
     * @var integer
     */
    public $idpedido;

    /**
     * ID del albarán relacionado.
     *
     * @var integer
     */
    public $idalbaran;

    /**
     * Estado del pedido:
     * 0 -> pendiente. (editable)
     * 1 -> aprobado. (hay un idalbaran y no es editable)
     * 2 -> rechazado. (no hay idalbaran y no es editable)
     *
     * @var integer
     */
    public $status;
    
    /**
     *
     * @var boolean
     */
    public $editable;

    /**
     * Fecha de salida prevista del material.
     *
     * @var string
     */
    public $fechasalida;

    /**
     * Si este presupuesto es la versión de otro, aquí se almacena el idpresupuesto del original.
     *
     * @var integer
     */
    public $idoriginal;

    public function tableName()
    {
        return 'pedidoscli';
    }

    public function primaryColumn()
    {
        return 'idpedido';
    }

    public function clear()
    {
        $this->clearDocumentoVenta();
        $this->status = 0;
        $this->editable = TRUE;
        $this->fechasalida = NULL;
        $this->idoriginal = NULL;
    }

    /**
     * Devuelve las líneas del pedido.
     *
     * @return \LineaPedidoCliente
     */
    public function getLineas()
    {
        $lineaModel = new LineaPedidoCliente();
        return $lineaModel->all(new DataBaseWhere('idpedido', $this->idpedido));
    }

    public function getVersiones()
    {
        $versiones = [];

        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE idoriginal = ' . $this->var2str($this->idpedido);
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
     * Comprueba los datos del pedido, devuelve TRUE si está todo correcto
     *
     * @return boolean
     */
    public function test()
    {
        /// comprobamos que editable se corresponda con el status
        if ($this->idalbaran) {
            $this->status = 1;
            $this->editable = FALSE;
        } elseif ($this->status == 0) {
            $this->editable = TRUE;
        } elseif ($this->status == 2) {
            $this->editable = FALSE;
        }

        return $this->testTrait();
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

    /**
     * Elimina el pedido de la base de datos.
     * Devuelve FALSE en caso de fallo.
     *
     * @return boolean
     */
    public function delete()
    {
        if ($this->db->exec('DELETE FROM ' . $this->tableName() . ' WHERE idpedido = ' . $this->var2str($this->idpedido) . ';')) {
            /// modificamos el presupuesto relacionado
            $this->db->exec('UPDATE presupuestoscli SET idpedido = NULL, editable = TRUE,'
                . ' status = 0 WHERE idpedido = ' . $this->var2str($this->idpedido) . ';');

            $this->new_message(ucfirst(FS_PEDIDO) . ' de venta ' . $this->codigo . ' eliminado correctamente.');

            return TRUE;
        }

        return FALSE;
    }

    public function cronJob()
    {
        /// marcamos como aprobados los presupuestos con idpedido
        $this->db->exec('UPDATE ' . $this->tableName() . " SET status = '1', editable = FALSE"
            . " WHERE status != '1' AND idalbaran IS NOT NULL;");

        /// devolvemos al estado pendiente a los pedidos con estado 1 a los que se haya borrado el albarán
        $this->db->exec('UPDATE ' . $this->tableName() . " SET status = '0', idalbaran = NULL, editable = TRUE "
            . "WHERE status = '1' AND idalbaran NOT IN (SELECT idalbaran FROM albaranescli);");

        /// marcamos como rechazados todos los presupuestos no editables y sin pedido asociado
        $this->db->exec("UPDATE pedidoscli SET status = '2' WHERE idalbaran IS NULL AND"
            . ' editable = false;');
    }
}
