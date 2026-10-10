<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\Ejercicio;

/**
 * Clase base de los informes contables que se calculan entre dos fechas, como el libro
 * mayor, el balance de sumas y saldos o las cuentas anuales. Proporciona la conexión a
 * la base de datos, las fechas de inicio y fin del periodo y el ejercicio sobre el que
 * se trabaja, que puede cargarse por código con setExercise() o deducirse de una fecha
 * y una empresa con setExerciseFromDate(). Las clases hijas deben implementar generate()
 * para lanzar el cálculo y getData() para obtener los saldos de cada apartado.
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
        $this->exercise->load($code);
    }

    /**
     * Load exercise data for the company and date
     *
     * @param int $idcompany
     * @param string $date
     *
     * @return bool
     */
    public function setExerciseFromDate($idcompany, $date): bool
    {
        $this->exercise->idempresa = $idcompany;
        return $this->exercise->loadFromDate($date, false, false);
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
        return date('d-m-Y', strtotime($add, strtotime($date)));
    }
}
