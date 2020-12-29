<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Empresa as DinEmpresa;

/**
 * Accounting year. It is the period in which accounting entry, invoices, delivery notes are grouped ...
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Ejercicio extends Base\ModelClass
{

    use Base\ModelTrait;

    const EXERCISE_STATUS_OPEN = 'ABIERTO';
    const EXERCISE_STATUS_CLOSED = 'CERRADO';

    /**
     * Primary key. Varchar(4).
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Exercise status: ABIERTO|CERRADO
     *
     * @var string
     */
    public $estado;

    /**
     * End date of the exercise.
     *
     * @var string with date format
     */
    public $fechafin;

    /**
     * Start date of the exercise.
     *
     * @var string with date format
     */
    public $fechainicio;

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
     * Foreign Key with Empresas table.
     *
     * @var int
     */
    public $idempresa;

    /**
     * Length of characters of the subaccounts assigned.
     *
     * @var int
     */
    public $longsubcuenta;

    /**
     * Name of the exercise.
     *
     * @var string
     */
    public $nombre;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->estado = self::EXERCISE_STATUS_OPEN;
        $this->fechainicio = \date('01-01-Y');
        $this->fechafin = \date('31-12-Y');
        $this->longsubcuenta = 10;
        $this->nombre = '';
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
        $fecha2 = \strtotime($fecha);
        if ($fecha2 >= \strtotime($this->fechainicio) && $fecha2 <= \strtotime($this->fechafin)) {
            return $fecha;
        }

        if ($fecha2 > \strtotime($this->fechainicio)) {
            if ($showError) {
                $this->toolBox()->i18nLog()->warning('date-out-of-rage-selected-better');
            }

            return $this->fechafin;
        }

        if ($showError) {
            $this->toolBox()->i18nLog()->warning('date-out-of-rage-selected-better');
        }

        return $this->fechainicio;
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
        /// needed dependecies
        new DinEmpresa();

        $code = $year = "'" . \date('Y') . "'";
        $start = self::$dataBase->var2str(\date('01-01-Y'));
        $end = self::$dataBase->var2str(\date('31-12-Y'));
        $state = "'" . self::EXERCISE_STATUS_OPEN . "'";
        return 'INSERT INTO ' . static::tableName()
            . ' (codejercicio,nombre,fechainicio,fechafin,estado,longsubcuenta,idempresa)'
            . ' VALUES (' . $code . ',' . $year . ',' . $start . ',' . $end . ',' . $state . ',10,1);';
    }

    /**
     * Check if the indicated date is within the period of the exercise dates
     *
     * @param string $dateToCheck        (string with date format)
     *
     * @return bool
     */
    public function inRange($dateToCheck): bool
    {
        $start = \strtotime($this->fechainicio);
        $end = \strtotime($this->fechafin);
        $date = \strtotime($dateToCheck);
        return $date >= $start && $date <= $end;
    }

    /**
     * Returns the state of the exercise OPEN -> true | CLOSED -> false
     *
     * @return bool
     */
    public function isOpened()
    {
        return $this->estado === self::EXERCISE_STATUS_OPEN;
    }

    /**
     * Load the exercise for the indicated date. If it does not exist, create it.
     * <bold>Need the company id to be correctly informed</bold>
     *
     * @param string $date
     * @param bool   $onlyOpened
     * @param bool   $create
     *
     * @return bool
     */
    public function loadFromDate($date, $onlyOpened = true, $create = true): bool
    {
        /// we need this data because loadfromcode() makes a clear()
        $idempresa = $this->idempresa;
        $long = $this->longsubcuenta;

        /// Search for fiscal year for date
        $where = [
            new DataBaseWhere('idempresa', $this->idempresa),
            new DataBaseWhere('fechainicio', $date, '<='),
            new DataBaseWhere('fechafin', $date, '>=')
        ];

        $order = [$this->primaryColumn() => 'DESC'];
        if ($this->loadFromCode('', $where, $order) && ($this->isOpened() || !$onlyOpened)) {
            return true;
        }

        $this->idempresa = $idempresa;
        $this->longsubcuenta = $long;

        /// If must be register
        if ($create && \strtotime($date) >= 1) {
            return $this->createNew($date);
        }

        return false;
    }

    /**
     * Returns the following code for the reported field or the primary key of the model.
     * (Formated to 4 digits)
     *
     * @param string $field
     * @param array  $where
     *
     * @return string
     */
    public function newCode(string $field = '', array $where = [])
    {
        $newCode = parent::newCode($field, $where);
        return \sprintf('%04s', (int) $newCode);
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
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'ejercicios';
    }

    /**
     * Check the exercise data, return True if they are correct
     *
     * @return bool
     */
    public function test()
    {
        /// TODO: Change dates verify to $this->inRange() call
        $this->codejercicio = \trim($this->codejercicio);
        $this->nombre = $this->toolBox()->utils()->noHtml($this->nombre);

        if (empty($this->idempresa)) {
            $this->idempresa = $this->toolBox()->appSettings()->get('default', 'idempresa');
        }

        if (1 !== \preg_match('/^[A-Z0-9_\+\.\-]{1,4}$/i', $this->codejercicio)) {
            $this->toolBox()->i18nLog()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codejercicio, '%column%' => 'codejercicio', '%min%' => '1', '%max%' => '4']
            );
        } elseif (\strlen($this->nombre) < 1 || \strlen($this->nombre) > 100) {
            $this->toolBox()->i18nLog()->warning(
                'invalid-column-lenght',
                ['%column%' => 'nombre', '%min%' => '1', '%max%' => '100']
            );
        } elseif (\strtotime($this->fechainicio) > \strtotime($this->fechafin)) {
            $params = ['%endDate%' => $this->fechainicio, '%startDate%' => $this->fechafin];
            $this->toolBox()->i18nLog()->warning('start-date-later-end-date', $params);
        } elseif (\strtotime($this->fechainicio) < 1) {
            $this->toolBox()->i18nLog()->warning('date-invalid');
        } else {
            return parent::test();
        }

        return false;
    }

    /**
     * Returns the value of the year of the exercise.
     *
     * @return string en formato año
     */
    public function year()
    {
        return \date('Y', \strtotime($this->fechainicio));
    }

    /**
     * 
     * @param string $date
     *
     * @return bool
     */
    protected function createNew($date)
    {
        $date2 = \strtotime($date);

        $this->codejercicio = \date('Y', $date2);
        $this->fechainicio = \date('1-1-Y', $date2);
        $this->fechafin = \date('31-12-Y', $date2);
        $this->nombre = \date('Y', $date2);

        /// for non-default companies we try to use range from 0001 to 9999
        if ($this->idempresa != $this->toolBox()->appSettings()->get('default', 'idempresa')) {
            $new = new static();
            for ($num = 1; $num < 1000; $num++) {
                $code = \sprintf('%04s', (int) $num);
                if (false === $new->loadFromCode($code)) {
                    $this->codejercicio = $code;
                    break;
                }
            }
        }

        if ($this->exists()) {
            $this->codejercicio = $this->newCode();
        }

        return $this->save();
    }

    /**
     * Insert the model data in the database.
     *
     * @param array $values
     * 
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        $where = [new DataBaseWhere('idempresa', $this->idempresa)];
        foreach ($this->all($where, [], 0, 0) as $ejercicio) {
            if ($this->inRange($ejercicio->fechainicio) || $this->inRange($ejercicio->fechafin)) {
                $this->toolBox()->i18nLog()->warning(
                    'exercise-date-range-exists', ['%start%' => $this->fechainicio, '%end%' => $this->fechafin]
                );
                return false;
            }
        }

        return parent::saveInsert($values);
    }
}
