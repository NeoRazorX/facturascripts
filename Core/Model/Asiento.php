<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\Diario as DinDiario;
use FacturaScripts\Dinamic\Model\Ejercicio as DinEjercicio;
use FacturaScripts\Dinamic\Model\Partida as DinPartida;
use FacturaScripts\Dinamic\Model\RegularizacionImpuesto as DinRegularizacionImpuesto;

/**
 * The accounting entry. It is related to an exercise and consists of games.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class Asiento extends Base\ModelOnChangeClass implements Base\GridModelInterface
{

    use Base\ModelTrait;
    use Base\ExerciseRelationTrait;

    const OPERATION_GENERAL = null;
    const OPERATION_OPENING = 'A';
    const OPERATION_CLOSING = 'C';
    const OPERATION_REGULARIZATION = 'R';
    const RENUMBER_LIMIT = 1000;

    /**
     * Accounting channel. For statisctics purpose.
     *
     * @var int
     */
    public $canal;

    /**
     * Accounting entry concept.
     *
     * @var string
     */
    public $concepto;

    /**
     * Document associated with the accounting entry.
     *
     * @var string
     */
    public $documento;

    /**
     * True if it is editable, but false.
     *
     * @var bool
     */
    public $editable;

    /**
     * Date of the accounting entry.
     *
     * @var string
     */
    public $fecha;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idasiento;

    /**
     * Diary identifier.
     *
     * @var int
     */
    public $iddiario;

    /**
     * Foreign Key with Empresas table.
     *
     * @var int
     */
    public $idempresa;

    /**
     * Amount of the accounting entry.
     *
     * @var float|int
     */
    public $importe;

    /**
     * Accounting entry number. It will be modified when renumbering.
     *
     * @var string
     */
    public $numero;

    /**
     * It establishes whether the accounting entry is of a special operation:
     * - opening
     * - regularization
     * - closing
     *
     * @var string
     */
    public $operacion;

    /**
     * Accumulate the amounts of the detail in the document
     *
     * @param array $detail
     */
    public function accumulateAmounts(array &$detail)
    {
        $haber = isset($detail['haber']) ? (float) $detail['haber'] : 0.0;
        $this->importe += round($haber, (int) FS_NF0);
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->editable = true;
        $this->fecha = \date(self::DATE_STYLE);
        $this->idempresa = $this->toolBox()->appSettings()->get('default', 'idempresa');
        $this->importe = 0.0;
        $this->numero = '';
        $this->operacion = self::OPERATION_GENERAL;
    }

    /**
     * Remove the accounting entry.
     *
     * @return bool
     */
    public function delete()
    {
        if (false === $this->getExercise()->isOpened()) {
            $this->toolBox()->i18nLog()->warning('closed-exercise', ['%exerciseName%' => $this->getExercise()->nombre]);
            return false;
        }

        $reg = new DinRegularizacionImpuesto();
        if ($reg->loadFechaInside($this->fecha) && $reg->bloquear) {
            $this->toolBox()->i18nLog()->warning('accounting-within-regularization');
            return false;
        }

        if (false === $this->isEditable()) {
            $this->toolBox()->i18nLog()->warning('non-editable-accounting-entry');
            return false;
        }

        /// forze delete lines to update subaccounts
        foreach ($this->getLines() as $line) {
            $line->delete();
        }

        return parent::delete();
    }

    /**
     *
     * @return DinPartida[]
     */
    public function getLines()
    {
        $partida = new DinPartida();
        $where = [new DataBaseWhere('idasiento', $this->idasiento)];
        return $partida->all($where, ['codsubcuenta' => 'ASC'], 0, 0);
    }

    /**
     *
     * @return DinPartida
     */
    public function getNewLine()
    {
        $partida = new DinPartida();
        $partida->concepto = $this->concepto;
        $partida->documento = $this->documento;
        $partida->idasiento = $this->primaryColumnValue();
        return $partida;
    }

    /**
     * Initializes the total fields
     */
    public function initTotals()
    {
        $this->importe = 0.0;
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
        new DinDiario();
        new DinEjercicio();

        return parent::install();
    }

    /**
     * Returns TRUE if accounting entry is balanced.
     * 
     * @return bool
     */
    public function isBalanced(): bool
    {
        $debe = 0.0;
        $haber = 0.0;
        foreach ($this->getLines() as $line) {
            $debe += $line->debe;
            $haber += $line->haber;
        }

        return $this->toolBox()->utils()->floatcmp($debe, $haber, \FS_NF0, true);
    }

    /**
     * Returns the following code for the reported field or the primary key of the model.
     *
     * @param string $field
     * @param array  $where
     *
     * @return int
     */
    public function newCode(string $field = '', array $where = [])
    {
        if ($field !== $this->primaryColumn()) {
            $where[] = new DataBaseWhere('codejercicio', $this->codejercicio);
        }
        return parent::newCode($field, $where);
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idasiento';
    }

    /**
     * Returns the name of the column that describes the model, such as name, description...
     *
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        return 'numero';
    }

    /**
     * Re-number the accounting entries of the open exercises.
     *
     * @param string $codejercicio
     *
     * @return bool
     */
    public function renumber($codejercicio = '')
    {
        $exerciseModel = new DinEjercicio();
        $where = empty($codejercicio) ? [] : [new DataBaseWhere('codejercicio', $codejercicio)];
        foreach ($exerciseModel->all($where) as $exe) {
            if (false === $exe->isOpened()) {
                continue;
            }

            $offset = 0;
            $number = 1;
            $sql = 'SELECT idasiento,numero,fecha FROM ' . static::tableName()
                . ' WHERE codejercicio = ' . self::$dataBase->var2str($exe->codejercicio)
                . ' ORDER BY codejercicio ASC, fecha ASC, idasiento ASC';

            $rows = self::$dataBase->selectLimit($sql, self::RENUMBER_LIMIT, $offset);
            while (!empty($rows)) {
                if (false === $this->renumberAccEntries($rows, $number)) {
                    $this->toolBox()->i18nLog()->warning('renumber-accounting-error', ['%exerciseCode%' => $exe->codejercicio]);
                    return false;
                }

                $offset += self::RENUMBER_LIMIT;
                $rows = self::$dataBase->selectLimit($sql, self::RENUMBER_LIMIT, $offset);
            }
        }

        return true;
    }

    /**
     * 
     * @return bool
     */
    public function save()
    {
        if (empty($this->codejercicio)) {
            $this->setDate($this->fecha);
        }

        if (false === $this->getExercise()->isOpened()) {
            $this->toolBox()->i18nLog()->warning('closed-exercise', ['%exerciseName%' => $this->getExercise()->nombre]);
            return false;
        }

        $reg = new DinRegularizacionImpuesto();
        if ($reg->loadFechaInside($this->fecha) && $reg->bloquear) {
            $this->toolBox()->i18nLog()->warning('accounting-within-regularization');
            return false;
        }

        if (false === $this->isEditable()) {
            $this->toolBox()->i18nLog()->warning('non-editable-accounting-entry');
            return false;
        }

        return parent::save();
    }

    /**
     *
     * @param string $date
     *
     * @return bool
     */
    public function setDate($date)
    {
        $exercise = new DinEjercicio();
        $exercise->idempresa = $this->idempresa;
        if ($exercise->loadFromDate($date)) {
            $this->codejercicio = $exercise->codejercicio;
            $this->fecha = $date;
            return true;
        }

        return false;
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'asientos';
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test(): bool
    {
        $utils = $this->toolBox()->utils();
        $this->concepto = $utils->noHtml($this->concepto);
        $this->documento = $utils->noHtml($this->documento);

        if (\strlen($this->concepto) == 0 || \strlen($this->concepto) > 255) {
            $this->toolBox()->i18nLog()->warning('invalid-column-lenght', ['%column%' => 'concepto', '%min%' => '1', '%max%' => '255']);
            return false;
        }

        return parent::test();
    }

    /**
     * Check if the accounting entry is editable.
     *
     * @return bool
     */
    protected function isEditable(): bool
    {
        return $this->editable || $this->previousData['editable'];
    }

    /**
     * 
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        switch ($field) {
            case 'codejercicio':
                $this->toolBox()->i18nLog()->warning('cant-change-accounting-entry-exercise');
                return false;

            case 'fecha':
                $this->setDate($this->fecha);
                if ($this->codejercicio != $this->previousData['codejercicio']) {
                    $this->toolBox()->i18nLog()->warning('cant-change-accounting-entry-exercise');
                    return false;
                }
                return true;
        }

        return parent::onChange($field);
    }

    /**
     * Update accounting entry numbers.
     *
     * @param array $entries
     * @param int   $number
     *
     * @return bool
     */
    protected function renumberAccEntries(&$entries, &$number)
    {
        $sql = '';
        foreach ($entries as $row) {
            if (self::$dataBase->var2str($row['numero']) !== self::$dataBase->var2str($number)) {
                $sql .= 'UPDATE ' . static::tableName() . ' SET numero = ' . self::$dataBase->var2str($number)
                    . ' WHERE idasiento = ' . self::$dataBase->var2str($row['idasiento']) . ';';
            }

            ++$number;
        }

        return empty($sql) || self::$dataBase->exec($sql);
    }

    /**
     * Insert the model data in the database.
     *
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = []): bool
    {
        $this->numero = $this->newCode('numero');
        return parent::saveInsert($values);
    }

    /**
     *
     * @param array $fields
     */
    protected function setPreviousData(array $fields = [])
    {
        $more = ['codejercicio', 'editable', 'fecha'];
        parent::setPreviousData(\array_merge($more, $fields));
    }
}
