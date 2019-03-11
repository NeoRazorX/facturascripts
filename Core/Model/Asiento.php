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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Utils;

/**
 * The accounting entry. It is related to an exercise and consists of games.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class Asiento extends Base\ModelClass implements Base\GridModelInterface
{

    use Base\ModelTrait;

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
        $this->idempresa = AppSettings::get('default', 'idempresa');
        $this->fecha = date('d-m-Y');
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
        if ($this->deleteErrorDataExercise()) {
            return false;
        }

        /// TODO: Check if accounting entry have VAT Accounts
        $regularization = new RegularizacionImpuesto();
        if ($regularization->getFechaInside($this->fecha)) {
            self::$miniLog->alert(self::$i18n->trans('acounting-within-regularization', ['%tax%' => FS_IVA]));
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
     * @return Partida[]
     */
    public function getLines()
    {
        $partida = new Partida();
        return $partida->all([new DataBaseWhere('idasiento', $this->idasiento)]);
    }

    /**
     *
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
     * Re-number the accounting entries of the open exercises
     *
     * @return bool
     */
    public function renumber()
    {
        $ejercicio = new Ejercicio();
        foreach ($ejercicio->all([new DataBaseWhere('estado', 'ABIERTO')]) as $eje) {
            $posicion = 0;
            $numero = 1;
            $consulta = 'SELECT idasiento,numero,fecha FROM ' . static::tableName()
                . ' WHERE codejercicio = ' . self::$dataBase->var2str($eje->codejercicio)
                . ' ORDER BY codejercicio ASC, fecha ASC, idasiento ASC';

            $asientos = self::$dataBase->selectLimit($consulta, 1000, $posicion);
            while (!empty($asientos)) {
                $sql = '';
                foreach ($asientos as $col) {
                    if ($col['numero'] !== $numero) {
                        $sql .= 'UPDATE ' . static::tableName() . ' SET numero = ' . self::$dataBase->var2str($numero)
                            . ' WHERE idasiento = ' . self::$dataBase->var2str($col['idasiento']) . ';';
                    }

                    ++$numero;
                }

                if (!empty($sql) && !self::$dataBase->exec($sql)) {
                    self::$miniLog->alert(self::$i18n->trans('renumber-accounting-error', ['%exerciseCode%' => $eje->codejercicio]));
                    return false;
                }

                $posicion += 1000;
                $asientos = self::$dataBase->selectLimit($consulta, 1000, $posicion);
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
        $this->concepto = Utils::noHtml($this->concepto);
        $this->documento = Utils::noHtml($this->documento);

        if (strlen($this->concepto) == 0 || strlen($this->concepto) > 255) {
            self::$miniLog->alert(self::$i18n->trans('invalid-column-lenght', ['%column%' => 'concepto', '%min%' => '1', '%max%' => '255']));
            return false;
        }

        if (empty($this->codejercicio)) {
            $this->setDate($this->fecha);
        }

        if ($this->testErrorInData()) {
            self::$miniLog->alert(self::$i18n->trans('accounting-data-missing'));
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
            self::$miniLog->warning(self::$i18n->trans('closed-exercise', ['%exerciseName%' => $exercise->nombre]));
            return true;
        }

        if ($this->idasiento === $exercise->idasientoapertura && !empty($exercise->idasientopyg)) {
            self::$miniLog->warning(self::$i18n->trans('delete-aperture-error'));
            return true;
        }

        if ($this->idasiento === $exercise->idasientopyg && !empty($exercise->idasientocierre)) {
            self::$miniLog->warning(self::$i18n->trans('delete-pyg-error'));
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
