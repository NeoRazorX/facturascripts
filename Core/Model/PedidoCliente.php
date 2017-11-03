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
     * True si es editable, sino false
     *
     * @var bool
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

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'pedidoscli';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'idpedido';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
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
     * Devuelve las líneas asociadas al pedido.
     *
     * @return LineaPedidoCliente[]
     */
    public function getLineas()
    {
        $lineaModel = new LineaPedidoCliente();
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

        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE idoriginal = ' . $this->var2str($this->idpedido);
        if ($this->idoriginal) {
            $sql .= ' OR idoriginal = ' . $this->var2str($this->idoriginal);
            $sql .= ' OR idpedido = ' . $this->var2str($this->idoriginal);
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
     * Comprueba los datos del pedido, devuelve True si es correcto
     *
     * @return boolean
     */
    public function test()
    {
        /// comprobamos que editable se corresponda con el status
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
     * Elimina el pedido de la base de datos.
     * Devuelve False en caso de fallo.
     *
     * @return boolean
     */
    public function delete()
    {
        if ($this->dataBase->exec('DELETE FROM ' . $this->tableName() . ' WHERE idpedido = ' . $this->var2str($this->idpedido) . ';')) {
            /// modificamos el presupuesto relacionado
            $this->dataBase->exec('UPDATE presupuestoscli SET idpedido = NULL, editable = TRUE,'
                . ' status = 0 WHERE idpedido = ' . $this->var2str($this->idpedido) . ';');

            return true;
        }

        return false;
    }

    /**
     * Ejecuta una tarea con cron
     */
    public function cronJob()
    {
        /// marcamos como aprobados los presupuestos con idpedido
        $this->dataBase->exec('UPDATE ' . $this->tableName() . " SET status = '1', editable = FALSE"
            . " WHERE status != '1' AND idalbaran IS NOT NULL;");

        /// devolvemos al estado pendiente a los pedidos con estado 1 a los que se haya borrado el albarán
        $this->dataBase->exec('UPDATE ' . $this->tableName() . " SET status = '0', idalbaran = NULL, editable = TRUE "
            . "WHERE status = '1' AND idalbaran NOT IN (SELECT idalbaran FROM albaranescli);");

        /// marcamos como rechazados todos los presupuestos no editables y sin pedido asociado
        $this->dataBase->exec('UPDATE ' . $this->tableName() . " SET status = '2' WHERE idalbaran IS NULL AND"
            . ' editable = false;');
    }
}
