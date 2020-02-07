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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\Partida;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Perform opening of account balances for the exercise.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class AccountingClosingOpening extends AccountingClosingBase
{

    /**
     * indicates whether the accounting account plan should be copied
     * to the new fiscal year.
     *
     * @var bool
     */
    protected $copySubAccounts = true;

    /**
     *
     * @var Ejercicio
     */
    protected $newExercise;

    /**
     * Delete main process.
     * Delete opening accounting entry from exercise.
     *
     * @param Ejercicio $exercise
     *
     * @return bool
     */
    public function delete($exercise): bool
    {
        $this->exercise = $exercise;
        $this->loadNewExercise();
        return $this->deleteAccountEntry($this->newExercise, Asiento::OPERATION_OPENING);
    }

    /**
     * Execute main process.
     * Create a new account entry for channel with a one line by account balance.
     * The informed exercise must be the closed exercise.
     *
     * @param Ejercicio $exercise
     * @param int       $idjournal
     *
     * @return bool
     */
    public function exec($exercise, $idjournal): bool
    {
        if (!$this->delete($exercise)) {
            return false;
        }

        if ($this->copySubAccounts && !$this->copyAccounts()) {
            return false;
        }

        $this->loadSubAccount($this->newExercise, AccountingAccounts::SPECIAL_PROFIT_LOSS_ACCOUNT);
        return parent::exec($exercise, $idjournal);
    }

    /**
     * Establish whether the accounting account plan should be copied
     * to the new fiscal year.
     *
     * @param bool $value
     */
    public function setCopySubAccounts($value)
    {
        $this->copySubAccounts = $value;
    }

    /**
     * Copy accounts and subaccounts from exercise to new exercise
     *
     * @return bool
     */
    protected function copyAccounts(): bool
    {
        $accounting = new AccountingCreation();

        /// update exercise configuration
        $this->newExercise->longsubcuenta = $this->exercise->longsubcuenta;
        $this->newExercise->save();

        /// copy accounts
        $accountModel = new Cuenta();
        $where = [new DataBaseWhere('codejercicio', $this->exercise->codejercicio)];
        foreach ($accountModel->all($where, ['codcuenta' => 'ASC'], 0, 0) as $account) {
            $newAccount = $accounting->copyAccountToExercise($account, $this->newExercise->codejercicio);
            if (!$newAccount->exists()) {
                return false;
            }
        }

        /// copy subaccounts
        $subaccountModel = new Subcuenta();
        $subaccountModel->clearExerciseCache();
        foreach ($subaccountModel->all($where, ['codsubcuenta' => 'ASC'], 0, 0) as $subaccount) {
            $newSubaccount = $accounting->copySubAccountToExercise($subaccount, $this->newExercise->codejercicio);
            if (!$newSubaccount->exists()) {
                return false;
            }
        }

        return true;
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
     * @return string
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
     * Get the special operation filter for obtain balance.
     *
     * @return string
     */
    protected function getOperationFilter(): string
    {
        return Asiento::OPERATION_CLOSING;
    }

    /**
     * Get Balance SQL sentence
     *
     * @return string
     */
    protected function getSQL(): string
    {
        return "SELECT " . $this->getSQLFields() . ", t3.idsubcuenta AS id_new"
            . " FROM asientos t1"
            . " INNER JOIN partidas t2 ON t2.idasiento = t1.idasiento " . $this->getSubAccountsFilter()
            . " INNER JOIN subcuentas t3 ON t3.codsubcuenta = t2.codsubcuenta AND t3.codejercicio = '" . $this->newExercise->codejercicio . "'"
            . " WHERE t1.codejercicio = '" . $this->exercise->codejercicio . "'"
            . " AND (t1.operacion IS NULL OR t1.operacion <> '" . $this->getOperationFilter() . "')"
            . " GROUP BY 1, 2, 3, t3.idsubcuenta"
            . " HAVING ROUND(SUM(t2.debe) - SUM(t2.haber), 4) <> 0.0000"
            . " ORDER BY 1, 3, 2";
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
     * Search and load next exercise of indicated exercise.
     */
    private function loadNewExercise()
    {
        $date = \date('d-m-Y', \strtotime($this->exercise->fechainicio . ' +1 year'));

        $this->newExercise = new Ejercicio();
        $this->newExercise->idempresa = $this->exercise->idempresa;
        $this->newExercise->loadFromDate($date, true, true);
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
     * Establishes the common data of the entries of the accounting entry.
     * Set new exercise data values.
     *
     * @param Partida $line
     * @param array   $data
     */
    protected function setDataLine(&$line, $data)
    {
        if ($this->isProfitLossAccount($data['code'])) {
            $this->setResultAccountData($data);
        }

        parent::setDataLine($line, $data);

        $line->idsubcuenta = $data['id_new'];
        if ($data['debit'] > $data['credit']) {
            $line->debe = $data['debit'] - $data['credit'];
            return;
        }

        $line->haber = $data['credit'] - $data['debit'];
    }

    /**
     * Check if subaccount is a special profit and loss account
     *
     * @param string $subaccount
     * @return bool
     */
    private function isProfitLossAccount(string $subaccount): bool
    {
        return isset($this->subAccount) && $this->subAccount->codsubcuenta == $subaccount;
    }

    /**
     * Set the sub-account data based on the result of the previous exercise
     *
     * @param array $data
     */
    private function setResultAccountData(&$data)
    {
        $specialAccount = ($data['debit'] > $data['credit']) ? AccountingAccounts::SPECIAL_NEGATIVE_PREV_ACCOUNT : AccountingAccounts::SPECIAL_POSITIVE_PREV_ACCOUNT;

        $accounting = new AccountingAccounts();
        $accounting->exercise = $this->newExercise;

        $subAccount = $accounting->getSpecialSubAccount($specialAccount);
        if (!empty($subAccount->codsubcuenta)) {
            $data['code'] = $subAccount->codsubcuenta;
            $data['id_new'] = $subAccount->idsubcuenta;
        }
    }
}
