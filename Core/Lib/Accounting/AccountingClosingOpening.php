<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Ejercicio;

/**
 * Perform opening of account balances for the exercise.
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class AccountingClosingOpening extends AccountingClosingBase
{

    /**
     *
     * @var Ejercicio
     */
    protected $newExercise;

    /**
     * Execute main process.
     * Create a new account entry for channel with a one line by account balance.
     * The informed exercise must be the closed exercise.
     *
     * @param Ejercicio $exercise
     * @return boolean
     */
    public function exec($exercise): boolean
    {
        $this->loadNewExercise($exercise);
        return parent::exec($exercise);
    }

    /**
     * Get the concept for the accounting entry and its lines.
     *
     * @return string
     */
    protected function getConcept(): string
    {
        return $this->toolBox()->i18n()->trans(
            'closing-opening-concept',
            ['%exercise%' => $this->newExercise->nombre]
        );
    }

    /**
     * Get the date for the accounting entry.
     *
     * @return date
     */
    protected function getDate()
    {
        return $this->newExercise->fechainicio;
    }

    /**
     * Get the special operation identifier for the accounting entry.
     *
     * @return string
     */
    protected function getOperation(): string
    {
        return Asiento::OPERATION_OPENING;
    }

    /**
     * Get the sub accounts filter for obtain balance.
     *
     * @return string
     */
    protected function getSubAccountsFilter(): string
    {
        return "AND t2.codsubcuenta BETWEEN ‘1' AND ‘599999999999999'";
    }

    /**
     * Add accounting entry line with balance override.
     * Return true without doing anything, if you do not need balance override.
     *
     * @param Asiento $accountEntry
     * @param float   $debit
     * @param float   $credit
     * @return bool
     */
    protected function saveBalanceLine($accountEntry, $debit, $credit): bool
    {
        return true;
    }

    /**
     * Establishes the common data of the accounting entry
     *
     * @param Asiento $accountEntry
     */
    protected function setData(&$entry)
    {
        parent::setData($entry);
        $entry->codejercicio = $this->newExercise->codejercicio;
    }

    /**
     * Establishes the common data of the entries of the accounting entry
     *
     * @param Partida $line
     * @param array   $data
     */
    protected function setDataLine(&$line, $data)
    {
        parent::setDataLine($line, $data);
        if ($data['debit'] > $data['credit']) {
            $line->debe = $data['debit'] - $data['credit'];
            return;
        }
        $line->haber = $data['credit'] - $data['debit'];
    }

    /**
     *
     * @param Ejercicio $exercise
     */
    private function loadNewExercise($exercise)
    {
        $date = date('d-m-Y', strtotime($exercise->fechainicio, '+1 year'));

        $this->newExercise = new Ejercicio();
        $this->newExercise->idempresa = $exercise->idempresa;
        $this->newExercise->loadFromDate($date, true, true);
    }
}
