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

use FacturaScripts\Dinamic\Lib\Accounting\AccountingAccounts;

/**
 * A VAT regularization.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class RegularizacionImpuesto extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Exercise code.
     *
     * @var string
     */
    public $codejercicio;

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
     * ID of the generated accounting entry.
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

    /**
     * Deletes the regularization of VAT from the database.
     *
     * @return bool
     */
    public function delete()
    {
        if (parent::delete()) {
            $asiento = $this->getAsiento();
            if ($asiento->exists()) {
                $asiento->delete();
            }

            return true;
        }

        return false;
    }

    /**
     *
     * @return Asiento
     */
    public function getAsiento()
    {
        $asiento = new Asiento();
        $asiento->loadFromCode($this->idasiento);
        return $asiento;
    }

    /**
     * 
     * @return Ejercicio
     */
    public function getEjercicio()
    {
        $ejercicio = new Ejercicio();
        $ejercicio->loadFromCode($this->codejercicio);
        return $ejercicio;
    }

    /**
     * Returns the VAT regularization corresponding to that date,
     * that is, the regularization whose start date is earlier
     * to the date provided and its end date is after the date
     * provided. So you can know if the period is still open to be able
     * check in.
     *
     * @param string $fecha
     *
     * @return bool|RegularizacionImpuesto
     */
    public function getFechaInside($fecha)
    {
        $sql = 'SELECT * FROM ' . static::tableName()
            . ' WHERE fechainicio <= ' . self::$dataBase->var2str($fecha)
            . ' AND fechafin >= ' . self::$dataBase->var2str($fecha) . ';';

        $data = self::$dataBase->select($sql);
        return empty($data) ? false : new static($data[0]);
    }

    /**
     * Returns the items per accounting entry.
     *
     * @return Partida[]
     */
    public function getPartidas()
    {
        $asiento = $this->getAsiento();
        return $asiento->getLines();
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
        new Ejercicio();
        new Subcuenta();
        new Asiento();

        return parent::install();
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
            $this->idempresa = $this->getEjercicio()->idempresa;
        }

        if (empty($this->codsubcuentaacr) || empty($this->codsubcuentadeu)) {
            $this->setDefaultAccounts();
        }

        return parent::test();
    }

    /**
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'ListImpuesto?activetab=List')
    {
        return parent::url($type, $list);
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
        /// Calculate actual year
        $year = explode('-', date(self::DATE_STYLE))[2];

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

    protected function setDefaultAccounts()
    {
        $accounts = new AccountingAccounts();
        $accounts->exercise = $this->getEjercicio();

        $subcuentaacr = $accounts->getSpecialSubAccount('IVAACR');
        $this->codsubcuentaacr = $subcuentaacr->codsubcuenta;
        $this->idsubcuentaacr = $subcuentaacr->primaryColumnValue();

        $subcuentadeu = $accounts->getSpecialSubAccount('IVADEU');
        $this->codsubcuentadeu = $subcuentadeu->codsubcuenta;
        $this->idsubcuentadeu = $subcuentadeu->primaryColumnValue();
    }
}
