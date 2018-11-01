<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\Utils;

/**
 * The accounting entry. It is related to an exercise and consists of games.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
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
        $this->importe += round(floatval($detail['haber']), (int) FS_NF0);
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
     * Execute a task with cron
     */
    public function cronJob()
    {
        /**
         * We block closed exercise accounting entry or within regularizations.
         */
        $eje0 = new Ejercicio();
        $regiva0 = new RegularizacionImpuesto();
        foreach ($eje0->all() as $ej) {
            if ($ej instanceof Ejercicio && $ej->abierto()) {
                foreach ($regiva0->all([new DataBase\DataBaseWhere('codejercicio', $ej->codejercicio)]) as $reg) {
                    $sql = 'UPDATE ' . static::tableName() . ' SET editable = false WHERE editable = true'
                        . ' AND codejercicio = ' . self::$dataBase->var2str($ej->codejercicio)
                        . ' AND fecha >= ' . self::$dataBase->var2str($reg->fechainicio)
                        . ' AND fecha <= ' . self::$dataBase->var2str($reg->fechafin) . ';';
                    self::$dataBase->exec($sql);
                }
            } else {
                $sql = 'UPDATE ' . static::tableName() . ' SET editable = false WHERE editable = true'
                    . ' AND codejercicio = ' . self::$dataBase->var2str($ej->codejercicio) . ';';
                self::$dataBase->exec($sql);
            }
        }

        $this->renumber();
    }

    /**
     * Remove the accounting entry.
     *
     * @return bool
     */
    public function delete()
    {
        $error = $this->deleteErrorDataExercise();
        if (!empty($error)) {
            self::$miniLog->alert($error);
            return false;
        }

        /// TODO: Check if accounting entry have VAT Accounts
        $regularization = new RegularizacionImpuesto();
        if ($regularization->getFechaInside($this->fecha)) {
            self::$miniLog->alert(self::$i18n->trans('acounting-within-regularization', ['%tax%' => FS_IVA]));
            return false;
        }
        unset($regularization);

        /// We keep the list of accounting items and date for subsequent operations
        $linesModel = new Partida();
        $lines = $linesModel->all([new DataBase\DataBaseWhere('idasiento', $this->idasiento)]);
        $date = $this->fecha;

        /// Run main delete action
        $inTransaction = self::$dataBase->inTransaction();
        try {
            self::$dataBase->beginTransaction();

            /// delete accounting entry and detail entries via FK
            if (!parent::delete()) {
                return false;
            }

            /// update accounts balances
            $account = new Subcuenta();
            foreach ($lines as $row) {
                $account->idsubcuenta = $row->idsubcuenta;
                if (!$account->updateBalance($date, ($row->debe * -1), ($row->haber * -1))) {
                    return false;
                }
            }

            /// save transaction
            if ($inTransaction === false) {
                self::$dataBase->commit();
            }
        } catch (\Exception $e) {
            self::$miniLog->error($e->getMessage());
            return false;
        } finally {
            if (!$inTransaction && self::$dataBase->inTransaction()) {
                self::$dataBase->rollback();
                return false;
            }
        }

        return true;
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
        new SubcuentaSaldo();

        return '';
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
        if (!empty($field) && $field !== $this->primaryColumn()) {
            $where[] = new DataBase\DataBaseWhere('codejercicio', $this->codejercicio);
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
        $continuar = false;
        $ejercicio = new Ejercicio();
        foreach ($ejercicio->all([new DataBase\DataBaseWhere('estado', 'ABIERTO')]) as $eje) {
            $posicion = 0;
            $numero = 1;
            $sql = '';
            $continuar = true;
            $consulta = 'SELECT idasiento,numero,fecha FROM ' . static::tableName()
                . ' WHERE codejercicio = ' . self::$dataBase->var2str($eje->codejercicio)
                . ' ORDER BY codejercicio ASC, fecha ASC, idasiento ASC';

            $asientos = self::$dataBase->selectLimit($consulta, 1000, $posicion);
            while (!empty($asientos) && $continuar) {
                foreach ($asientos as $col) {
                    if ($col['numero'] !== $numero) {
                        $sql .= 'UPDATE ' . static::tableName() . ' SET numero = ' . self::$dataBase->var2str($numero)
                            . ' WHERE idasiento = ' . self::$dataBase->var2str($col['idasiento']) . ';';
                    }

                    ++$numero;
                }
                $posicion += 1000;

                if ($sql !== '') {
                    if (!self::$dataBase->exec($sql)) {
                        self::$miniLog->alert(self::$i18n->trans('renumber-accounting-error', ['%exerciseCode%' => $eje->codejercicio]));
                        $continuar = false;
                    }
                    $sql = '';
                }

                $asientos = self::$dataBase->selectLimit($consulta, 1000, $posicion);
            }
        }

        return $continuar;
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

        if ($this->testErrorInData()) {
            self::$miniLog->alert(self::$i18n->trans('accounting-data-missing'));
            return false;
        }

        if (strlen($this->concepto) > 255) {
            self::$miniLog->alert(self::$i18n->trans('concept-too-large'));
            return false;
        }

        $error = $this->testErrorInExercise();
        if (!empty($error)) {
            self::$miniLog->alert(self::$i18n->trans($error));
            return false;
        }

        return parent::test();
    }

    /**
     * Check if accounty entry is a special entry or is in a closed fiscal year
     */
    private function deleteErrorDataExercise(): string
    {
        $exercise = new Ejercicio();
        $exercise->loadFromCode($this->codejercicio);

        if (!$exercise->abierto()) {
            return self::$i18n->trans('closed-exercise', ['%exerciseName%' => $exercise->nombre]);
        }

        if (($this->idasiento === $exercise->idasientoapertura) && ($exercise->idasientopyg > 0)) {
            return self::$i18n->trans('delete-aperture-error');
        }

        if (($this->idasiento === $exercise->idasientopyg) && ($exercise->idasientocierre > 0)) {
            return self::$i18n->trans('delete-pyg-error');
        }

        return '';
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
     * TODO: Uncomplete documentation
     *
     * @return string
     */
    private function testErrorInExercise(): string
    {
        if (empty($this->codejercicio)) {
            return 'exercise-data-missing';
        }

        $exercise = new Ejercicio();
        $exercise->loadFromCode($this->codejercicio);

        if (!$exercise->abierto()) {
            return 'exercise-closed';
        }

        // All Ok, get company code
        $this->idempresa = $exercise->idempresa;
        return '';
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
