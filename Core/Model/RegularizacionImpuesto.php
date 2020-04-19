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
use FacturaScripts\Dinamic\Lib\Accounting\AccountingAccounts;
use FacturaScripts\Dinamic\Model\Asiento as DinAsiento;
use FacturaScripts\Dinamic\Model\Ejercicio as DinEjercicio;
use FacturaScripts\Dinamic\Model\Partida as DinPartida;
use FacturaScripts\Dinamic\Model\Subcuenta as DinSubcuenta;

/**
 * A VAT regularization.
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
     *
     * @var bool
     */
    public $bloquear;

    /**
     * Code, not ID, of the related sub-account.
     *
     * @var string
     */
    public $codsubcuentaacr;

    /**
     * Code, not ID, of the related sub-account.
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
     * Related sub-account ID.
     *
     * @var int
     */
    public $idsubcuentaacr;

    /**
     * Related sub-account ID.
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

    /**
     * Deletes the regularization of VAT from the database.
     *
     * @return bool
     */
    public function delete()
    {
        if (parent::delete()) {
            $accEntry = $this->getAccountingEntry();
            if ($accEntry->exists()) {
                $accEntry->delete();
            }

            return true;
        }

        return false;
    }

    /**
     * Returns the items per accounting entry.
     *
     * @return DinPartida[]
     */
    public function getPartidas()
    {
        return $this->getAccountingEntry()->getLines();
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
        /// needed dependencies
        new DinEjercicio();
        new DinSubcuenta();
        new DinAsiento();

        return parent::install();
    }

    /**
     * 
     * @param string $fecha
     *
     * @return bool
     */
    public function loadFechaInside($fecha): bool
    {
        $where = [
            new DataBaseWhere('fechainicio', $fecha, '<='),
            new DataBaseWhere('fechafin', $fecha, '>=')
        ];
        return $this->loadFromCode('', $where);
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idregiva';
    }

    /**
     * Returns the description for model data.
     *
     * @return string
     */
    public function primaryDescription()
    {
        return $this->codejercicio . ' - ' . $this->periodo;
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'regularizacionimpuestos';
    }

    /**
     * Returns true if there are no errors in the values of the model properties.
     * It runs inside the save method.
     *
     * @return bool
     */
    public function test()
    {
        /// calculate dates to selected period
        $period = $this->getPeriod($this->periodo);
        $this->fechainicio = $period['start'];
        $this->fechafin = $period['end'];

        if (empty($this->idempresa)) {
            $this->idempresa = $this->getExercise()->idempresa;
        }

        if (empty($this->codsubcuentaacr) || empty($this->codsubcuentadeu)) {
            $this->setDefaultAccounts();
        }

        return parent::test();
    }

    /**
     * Calculate Period data
     *
     * @param string $period
     *
     * @return array
     */
    private function getPeriod($period): array
    {
        /// Calculate year
        $year = \date('Y', \strtotime($this->getExercise()->fechainicio));

        // return periods values
        switch ($period) {
            case 'T2':
                return ['start' => \date('01-04-' . $year), 'end' => \date('30-06-' . $year)];

            case 'T3':
                return ['start' => \date('01-07-' . $year), 'end' => \date('30-09-' . $year)];

            case 'T4':
                return ['start' => \date('01-10-' . $year), 'end' => \date('31-12-' . $year)];

            default:
                return ['start' => \date('01-01-' . $year), 'end' => \date('31-03-' . $year)];
        }
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
