<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2016-2017    Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class LineaTransferenciaStock
{

    use Base\ModelTrait;

    /**
     * Clave primaria. integer
     *
     * @var int
     */
    public $idlinea;

    /**
     * Identificador de transferéncia
     *
     * @var int
     */
    public $idtrans;

    /**
     * Referencia
     *
     * @var string
     */
    public $referencia;

    /**
     * Cantidad
     *
     * @var float|int
     */
    public $cantidad;

    /**
     * Descripción de la transferencia.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Fecha
     *
     * @var string
     */
    private $fecha;

    /**
     * Hora
     *
     * @var string
     */
    private $hora;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'lineastranstocks';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
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
     * Devuelve la fecha
     *
     * @return string
     */
    public function fecha()
    {
        return $this->fecha;
    }

    /**
     * Devuelve la hora
     *
     * @return string
     */
    public function hora()
    {
        return $this->hora;
    }

    /**
     * Devuelve todas las líneas de transferéncia de stock
     *
     * @param string $ref
     * @param string $codalmaorigen
     * @param string $codalmadestino
     * @param string $desde
     * @param string $hasta
     *
     * @return self[]
     */
    public function allFromReferencia($ref, $codalmaorigen = '', $codalmadestino = '', $desde = '', $hasta = '')
    {
        $list = [];

        $sql = 'SELECT l.idlinea,l.idtrans,l.referencia,l.cantidad,l.descripcion,t.fecha,t.hora FROM lineastransstock l'
            . ' LEFT JOIN transstock t ON l.idtrans = t.idtrans'
            . ' WHERE l.referencia = ' . $this->dataBase->var2str($ref);
        if (!empty($codalmaorigen)) {
            $sql .= ' AND t.codalmaorigen = ' . $this->dataBase->var2str($codalmaorigen);
        }
        if (!empty($codalmadestino)) {
            $sql .= ' AND t.codalmadestino = ' . $this->dataBase->var2str($codalmadestino);
        }
        if (!empty($desde)) {
            $sql .= ' AND t.fecha >= ' . $this->dataBase->var2str($desde);
        }
        if (!empty($hasta)) {
            $sql .= ' AND t.fecha >= ' . $this->dataBase->var2str($hasta);
        }
        $sql .= ' ORDER BY t.fecha ASC, t.hora ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $list[] = new self($d);
            }
        }

        return $list;
    }

    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     *
     * @return string
     */
    public function install()
    {
        /// forzamos la comprobación de la tabla de transferencias de stock
        //new TransferenciaStock();

        return '';
    }
}
