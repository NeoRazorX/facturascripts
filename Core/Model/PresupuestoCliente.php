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
 * Presupuesto de cliente
 */
class PresupuestoCliente
{

    use Base\DocumentoVenta;

    /**
     * Clave primaria.
     *
     * @var integer
     */
    public $idpresupuesto;

    /**
     * ID del pedido relacionado, si lo hay.
     *
     * @var integer
     */
    public $idpedido;

    /**
     * Fecha en la que termina la validéz del presupuesto.
     *
     * @var string
     */
    public $finoferta;

    /**
     * Estado del presupuesto:
     * 0 -> pendiente. (editable)
     * 1 -> aprobado. (hay un idpedido y no es editable)
     * 2 -> rechazado. (no hay idpedido y no es editable)
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
        return 'presupuestoscli';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'idpresupuesto';
    }
    
    public function install()
    {
        new Serie();
        new Ejercicio();
        
        return '';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->clearDocumentoVenta();
        $this->finoferta = date('d-m-Y', strtotime(date('d-m-Y') . ' +1 month'));
        $this->status = 0;
        $this->editable = true;
    }

    /**
     * Devuelve True si la fecha de oferta es menor a la actual, sino False
     *
     * @return bool
     */
    public function finoferta()
    {
        return strtotime(date('d-m-Y')) > strtotime($this->finoferta);
    }

    /**
     * Devuelve las líneas asociadas al presupuesto.
     *
     * @return LineaPresupuestoCliente[]
     */
    public function getLineas()
    {
        $lineaModel = new LineaPresupuestoCliente();
        return $lineaModel->all(new DataBaseWhere('idpresupuesto', $this->idpresupuesto));
    }

    /**
     * Devuelve las versiones de un presupuesto
     *
     * @return self[]
     */
    public function getVersiones()
    {
        $versiones = [];

        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE idoriginal = ' . $this->var2str($this->idpresupuesto);
        if ($this->idoriginal) {
            $sql .= ' OR idoriginal = ' . $this->var2str($this->idoriginal);
            $sql .= ' OR idpresupuesto = ' . $this->var2str($this->idoriginal);
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
     * Comprueba los datos del presupuesto, devuelve True si está correcto
     *
     * @return boolean
     */
    public function test()
    {
        /// comprobamos que editable se corresponda con el status
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
     * Ejecuta una tarea con cron
     */
    public function cronJob()
    {
        /// marcamos como aprobados los presupuestos con idpedido
        $this->dataBase->exec('UPDATE ' . $this->tableName() . " SET status = '1', editable = FALSE"
            . " WHERE status != '1' AND idpedido IS NOT NULL;");

        /// devolvemos al estado pendiente a los presupuestos con estado 1 a los que se haya borrado el pedido
        $this->dataBase->exec('UPDATE ' . $this->tableName() . " SET status = '0', idpedido = NULL, editable = TRUE"
            . " WHERE status = '1' AND idpedido NOT IN (SELECT idpedido FROM pedidoscli);");

        /// marcamos como rechazados todos los presupuestos con finoferta ya pasada
        $this->dataBase->exec('UPDATE ' . $this->tableName() . " SET status = '2' WHERE finoferta IS NOT NULL AND"
            . ' finoferta < ' . $this->var2str(date('d-m-Y')) . ' AND idpedido IS NULL;');

        /// marcamos como rechazados todos los presupuestos no editables y sin pedido asociado
        $this->dataBase->exec("UPDATE " . $this->tableName() . " SET status = '2' WHERE idpedido IS NULL AND"
            . ' editable = false;');
    }
}
