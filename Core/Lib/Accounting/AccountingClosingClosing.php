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

use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\Partida;

/**
 * Perform closing of account balances for the exercise.
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class AccountingClosingClosing extends AccountingClosingBase
{

    /**
     * Delete main process.
     * Delete closing accounting entry from exercise.
     *
     * @param Ejercicio $exercise
     *
     * @return bool
     */
    public function delete($exercise): bool
    {
        return $this->deleteAccountEntry($exercise, Asiento::OPERATION_CLOSING);
    }

    /**
     * Execute main process.
     * Create a new account entry for channel with a one line by account balance.
     *
     * @param Ejercicio $exercise
     * @param int       $idjournal
     *
     * @return bool
     */
    public function exec($exercise, $idjournal): bool
    {
        return $this->delete($exercise) && parent::exec($exercise, $idjournal);
    }

    /**
     * Get the concept for the accounting entry and its lines.
     *
     * @return string
     */
    protected function getConcept(): string
    {
        return $this->toolBox()->i18n()->trans(
                'closing-closing-concept',
                ['%exercise%' => $this->exercise->nombre]
        );
    }

    /**
     * Get the date for the accounting entry.
     *
     * @return string
     */
    protected function getDate()
    {
        return $this->exercise->fechafin;
    }

    /**
     * Get the special operation identifier for the accounting entry.
     *
     * @return string
     */
    protected function getOperation(): string
    {
        return Asiento::OPERATION_CLOSING;
    }

    /**
     * Get the sub accounts filter for obtain balance.
     *
     * @return string
     */
    protected function getSubAccountsFilter(): string
    {
        return "AND t2.codsubcuenta BETWEEN '1' AND '599999999999999'";
    }

    /**
     * Add accounting entry line with balance override.
     * Return true without doing anything, if you do not need balance override.
     *
     * @param Asiento $accountEntry
     * @param float   $debit
     * @param float   $credit
     *
     * @return bool
     */
    protected function saveBalanceLine($accountEntry, $debit, $credit): bool
    {
        return true;
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
            $line->haber = $data['debit'] - $data['credit'];
            return;
        }
        $line->debe = $data['credit'] - $data['debit'];
    }
}
