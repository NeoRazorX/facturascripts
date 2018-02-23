<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Utils;

/**
 * Accounting year. It is the period in which accounting entry, invoices, delivery notes are grouped ...
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Ejercicio extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary key. Varchar(4).
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Name of the exercise.
     *
     * @var string
     */
    public $nombre;

    /**
     * Start date of the exercise.
     *
     * @var string with date format
     */
    public $fechainicio;

    /**
     * End date of the exercise.
     *
     * @var string with date format
     */
    public $fechafin;

    /**
     * Exercise status: ABIERTO|CERRADO
     *
     * @var string
     */
    public $estado;

    /**
     * Accounting entry ID of the year end.
     *
     * @var int
     */
    public $idasientocierre;

    /**
     * Profit and loss entry ID.
     *
     * @var int
     */
    public $idasientopyg;

    /**
     * Opening accounting entry ID.
     *
     * @var int
     */
    public $idasientoapertura;

    /**
     * Identify the accounting plan used. This is only necessary
     * to support Eneboo. In FacturaScripts it is not used.
     *
     * @var string
     */
    public $plancontable;

    /**
     * Length of characters of the subaccounts assigned.
     *
     * @var int
     */
    public $longsubcuenta;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'ejercicios';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codejercicio';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->nombre = '';
        $this->fechainicio = date('01-01-Y');
        $this->fechafin = date('31-12-Y');
        $this->estado = 'ABIERTO';
        $this->plancontable = '08';
        $this->longsubcuenta = 10;
    }

    /**
     * Returns the state of the exercise ABIERTO -> true | CLOSED -> false
     *
     * @return bool
     */
    public function abierto()
    {
        return $this->estado === 'ABIERTO';
    }

    /**
     * Returns the value of the year of the exercise.
     *
     * @return string en formato año
     */
    public function year()
    {
        return date('Y', strtotime($this->fechainicio));
    }

    /**
     * Returns a new code for an exercise.
     *
     * @param string $cod
     *
     * @return string
     */
    public function newCodigo($cod = '0001')
    {
        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE codejercicio = ' . self::$dataBase->var2str($cod) . ';';
        if (!self::$dataBase->select($sql)) {
            return $cod;
        }

        $sql = 'SELECT MAX(' . self::$dataBase->sql2Int('codejercicio') . ') as cod FROM ' . static::tableName() . ';';
        $newCod = self::$dataBase->select($sql);
        if (!empty($newCod)) {
            return sprintf('%04s', 1 + (int) $newCod[0]['cod']);
        }

        return '0001';
    }

    /**
     * Returns the date closest to $date that is within the range of this exercise.
     *
     * @param string $fecha
     * @param bool   $showError
     *
     * @return string
     */
    public function getBestFecha($fecha, $showError = false)
    {
        $fecha2 = strtotime($fecha);

        if ($fecha2 >= strtotime($this->fechainicio) && $fecha2 <= strtotime($this->fechafin)) {
            return $fecha;
        }

        if ($fecha2 > strtotime($this->fechainicio)) {
            if ($showError) {
                self::$miniLog->alert(self::$i18n->trans('date-out-of-rage-selected-better'));
            }

            return $this->fechafin;
        }

        if ($showError) {
            self::$miniLog->alert(self::$i18n->trans('date-out-of-rage-selected-better'));
        }

        return $this->fechainicio;
    }

    /**
     * Returns the exercise for the indicated date.
           * If it does not exist, create it.
     *
     * @param string $fecha
     * @param bool   $soloAbierto
     * @param bool   $crear
     *
     * @return bool|Ejercicio
     */
    public function getByFecha($fecha, $soloAbierto = true, $crear = true)
    {
        $sql = 'SELECT * FROM ' . static::tableName()
            . ' WHERE ' . self::$dataBase->var2str($fecha) . ' BETWEEN fechainicio AND fechafin;';

        $data = self::$dataBase->select($sql);
        if (empty($data)) {
            if ($crear && (strtotime($fecha) >= 1)) {
                $eje = new self();
                $eje->codejercicio = $eje->newCodigo(date('Y', strtotime($fecha)));
                $eje->nombre = date('Y', strtotime($fecha));
                $eje->fechainicio = date('1-1-Y', strtotime($fecha));
                $eje->fechafin = date('31-12-Y', strtotime($fecha));
                if ($eje->save()) {
                    return $eje;
                }
            }
            self::$miniLog->alert(self::$i18n->trans('invalid-date', ['%date%' => $fecha]));
            return false;
        }

        $eje = new self($data[0]);
        return ($eje->abierto() || $soloAbierto === false) ? $eje : false;
    }

    /**
     * Check the exercise data, return True if they are correct
     *
     * @return bool
     */
    public function test()
    {
        /// TODO: Change dates verify to $this->inRange() call
        $status = false;

        $this->codejercicio = trim($this->codejercicio);
        $this->nombre = Utils::noHtml($this->nombre);

        if (!preg_match('/^[A-Z0-9_]{1,4}$/i', $this->codejercicio)) {
            self::$miniLog->alert(self::$i18n->trans('fiscal-year-code-invalid'));
        } elseif (!(strlen($this->nombre) > 1) && !(strlen($this->nombre) < 100)) {
            self::$miniLog->alert(self::$i18n->trans('fiscal-year-name-invalid'));
        } elseif (strtotime($this->fechainicio) > strtotime($this->fechafin)) {
            $params = ['%endDate%' => $this->fechainicio, '%startDate%' => $this->fechafin];
            self::$miniLog->alert(self::$i18n->trans('start-date-later-end-date', $params));
        } elseif (strtotime($this->fechainicio) < 1) {
            self::$miniLog->alert(self::$i18n->trans('date-invalid'));
        } else {
            $status = true;
        }

        return $status;
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
        return 'INSERT INTO ' . static::tableName() . ' (codejercicio,nombre,fechainicio,fechafin,'
            . 'estado,longsubcuenta,plancontable,idasientoapertura,idasientopyg,idasientocierre) '
            . "VALUES ('" . date('Y') . "','" . date('Y') . "'," . self::$dataBase->var2str(date('01-01-Y'))
            . ', ' . self::$dataBase->var2str(date('31-12-Y')) . ",'ABIERTO',10,'08',null,null,null);";
    }

    /**
     * Check if the indicated date is within the period of the exercise dates
     *
     * @param string $dateToCheck        (string with date format)
     * @return bool
     */
    public function inRange($dateToCheck): bool
    {
        $start = strtotime($this->fechainicio);
        $end = strtotime($this->fechafin);
        $date = strtotime($dateToCheck);
        return (($date >= $start) && ($date <= $end));
    }
}
