<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\DataSrc\Ejercicios;
use FacturaScripts\Dinamic\Lib\Accounting\AccountingAccounts;
use FacturaScripts\Dinamic\Model\Asiento as DinAsiento;
use FacturaScripts\Dinamic\Model\Ejercicio as DinEjercicio;
use FacturaScripts\Dinamic\Model\Subcuenta as DinSubcuenta;

/**
 * Tax regularization.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class RegularizacionImpuesto extends Base\ModelClass
{
    use Base\ModelTrait;
    use Base\AccEntryRelationTrait;
    use Base\ExerciseRelationTrait;

    /** @var bool */
    public $bloquear;

    /** @var string */
    public $codsubcuentaacr;

    /** @var string */
    public $codsubcuentadeu;

    /** @var string */
    public $fechaasiento;

    /** @var string */
    public $fechafin;

    /** @var string */
    public $fechainicio;

    /** @var int */
    public $idempresa;

    /** @var int */
    public $idregiva;

    /** @var int */
    public $idsubcuentaacr;

    /** @var int */
    public $idsubcuentadeu;

    /** @var string */
    public $periodo;

    public function clear()
    {
        parent::clear();
        $this->bloquear = false;
    }

    public function delete(): bool
    {
        if (false === parent::delete()) {
            return false;
        }

        // eliminamos el asiento
        $accEntry = $this->getAccountingEntry();
        if ($accEntry->exists()) {
            $accEntry->delete();
        }

        return true;
    }

    public function install(): string
    {
        // dependencias
        new DinEjercicio();
        new DinSubcuenta();
        new DinAsiento();

        return parent::install();
    }

    public function loadFechaInside(string $fecha): bool
    {
        return $this->loadFromCode('', [
            new DataBaseWhere('fechainicio', $fecha, '<='),
            new DataBaseWhere('fechafin', $fecha, '>=')
        ]);
    }

    public static function primaryColumn(): string
    {
        return 'idregiva';
    }

    public function primaryDescription(): string
    {
        return $this->codejercicio . ' - ' . $this->periodo;
    }

    public static function tableName(): string
    {
        return 'regularizacionimpuestos';
    }

    public function test(): bool
    {
        if (empty($this->codejercicio)) {
            foreach (Ejercicios::all() as $ejercicio) {
                $this->codejercicio = $ejercicio->codejercicio;
                $this->idempresa = $ejercicio->idempresa;
                break;
            }
        } elseif (empty($this->idempresa)) {
            $this->idempresa = $this->getExercise()->idempresa;
        } elseif ($this->idempresa != $this->getExercise()->idempresa) {
            // no comparar tipos, ya que idempresa, al venir del formulario puede venir como string
            $this->toolBox()->i18nLog()->warning('exercise-company-mismatch');
            return false;
        }

        if ($this->getExercise()->isOpened() === false) {
            $this->toolBox()->i18nLog()->warning('closed-exercise', ['%exerciseName%' => $this->codejercicio]);
            return false;
        }

        if (empty($this->periodo)) {
            $this->periodo = 'T1';
        }
        if (empty($this->fechainicio) || empty($this->fechafin)) {
            $this->setDates();
        }

        if (empty($this->codsubcuentaacr) || empty($this->codsubcuentadeu)) {
            $this->setDefaultAccounts();
        }

        return parent::test();
    }

    protected function setDates(): void
    {
        $year = date('Y', strtotime($this->getExercise()->fechainicio));

        // asignamos la fecha en función del periodo
        switch ($this->periodo) {
            default:
            case 'T1':
                $this->fechainicio = date('01-01-' . $year);
                $this->fechafin = date('31-03-' . $year);
                break;

            case 'T2':
                $this->fechainicio = date('01-04-' . $year);
                $this->fechafin = date('30-06-' . $year);
                break;

            case 'T3':
                $this->fechainicio = date('01-07-' . $year);
                $this->fechafin = date('30-09-' . $year);
                break;

            case 'T4':
                $this->fechainicio = date('01-10-' . $year);
                $this->fechafin = date('31-12-' . $year);
                break;

            case 'Y':
                $this->fechainicio = date('01-01-' . $year);
                $this->fechafin = date('31-12-' . $year);
                break;
        }
    }

    protected function setDefaultAccounts(): void
    {
        $accounts = new AccountingAccounts();
        $accounts->exercise = $this->getExercise();

        // buscamos la subcuenta de acreedores
        $subcuentaAcr = $accounts->getSpecialSubAccount('IVAACR');
        $this->codsubcuentaacr = $subcuentaAcr->codsubcuenta;
        $this->idsubcuentaacr = $subcuentaAcr->primaryColumnValue();

        // buscamos la subcuenta de deudores
        $subcuentaDeu = $accounts->getSpecialSubAccount('IVADEU');
        $this->codsubcuentadeu = $subcuentaDeu->codsubcuenta;
        $this->idsubcuentadeu = $subcuentaDeu->primaryColumnValue();
    }
}
