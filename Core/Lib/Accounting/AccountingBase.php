<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\Accounting;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Dinamic\Model\Ejercicio;

/**
 * Description of AccountingBase
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author nazca                <comercial@nazcanetworks.com>
 */
abstract class AccountingBase
{

    /**
     * Link with the active dataBase
     *
     * @var DataBase
     */
    protected $dataBase;

    /**
     * Start date.
     *
     * @var string
     */
    protected $dateFrom;

    /**
     * End date.
     *
     * @var string
     */
    protected $dateTo;

    /**
     * Fiscal exercise
     *
     * @var Ejercicio
     */
    protected $exercise;

    /**
     * Generate the balance amounts between two dates.
     */
    abstract public function generate(string $dateFrom, string $dateTo, array $params = []);

    /**
     * Obtains the balances for each one of the sections of the balance sheet according to their assigned accounts.
     */
    abstract protected function getData();

    /**
     * AccountingBase constructor.
     */
    public function __construct()
    {
        $this->dataBase = new DataBase();
        $this->exercise = new Ejercicio();
    }

    /**
     * Load exercise data for the specified code
     *
     * @param string $code
     */
    public function setExercise($code)
    {
        $this->exercise->loadFromCode($code);
    }

    /**
     * Load exercise data for the company and date
     *
     * @param int    $idcompany
     * @param string $date
     */
    public function setExerciseFromDate($idcompany, $date)
    {
        $this->exercise->idempresa = $idcompany;
        $this->exercise->loadFromDate($date, false, false);
    }

    /**
     * Returns a new date.
     *
     * @param string $date
     * @param string $add
     *
     * @return string
     */
    protected function addToDate($date, $add)
    {
        return \date('d-m-Y', \strtotime($add, \strtotime($date)));
    }

    /**
     *
     * @return ToolBox
     */
    protected function toolBox()
    {
        return new ToolBox();
    }
}
