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
use FacturaScripts\Dinamic\Lib\Accounting\AccountingAccounts;
use FacturaScripts\Dinamic\Model\Asiento as DinAsiento;
use FacturaScripts\Dinamic\Model\Ejercicio as DinEjercicio;
use FacturaScripts\Dinamic\Model\Partida as DinPartida;
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

    /**
     * @var bool
     */
    public $bloquear;

    /**
     * Code, not ID, of the related subaccount.
     *
     * @var string
     */
    public $codsubcuentaacr;

    /**
     * Code, not ID, of the related subaccount.
     *
     * @var string
     */
    public $codsubcuentadeu;

    /**
     * Date of entry.
     *
     * @var string
     */
    public $fechaasiento;

    /**
     * End date.
     *
     * @var string
     */
    public $fechafin;

    /**
     * Start date.
     *
     * @var string
     */
    public $fechainicio;

    /**
     * Foreign Key with Empresas table.
     *
     * @var int
     */
    public $idempresa;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idregiva;

    /**
     * Related subaccount ID.
     *
     * @var int
     */
    public $idsubcuentaacr;

    /**
     * Related subaccount ID.
     *
     * @var int
     */
    public $idsubcuentadeu;

    /**
     * Period of regularization.
     *
     * @var string
     */
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

        $accEntry = $this->getAccountingEntry();
        if ($accEntry->exists()) {
            $accEntry->delete();
        }

        return true;
    }

    /**
     * Returns the items per accounting entry.
     *
     * @return DinPartida[]
     */
    public function getPartidas(): array
    {
        return $this->getAccountingEntry()->getLines();
    }

    /**
     * Calculate Period data
     *
     * @param string $period
     *
     * @return array
     */
    public function getPeriod(string $period): array
    {
        // Calculate year
        $year = date('Y', strtotime($this->getExercise()->fechainicio));

        // return periods values
        switch ($period) {
            case 'T2':
                return ['start' => date('01-04-' . $year), 'end' => date('30-06-' . $year)];

            case 'T3':
                return ['start' => date('01-07-' . $year), 'end' => date('30-09-' . $year)];

            case 'T4':
                return ['start' => date('01-10-' . $year), 'end' => date('31-12-' . $year)];

            default:
                return ['start' => date('01-01-' . $year), 'end' => date('31-03-' . $year)];
        }
    }

    public function install(): string
    {
        // needed dependencies
        new DinEjercicio();
        new DinSubcuenta();
        new DinAsiento();

        return parent::install();
    }

    public function loadFechaInside(string $fecha): bool
    {
        $where = [
            new DataBaseWhere('fechainicio', $fecha, '<='),
            new DataBaseWhere('fechafin', $fecha, '>=')
        ];
        return $this->loadFromCode('', $where);
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
        if ($this->periodo) {
            // calculate dates to selected period
            $period = $this->getPeriod($this->periodo);
            $this->fechainicio = $period['start'];
            $this->fechafin = $period['end'];
        }

        if (empty($this->idempresa)) {
            $this->idempresa = $this->getExercise()->idempresa;
        }

        if (empty($this->codsubcuentaacr) || empty($this->codsubcuentadeu)) {
            $this->setDefaultAccounts();
        }

        return parent::test();
    }

    protected function setDefaultAccounts()
    {
        $accounts = new AccountingAccounts();
        $accounts->exercise = $this->getExercise();

        $subcuentaacr = $accounts->getSpecialSubAccount('IVAACR');
        $this->codsubcuentaacr = $subcuentaacr->codsubcuenta;
        $this->idsubcuentaacr = $subcuentaacr->primaryColumnValue();

        $subcuentadeu = $accounts->getSpecialSubAccount('IVADEU');
        $this->codsubcuentadeu = $subcuentadeu->codsubcuenta;
        $this->idsubcuentadeu = $subcuentadeu->primaryColumnValue();
    }
}
