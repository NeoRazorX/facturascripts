<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Perform regularization of account balances for the exercise.
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class AccountingClosingRegularization extends AccountingClosingBase
{

    /**
     * Execute main process.
     * Create a new account entry for channel with a one line by account balance.
     *
     * @param Ejercicio $exercise
     * @param int $idjournal
     *
     * @return bool
     */
    public function exec($exercise, $idjournal): bool
    {
        if (!$this->loadSubAccount($exercise, AccountingAccounts::SPECIAL_PROFIT_LOSS_ACCOUNT)) {
            $this->toolBox()->i18nLog()->error('subaccount-pyg-not-found');
            return false;
        }

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
            'closing-regularization-concept',
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
        return Asiento::OPERATION_REGULARIZATION;
    }

    /**
     * Get Balance SQL sentence
     *
     * @return string
     */
    protected function getSQL(): string
    {
        return "SELECT COALESCE(t1.canal, 0) AS channel,"
            . "t2.idsubcuenta AS id,"
            . "t2.codsubcuenta AS code,"
            . "ROUND(SUM(t2.debe), 4) AS debit,"
            . "ROUND(SUM(t2.haber), 4) AS credit"
            . " FROM asientos t1"
            . " INNER JOIN partidas t2 ON t2.idasiento = t1.idasiento AND t2.codsubcuenta BETWEEN '6' AND '799999999999999'"
            . " WHERE t1.codejercicio = '" . $this->exercise->codejercicio . "'"
            . " AND (t1.operacion IS NULL OR t1.operacion <> '" . $this->getOperation() . "')"
            . " GROUP BY 1, 2, 3"
            . " HAVING ROUND(SUM(t2.debe) - SUM(t2.haber), 4) <> 0.0000"
            . " ORDER BY 1, 3, 2";
    }

    /**
     * Add regularization lines to account entry.
     *
     * @param Asiento $accountEntry
     * @param float $debit
     * @param float $credit
     *
     * @return bool
     */
    protected function saveBalanceLine($accountEntry, $debit, $credit): bool
    {
        if ($debit > $credit) {
            return $this->addLine($accountEntry, 0.00, $debit - $credit);
        }

        return $this->addLine($accountEntry, $credit - $debit, 0.00);
    }

    /**
     * Establishes the common data of the entries of the accounting entry
     *
     * @param Partida $line
     * @param array $data
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

    /**
     *
     * @param Asiento $accountEntry
     * @param float $debit
     * @param float $credit
     *
     * @return bool
     */
    private function addLine($accountEntry, $debit, $credit): bool
    {
        $data = [
            'id' => $this->subAccount->idsubcuenta,
            'code' => $this->subAccount->codsubcuenta,
            'debit' => $debit,
            'credit' => $credit
        ];

        $line = $accountEntry->getNewLine();
        $this->setDataLine($line, $data);
        return $line->save();
    }
}
