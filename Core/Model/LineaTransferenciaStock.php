<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2016-2017    Carlos Garcia Gomez  neorazorx@gmail.com
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
 * Description of linea_transferencia_stock
 *
 * @author Carlos García Gómez
 */
class LineaTransferenciaStock
{

    use Base\ModelTrait;

    /// clave primaria. integer
    /**
     * TODO
     * @var int
     */
    public $idlinea;

    /**
     * TODO
     * @var int
     */
    public $idtrans;

    /**
     * TODO
     * @var string
     */
    public $referencia;

    /**
     * TODO
     * @var float
     */
    public $cantidad;

    /**
     * TODO
     * @var string
     */
    public $descripcion;

    /**
     * TODO
     * @var string
     */
    private $fecha;

    /**
     * TODO
     * @var string
     */
    private $hora;

    public function tableName()
    {
        return 'lineastranstocks';
    }

    public function primaryColumn()
    {
        return 'idlinea';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->idlinea = null;
        $this->idtrans = null;
        $this->referencia = null;
        $this->cantidad = 0;
        $this->descripcion = null;
        $this->fecha = null;
        $this->hora = null;
    }

    /**
     * TODO
     * @return string
     */
    public function fecha()
    {
        return $this->fecha;
    }

    /**
     * TODO
     * @return string
     */
    public function hora()
    {
        return $this->hora;
    }

    /**
     * TODO
     *
     * @param string $ref
     * @param string $codalmaorigen
     * @param string $codalmadestino
     * @param string $desde
     * @param string $hasta
     *
     * @return array
     */
    public function allFromReferencia($ref, $codalmaorigen = '', $codalmadestino = '', $desde = '', $hasta = '')
    {
        $list = [];

        $sql = 'SELECT l.idlinea,l.idtrans,l.referencia,l.cantidad,l.descripcion,t.fecha,t.hora FROM lineastransstock l'
            . ' LEFT JOIN transstock t ON l.idtrans = t.idtrans'
            . ' WHERE l.referencia = ' . $this->var2str($ref);
        if (!empty($codalmaorigen)) {
            $sql .= ' AND t.codalmaorigen = ' . $this->var2str($codalmaorigen);
        }
        if (!empty($codalmadestino)) {
            $sql .= ' AND t.codalmadestino = ' . $this->var2str($codalmadestino);
        }
        if (!empty($desde)) {
            $sql .= ' AND t.fecha >= ' . $this->var2str($desde);
        }
        if (!empty($hasta)) {
            $sql .= ' AND t.fecha >= ' . $this->var2str($hasta);
        }
        $sql .= ' ORDER BY t.fecha ASC, t.hora ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $list[] = new LineaTransferenciaStock($d);
            }
        }

        return $list;
    }

    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     * @return string
     */
    public function install()
    {
        /// forzamos la comprobación de la tabla de transferencias de stock
        //new TransferenciaStock();

        return '';
    }
}
