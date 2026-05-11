<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\Base\ExerciseRelationTrait;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Diario as DinDiario;
use FacturaScripts\Dinamic\Model\Ejercicio as DinEjercicio;
use FacturaScripts\Dinamic\Model\Partida as DinPartida;
use FacturaScripts\Dinamic\Model\RegularizacionImpuesto as DinRegularizacionImpuesto;

/**
 * La clase del asiento contable. Está relacionada con un ejercicio y consta de partidas.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class Asiento extends ModelClass
{
    use ModelTrait;
    use ExerciseRelationTrait;

    const OPERATION_GENERAL = null;
    const OPERATION_OPENING = 'A';
    const OPERATION_CLOSING = 'C';
    const OPERATION_REGULARIZATION = 'R';
    const RENUMBER_LIMIT = 1000;

    /**
     * Canal contable. Para fines estadísticos.
     *
     * @var int
     */
    public $canal;

    /**
     * Concepto del asiento contable.
     *
     * @var string
     */
    public $concepto;

    /** @var float */
    public $debe = 0.0;

    /**
     * Documento asociado con el asiento contable.
     *
     * @var string
     */
    public $documento;

    /**
     * True si es editable, false en caso contrario.
     *
     * @var bool
     */
    public $editable;

    /**
     * Fecha del asiento contable.
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
     * Identificador del diario contable.
     *
     * @var int
     */
    public $iddiario;

    /**
     * Foreign Key de la tabla Empresas.
     *
     * @var int
     */
    public $idempresa;

    /**
     * Importe del asiento contable.
     *
     * @var float|int
     */
    public $importe;

    /**
     * Número de asiento contable. Se modificará al renumerar.
     *
     * @var string
     */
    public $numero;

    /**
     * Establece si el asiento contable es de una operación especial:
     * - apertura:          opening
     * - regularización:    regularization
     * - cierre:            closing
     *
     * @var string
     */
    public $operacion;

    /**
     * Acumula los importes del detalle en el documento
     *
     * @param array $detail
     */
    public function accumulateAmounts(array &$detail)
    {
        $nf0 = Tools::settings('default', 'decimals', 2);
        $haber = isset($detail['haber']) ? (float)$detail['haber'] : 0.0;
        $this->importe += round($haber, $nf0);
    }

    public function clear(): void
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
        Tools::log(LogMessage::AUDIT_CHANNEL)->warning('deleted-model', [
            '%model%' => $this->modelClassName(),
            '%key%' => $this->id(),
            '%desc%' => $this->primaryDescription(),
            'model-class' => $this->modelClassName(),
            'model-code' => $this->id(),
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

        return $this->editable || $this->getOriginal('editable');
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
        $partida->idasiento = $this->id();

        if ($subcuenta) {
            $partida->setAccount($subcuenta);
        }

        return $partida;
    }

    /**
     * Inicializa los campos totales
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
     * Comprueba que el asiento está balanceado.
     *
     * @return bool Devuelve TRUE si el asiento está balanceado.
     */
    public function isBalanced(): bool
    {
        $debe = 0.0;
        $haber = 0.0;
        foreach ($this->getLines() as $line) {
            $debe += $line->debe;
            $haber += $line->haber;
        }

        $nf0 = Tools::settings('default', 'decimals', 2);
        return Tools::floatCmp($debe, $haber, $nf0, true);
    }

    /**
     * Devuelve el siguiente código para el campo reportado o la clave primaria del modelo.
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
     * Renumera los asientos contables del ejercicio indicado.
     *
     * @param string $codejercicio
     *
     * @return bool
     */
    public function renumber(string $codejercicio): bool
    {
        $exercise = new DinEjercicio();
        if (false === $exercise->load($codejercicio)) {
            Tools::log()->error('exercise-not-found', ['%code%' => $codejercicio]);
            return false;
        } elseif (false === $exercise->isOpened()) {
            Tools::log()->warning('closed-exercise', ['%exerciseName%' => $exercise->nombre]);
            return false;
        }

        $offset = 0;
        $number = 1;
        $sql = 'SELECT idasiento,numero,fecha FROM ' . static::tableName()
            . ' WHERE codejercicio = ' . self::db()->var2str($exercise->codejercicio)
            . " ORDER BY fecha ASC, CASE WHEN operacion = 'A' THEN 1 ELSE 2 END ASC, idasiento ASC";

        $rows = self::db()->selectLimit($sql, self::RENUMBER_LIMIT, $offset);
        while (!empty($rows)) {
            if (false === $this->renumberAccEntries($rows, $number)) {
                Tools::log()->warning('renumber-accounting-error', ['%exerciseCode%' => $codejercicio]);
                return false;
            }

            $offset += self::RENUMBER_LIMIT;
            $rows = self::db()->selectLimit($sql, self::RENUMBER_LIMIT, $offset);
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
        Tools::log(LogMessage::AUDIT_CHANNEL)->info('updated-model', [
            '%model%' => $this->modelClassName(),
            '%key%' => $this->id(),
            '%desc%' => $this->primaryDescription(),
            'model-class' => $this->modelClassName(),
            'model-code' => $this->id(),
            'model-data' => $this->toArray()
        ]);

        return true;
    }

    public function setDate(string $date): bool
    {
        $exercise = new DinEjercicio();
        $exercise->idempresa = $this->idempresa;
        if (false === $exercise->loadFromDate($date)) {
            return false;
        }

        $this->codejercicio = $exercise->codejercicio;
        $this->fecha = $date;

        return true;
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

    protected function onChange(string $field): bool
    {
        switch ($field) {
            case 'codejercicio':
                Tools::log()->warning('cant-change-accounting-entry-exercise');
                return false;

            case 'fecha':
                $this->setDate($this->fecha);
                if ($this->codejercicio != $this->getOriginal('codejercicio')) {
                    Tools::log()->warning('cant-change-accounting-entry-exercise');
                    return false;
                }
                return true;
        }

        return parent::onChange($field);
    }

    /**
     * Actualiza los números de los asientos contables.
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
            if (self::db()->var2str($row['numero']) !== self::db()->var2str($number)) {
                $sql .= 'UPDATE ' . static::tableName()
                    . ' SET numero = ' . self::db()->var2str($number)
                    . ' WHERE idasiento = ' . self::db()->var2str($row['idasiento']) . ';';
            }
            ++$number;
        }
        return empty($sql) || self::db()->exec($sql);
    }

    protected function saveInsert(): bool
    {
        $this->numero = $this->newCode('numero');

        return parent::saveInsert();
    }
}
