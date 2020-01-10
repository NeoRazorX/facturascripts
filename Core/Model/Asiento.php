<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * The accounting entry. It is related to an exercise and consists of games.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class Asiento extends Base\ModelClass implements Base\GridModelInterface
{

    use Base\ModelTrait;

    const OPERATION_GENERAL = null;
    const OPERATION_OPENING = 'A';
    const OPERATION_CLOSING = 'C';
    const OPERATION_REGULARIZATION = 'R';

    /**
     *
     * @var int
     */
    public $canal;

    /**
     * Exercise code of the accounting entry.
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Accounting entry concept.
     *
     * @var string
     */
    public $concepto;

    /**
     *
     * @var bool
     */
    private $deleteTest = true;

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
        $this->idempresa = $this->toolBox()->appSettings()->get('default', 'idempresa');
        $this->fecha = date(self::DATE_STYLE);
        $this->editable = true;
        $this->importe = 0.0;
        $this->numero = '';
    }

    /**
     * Remove the accounting entry.
     *
     * @return bool
     */
    public function delete()
    {
        if ($this->deleteTest) {
            if ($this->deleteErrorDataExercise()) {
                return false;
            }

            /// TODO: Check if accounting entry have VAT Accounts
            $regularization = new RegularizacionImpuesto();
            if ($regularization->getFechaInside($this->fecha)) {
                $this->toolBox()->i18nLog()->warning('acounting-within-regularization');
                return false;
            }
        }

        /// forze delete lines to update subaccounts
        foreach ($this->getLines() as $line) {
            $line->delete();
        }

        return parent::delete();
    }

    /**
     * Change delete test status
     *
     * @param bool $value
     */
    public function setDeleteTest($value)
    {
        $this->deleteTest = $value;
    }

    /**
     *
     * @return Partida[]
     */
    public function getLines()
    {
        $partida = new Partida();
        return $partida->all([new DataBaseWhere('idasiento', $this->idasiento)]);
    }

    /**
     *
     * @return Partida
     */
    public function getNewLine()
    {
        $partida = new Partida();
        $partida->idasiento = $this->primaryColumnValue();
        $partida->concepto = $this->concepto;

        return $partida;
    }

    /**
     * Indicates whether the exercise of the accounting entry has
     * a closing entry.
     *
     * @return bool
     */
    public function hasClosing(): bool
    {
        $entry = new self();
        $where = [
            new DataBaseWhere('codejercicio', $this->codejercicio),
            new DataBaseWhere('operacion', self::OPERATION_CLOSING)
        ];
        return $entry->loadFromCode('', $where);
    }

    /**
     * Indicates whether the exercise of the accounting entry has
     * a regularization entry.
     *
     * @return bool
     */
    public function hasRegularization(): bool
    {
        $entry = new self();
        $where = [
            new DataBaseWhere('codejercicio', $this->codejercicio),
            new DataBaseWhere('operacion', self::OPERATION_REGULARIZATION)
        ];
        return $entry->loadFromCode('', $where);
    }

    /**
     * Initializes the total fields
     */
    public function initTotals()
    {
        $this->importe = 0.00;
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
        new Ejercicio();
        new Diario();

        return parent::install();
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
        if (!empty($field) && $field !== $this->primaryColumn()) {
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
     * @param string $codjercicio
     *
     * @return bool
     */
    public function renumber($codjercicio = '')
    {
        $ejercicio = new Ejercicio();
        $where = empty($codjercicio) ? [] : [new DataBaseWhere('codejercicio', $codjercicio)];

        foreach ($ejercicio->all($where) as $eje) {
            if ($eje->isOpened() === false) {
                continue;
            }

            $offset = 0;
            $number = 1;
            $sql = 'SELECT idasiento,numero,fecha FROM ' . static::tableName()
                . ' WHERE codejercicio = ' . self::$dataBase->var2str($eje->codejercicio)
                . ' ORDER BY codejercicio ASC, fecha ASC, idasiento ASC';

            $asientos = self::$dataBase->selectLimit($sql, 1000, $offset);
            while (!empty($asientos)) {
                if (!$this->renumberAccountingEntries($asientos, $number)) {
                    $this->toolBox()->i18nLog()->warning('renumber-accounting-error', ['%exerciseCode%' => $eje->codejercicio]);
                    return false;
                }
                $offset += 1000;
                $asientos = self::$dataBase->selectLimit($sql, 1000, $offset);
            }
        }

        return true;
    }

    /**
     *
     * @param string $date
     *
     * @return bool
     */
    public function setDate($date)
    {
        $ejercicio = new Ejercicio();
        $ejercicio->idempresa = $this->idempresa;

        if ($ejercicio->loadFromDate($date)) {
            $this->codejercicio = $ejercicio->codejercicio;
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

        if (strlen($this->concepto) == 0 || strlen($this->concepto) > 255) {
            $this->toolBox()->i18nLog()->warning('invalid-column-lenght', ['%column%' => 'concepto', '%min%' => '1', '%max%' => '255']);
            return false;
        }

        if (empty($this->codejercicio)) {
            $this->setDate($this->fecha);
        }

        if ($this->testErrorInData()) {
            $this->toolBox()->i18nLog()->warning('accounting-data-missing');
            return false;
        }

        return parent::test();
    }

    /**
     * Checks if accounty entry is a special entry or is in a closed fiscal year.
     * Returns TRUE on error.
     *
     * @return bool
     */
    private function deleteErrorDataExercise(): bool
    {
        $exercise = new Ejercicio();
        if (!$exercise->loadFromCode($this->codejercicio)) {
            return true;
        }

        if (!$exercise->isOpened()) {
            $this->toolBox()->i18nLog()->warning('closed-exercise', ['%exerciseName%' => $exercise->nombre]);
            return true;
        }

        if ($this->operacion === self::OPERATION_OPENING && $this->hasRegularization()) {
            $this->toolBox()->i18nLog()->warning('delete-aperture-error');
            return true;
        }

        if ($this->operacion === self::OPERATION_REGULARIZATION && $this->hasClosing()) {
            $this->toolBox()->i18nLog()->warning('delete-pyg-error');
            return true;
        }

        return false;
    }

    /**
     * Check if exists error in accounting entry
     *
     * @return bool
     */
    private function testErrorInData(): bool
    {
        return empty($this->codejercicio) || empty($this->concepto) || empty($this->fecha);
    }

    /**
     * Update accounting entry number for
     *
     * @param Asiento[] $entries
     * @param int $number
     * @return bool
     */
    protected function renumberAccountingEntries($entries, &$number)
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
}
