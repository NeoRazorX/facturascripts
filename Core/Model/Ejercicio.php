<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\DataSrc\Ejercicios;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Empresa as DinEmpresa;

/**
 * Ejercicio contable. Es el periodo en el que se agrupan asientos, facturas, albaranes, etc.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Ejercicio extends ModelClass
{
    use ModelTrait;

    const EXERCISE_STATUS_OPEN = 'ABIERTO';
    const EXERCISE_STATUS_CLOSED = 'CERRADO';

    /** Clave primaria. Varchar(4). */
    public $codejercicio;

    /** Estado del ejercicio: ABIERTO|CERRADO */
    public $estado;

    /** Fecha de fin del ejercicio. */
    public $fechafin;

    /** Fecha de inicio del ejercicio. */
    public $fechainicio;

    /** ID del asiento de cierre. */
    public $idasientocierre;

    /** ID del asiento de pérdidas y ganancias. */
    public $idasientopyg;

    /** ID del asiento de apertura. */
    public $idasientoapertura;

    /** Clave ajena de la tabla Empresas. */
    public $idempresa;

    /** Longitud en caracteres de las subcuentas asignadas. */
    public $longsubcuenta;

    /** Nombre del ejercicio. */
    public $nombre;

    public function clear(): void
    {
        parent::clear();
        $this->estado = self::EXERCISE_STATUS_OPEN;
        $this->fechainicio = date('01-01-Y');
        $this->fechafin = date('31-12-Y');
        $this->longsubcuenta = 10;
        $this->nombre = '';
    }

    public function clearCache(): void
    {
        parent::clearCache();

        Ejercicios::clear();
    }

    /** Devuelve la fecha más cercana a $fecha que esté dentro del rango de este ejercicio. */
    public function getBestFecha(string $fecha, bool $showError = false): string
    {
        $fecha2 = strtotime($fecha);
        if ($fecha2 >= strtotime($this->fechainicio) && $fecha2 <= strtotime($this->fechafin)) {
            return $fecha;
        }

        if ($fecha2 > strtotime($this->fechainicio)) {
            if ($showError) {
                Tools::log()->warning('date-out-of-rage-selected-better');
            }

            return $this->fechafin;
        }

        if ($showError) {
            Tools::log()->warning('date-out-of-rage-selected-better');
        }

        return $this->fechainicio;
    }

    public function hasAccountingPlan(): bool
    {
        $where = [Where::eq('codejercicio', $this->codejercicio)];
        return Subcuenta::count($where) > 0;
    }

    public function install(): string
    {
        // dependencias necesarias
        new DinEmpresa();

        $code = $year = "'" . date('Y') . "'";
        $start = self::db()->var2str(date('01-01-Y'));
        $end = self::db()->var2str(date('31-12-Y'));
        $state = "'" . self::EXERCISE_STATUS_OPEN . "'";
        return 'INSERT INTO ' . static::tableName()
            . ' (codejercicio,nombre,fechainicio,fechafin,estado,longsubcuenta,idempresa)'
            . ' VALUES (' . $code . ',' . $year . ',' . $start . ',' . $end . ',' . $state . ',10,1);';
    }

    /** Comprueba si la fecha indicada está dentro del periodo del ejercicio. */
    public function inRange(string $dateToCheck): bool
    {
        $start = strtotime($this->fechainicio);
        $end = strtotime($this->fechafin);
        $date = strtotime($dateToCheck);
        return $date >= $start && $date <= $end;
    }

    /** Devuelve true si el ejercicio está abierto. */
    public function isOpened(): bool
    {
        return $this->estado === self::EXERCISE_STATUS_OPEN;
    }

    /** Carga el ejercicio para la fecha indicada. Si no existe, lo crea. Requiere idempresa informado. */
    public function loadFromDate(string $date, bool $onlyOpened = true, bool $create = true): bool
    {
        // guardamos estos datos porque loadFromCode() hace un clear()
        $idempresa = $this->idempresa;
        $long = $this->longsubcuenta;

        // buscamos el ejercicio para la fecha
        $where = [
            Where::eq('idempresa', $this->idempresa),
            Where::lte('fechainicio', $date),
            Where::gte('fechafin', $date),
        ];

        $order = [$this->primaryColumn() => 'DESC'];
        if ($this->loadWhere($where, $order) && ($this->isOpened() || !$onlyOpened)) {
            return true;
        }

        $this->idempresa = $idempresa;
        $this->longsubcuenta = $long;

        // si no existe, lo creamos
        if ($create && strtotime($date) >= 1) {
            return $this->createNew($date);
        }

        return false;
    }

    /** Devuelve el siguiente código para el campo o la clave primaria del modelo, formateado a 4 dígitos. */
    public function newCode(string $field = '', array $where = [])
    {
        $newCode = parent::newCode($field, $where);
        return sprintf('%04s', (int)$newCode);
    }

    public static function primaryColumn(): string
    {
        return 'codejercicio';
    }

    public static function tableName(): string
    {
        return 'ejercicios';
    }

    public function test(): bool
    {
        $this->codejercicio = trim($this->codejercicio);
        $this->nombre = Tools::noHtml($this->nombre);

        if (empty($this->idempresa)) {
            $this->idempresa = Tools::settings('default', 'idempresa');
        }

        if (1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,4}$/i', $this->codejercicio)) {
            Tools::log()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codejercicio, '%column%' => 'codejercicio', '%min%' => '1', '%max%' => '4']
            );
        } elseif (strlen($this->nombre) < 1 || strlen($this->nombre) > 100) {
            Tools::log()->warning(
                'invalid-column-lenght',
                ['%column%' => 'nombre', '%min%' => '1', '%max%' => '100']
            );
        } elseif ($this->longsubcuenta < 4 || $this->longsubcuenta > 15) {
            Tools::log()->warning(
                'invalid-column-lenght',
                ['%column%' => 'longsubcuenta', '%min%' => '4', '%max%' => '15']
            );
        } elseif (strtotime($this->fechainicio) > strtotime($this->fechafin)) {
            Tools::log()->warning('start-date-later-end-date', [
                '%endDate%' => $this->fechainicio,
                '%startDate%' => $this->fechafin
            ]);
        } elseif (strtotime($this->fechainicio) < 1) {
            Tools::log()->warning('invalid-date', ['%date%' => $this->fechainicio]);
        } else {
            return parent::test();
        }

        return false;
    }

    /** Devuelve el año del ejercicio. */
    public function year(): string
    {
        return date('Y', strtotime($this->fechainicio));
    }

    protected function createNew(string $date): bool
    {
        $date2 = strtotime($date);

        $year = date('Y', $date2);
        $year2 = date('y', $date2);

        $this->codejercicio = $year;
        $this->fechainicio = date('1-1-Y', $date2);
        $this->fechafin = date('31-12-Y', $date2);
        $this->nombre = $year;

        // si hay más de una empresa, añadimos el nombre de la empresa
        if (count(Empresas::all()) > 1) {
            $this->nombre = Empresas::get($this->idempresa)->nombrecorto . ' ' . $this->nombre;
        }

        // códigos alternativos: año, 00+yy, 0+idempresa+yy, 0001-9999
        $new = new static();
        if ($this->exists()) {
            $this->codejercicio = '00' . $year2;
        }
        if ($this->exists()) {
            $this->codejercicio = sprintf('%04s', '0' . $this->idempresa . $year2);
        }
        if ($this->exists()) {
            for ($num = 1; $num < 10000; $num++) {
                $code = sprintf('%04s', $num);
                if (false === $new->load($code)) {
                    $this->codejercicio = $code;
                    break;
                }
            }
        }

        return $this->save();
    }

    protected function saveInsert(): bool
    {
        $where = [Where::eq('idempresa', $this->idempresa)];
        foreach ($this->all($where, [], 0, 0) as $ejercicio) {
            if ($this->inRange($ejercicio->fechainicio) || $this->inRange($ejercicio->fechafin)) {
                Tools::log()->warning(
                    'exercise-date-range-exists', ['%start%' => $this->fechainicio, '%end%' => $this->fechafin]
                );
                return false;
            }
        }

        return parent::saveInsert();
    }
}
