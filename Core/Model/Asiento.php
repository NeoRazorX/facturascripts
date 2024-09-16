<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Tools;
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
class Asiento extends Base\ModelOnChangeClass
{
    use Base\ModelTrait;
    use Base\ExerciseRelationTrait;

    const OPERATION_GENERAL = null;
    const OPERATION_OPENING = 'A';
    const OPERATION_CLOSING = 'C';
    const OPERATION_REGULARIZATION = 'R';
    const RENUMBER_LIMIT = 1000;

    /**
     * Accounting channel. For statistics purpose.
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

    /** @var float */
    public $debe = 0.0;

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

    /** @var float */
    public $haber = 0.0;

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
        $haber = isset($detail['haber']) ? (float)$detail['haber'] : 0.0;
        $this->importe += round($haber, FS_NF0);
    }

    public function clear()
    {
        parent::clear();
        $this->editable = true;
        $this->fecha = Tools::date();
        $this->idempresa = Tools::settings('default', 'idempresa');
        $this->importe = 0.0;
        $this->numero = '';
        $this->operacion = self::OPERATION_GENERAL;
    }

    public function delete(): bool
    {
        if (false === $this->editable()) {
            Tools::log()->warning('non-editable-accounting-entry');
            return false;
        }

        // force delete lines to update subaccounts
        foreach ($this->getLines() as $line) {
            $line->delete();
        }

        if (false === parent::delete()) {
            return false;
        }

        // add audit log
        Tools::log(self::AUDIT_CHANNEL)->warning('deleted-model', [
            '%model%' => $this->modelClassName(),
            '%key%' => $this->primaryColumnValue(),
            '%desc%' => $this->primaryDescription(),
            'model-class' => $this->modelClassName(),
            'model-code' => $this->primaryColumnValue(),
            'model-data' => $this->toArray()
        ]);
        return true;
    }

    public function editable(): bool
    {
        $exercise = $this->getExercise();
        if (false === $exercise->isOpened()) {
            Tools::log()->warning('closed-exercise', ['%exerciseName%' => $exercise->nombre]);
            return false;
        }

        $reg = new DinRegularizacionImpuesto();
        $reg->idempresa = $this->idempresa;
        if ($reg->loadFechaInside($this->fecha) && $reg->bloquear) {
            Tools::log()->warning('accounting-within-regularization');
            return false;
        }

        return $this->editable || $this->previousData['editable'];
    }

    /**
     * @return DinPartida[]
     */
    public function getLines(): array
    {
        $partida = new DinPartida();
        $where = [new DataBaseWhere('idasiento', $this->idasiento)];
        return $partida->all($where, ['orden' => 'DESC', 'codsubcuenta' => 'ASC'], 0, 0);
    }

    /**
     * @return DinPartida
     */
    public function getNewLine(?Subcuenta $subcuenta = null): Partida
    {
        $partida = new DinPartida();
        $partida->concepto = $this->concepto;
        $partida->documento = $this->documento;
        $partida->idasiento = $this->primaryColumnValue();

        if ($subcuenta) {
            $partida->setAccount($subcuenta);
        }

        return $partida;
    }

    /**
     * Initializes the total fields
     */
    public function initTotals()
    {
        $this->importe = 0.0;
    }

    public function install(): string
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

        return Utils::floatcmp($debe, $haber, FS_NF0, true);
    }

    /**
     * Returns the following code for the reported field or the primary key of the model.
     *
     * @param string $field
     * @param array $where
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

    public static function primaryColumn(): string
    {
        return 'idasiento';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'numero';
    }

    /**
     * Renumber the accounting entries of the given exercise.
     *
     * @param string $codejercicio
     *
     * @return bool
     */
    public function renumber(string $codejercicio): bool
    {
        $exercise = new DinEjercicio();
        if (false === $exercise->loadFromCode($codejercicio)) {
            Tools::log()->error('exercise-not-found', ['%code%' => $codejercicio]);
            return false;
        } elseif (false === $exercise->isOpened()) {
            Tools::log()->warning('closed-exercise', ['%exerciseName%' => $exercise->nombre]);
            return false;
        }

        $offset = 0;
        $number = 1;
        $sql = 'SELECT idasiento,numero,fecha FROM ' . static::tableName()
            . ' WHERE codejercicio = ' . self::$dataBase->var2str($exercise->codejercicio)
            . " ORDER BY fecha ASC, CASE WHEN operacion = 'A' THEN 1 ELSE 2 END ASC, idasiento ASC";

        $rows = self::$dataBase->selectLimit($sql, self::RENUMBER_LIMIT, $offset);
        while (!empty($rows)) {
            if (false === $this->renumberAccEntries($rows, $number)) {
                Tools::log()->warning('renumber-accounting-error', ['%exerciseCode%' => $codejercicio]);
                return false;
            }

            $offset += self::RENUMBER_LIMIT;
            $rows = self::$dataBase->selectLimit($sql, self::RENUMBER_LIMIT, $offset);
        }
        return true;
    }

    public function save(): bool
    {
        if (empty($this->codejercicio)) {
            $this->setDate($this->fecha);
        }

        if (false === $this->editable()) {
            Tools::log()->warning('non-editable-accounting-entry');
            return false;
        }

        if (false === parent::save()) {
            return false;
        }

        // add audit log
        Tools::log(self::AUDIT_CHANNEL)->info('updated-model', [
            '%model%' => $this->modelClassName(),
            '%key%' => $this->primaryColumnValue(),
            '%desc%' => $this->primaryDescription(),
            'model-class' => $this->modelClassName(),
            'model-code' => $this->primaryColumnValue(),
            'model-data' => $this->toArray()
        ]);
        return true;
    }

    public function setDate(string $date): bool
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

    public static function tableName(): string
    {
        return 'asientos';
    }

    public function test(): bool
    {
        $this->concepto = Tools::noHtml($this->concepto);
        $this->documento = Tools::noHtml($this->documento);

        if (strlen($this->concepto) == 0 || strlen($this->concepto) > 255) {
            Tools::log()->warning('invalid-column-lenght', [
                '%column%' => 'concepto', '%min%' => '1', '%max%' => '255'
            ]);
            return false;
        }

        if (empty($this->canal)) {
            $this->canal = null;
        }

        return parent::test();
    }

    /**
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        switch ($field) {
            case 'codejercicio':
                Tools::log()->warning('cant-change-accounting-entry-exercise');
                return false;

            case 'fecha':
                $this->setDate($this->fecha);
                if ($this->codejercicio != $this->previousData['codejercicio']) {
                    Tools::log()->warning('cant-change-accounting-entry-exercise');
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
     * @param int $number
     *
     * @return bool
     */
    protected function renumberAccEntries(array &$entries, int &$number): bool
    {
        $sql = '';
        foreach ($entries as $row) {
            if (self::$dataBase->var2str($row['numero']) !== self::$dataBase->var2str($number)) {
                $sql .= 'UPDATE ' . static::tableName()
                    . ' SET numero = ' . self::$dataBase->var2str($number)
                    . ' WHERE idasiento = ' . self::$dataBase->var2str($row['idasiento']) . ';';
            }
            ++$number;
        }
        return empty($sql) || self::$dataBase->exec($sql);
    }

    protected function saveInsert(array $values = []): bool
    {
        $this->numero = $this->newCode('numero');
        return parent::saveInsert($values);
    }

    protected function setPreviousData(array $fields = [])
    {
        $more = ['codejercicio', 'editable', 'fecha'];
        parent::setPreviousData(array_merge($more, $fields));
    }
}
