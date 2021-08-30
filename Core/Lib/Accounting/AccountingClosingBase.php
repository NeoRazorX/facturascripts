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

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\Partida;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Description of AccountingClossing
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
abstract class AccountingClosingBase
{

    /**
     * It provides direct access to the database.
     *
     * @var DataBase
     */
    protected static $dataBase;

    /**
     * @var Ejercicio
     */
    protected $exercise;

    /**
     * Subaccount for special process
     *
     * @var Subcuenta
     */
    protected $subAccount;

    /**
     * Get the concept for the accounting entry and its lines.
     */
    abstract protected function getConcept(): string;

    /**
     * Get the date for the accounting entry.
     */
    abstract protected function getDate();

    /**
     * Get the special operation identifier for the accounting entry.
     */
    abstract protected function getOperation(): string;

    /**
     * Get the sql with balance data for operation.
     */
    abstract protected function getSQL(): string;

    /**
     * Class Constructor
     */
    public function __construct()
    {
        if (!isset(self::$dataBase)) {
            self::$dataBase = new DataBase();
        }
    }

    /**
     * Delete special accounting entry from exercise.
     *
     * @param Ejercicio $exercise
     *
     * @return bool
     */
    public function delete($exercise): bool
    {
        return $this->deleteAccountEntry($exercise, $this->getOperation());
    }

    /**
     * Execute main process.
     * Create a new account entry for channel with a one line by account balance.
     * For each channel:
     *      - Create an account
     *      - Create the lines with balance
     *      - Create a line to canceling the balance (if necessary)
     *      - Update account totals
     *
     * @param Ejercicio $exercise
     * @param int $idjournal
     *
     * @return bool
     */
    public function exec($exercise, $idjournal)
    {
        $this->exercise = $exercise;
        $accountEntry = null;
        foreach ($this->getBalance() as $channel => $balance) {
            if (!$this->newAccountEntry($accountEntry, $channel, $idjournal)) {
                return false;
            }

            $debit = 0.00;
            $credit = 0.00;
            if (!$this->saveLines($accountEntry, $balance, $debit, $credit)) {
                return false;
            }

            if (!$this->saveBalanceLine($accountEntry, $debit, $credit)) {
                return false;
            }

            $accountEntry->importe = ($debit > $credit) ? $debit : $credit;
            $accountEntry->save();
        }

        return true;
    }

    /**
     * Search and load data account from a special account code
     *
     * @param Ejercicio $exercise
     * @param string $specialAccount
     *
     * @return bool
     */
    protected function loadSubAccount($exercise, string $specialAccount): bool
    {
        $accounting = new AccountingAccounts();
        $accounting->exercise = $exercise;
        $this->subAccount = $accounting->getSpecialSubAccount($specialAccount);
        if (empty($this->subAccount->idsubcuenta)) {
            return false;
        }

        return true;
    }

    /**
     * Establishes the common data of the accounting entry
     *
     * @param Asiento $entry
     */
    protected function setData(&$entry)
    {
        $entry->codejercicio = $this->exercise->codejercicio;
        $entry->idempresa = $this->exercise->idempresa;
        $entry->importe = 0.00;

        $entry->concepto = $this->getConcept();
        $entry->fecha = $this->getDate();
        $entry->operacion = $this->getOperation();
    }

    /**
     * Establishes the common data of the entries of the accounting entry
     *
     * @param Partida $line
     * @param array $data
     */
    protected function setDataLine(&$line, $data)
    {
        $line->idsubcuenta = $data['id'];
        $line->codsubcuenta = $data['code'];
        $line->concepto = $this->getConcept();
        $line->debe = 0.00;
        $line->haber = 0.00;
    }

    /**
     * Add accounting entry line with balance override.
     * Return true without doing anything, if you do not need balance override.
     *
     * @param Asiento $accountEntry
     * @param float $debit
     * @param float $credit
     *
     * @return bool
     */
    protected function saveBalanceLine($accountEntry, $debit, $credit): bool
    {
        return true;
    }

    /**
     * Create each of the lines of the accounting entry
     * with the accounts that have a balance.
     *
     * @param Asiento $accountEntry
     * @param array $balance
     * @param float $debit
     * @param float $credit
     *
     * @return bool
     */
    protected function saveLines($accountEntry, $balance, &$debit, &$credit): bool
    {
        foreach ($balance as $row) {
            $line = $accountEntry->getNewLine();
            $this->setDataLine($line, $row);
            if (!$line->save()) {
                return false;
            }

            $debit += $row['debit'];
            $credit += $row['credit'];
        }
        return true;
    }

    /**
     * @return ToolBox
     */
    protected function toolBox(): ToolBox
    {
        return new ToolBox();
    }

    /**
     * Delete accounting entry of type indicated.
     *
     * @param Ejercicio $exercise
     * @param string $type
     */
    private function deleteAccountEntry($exercise, $type): bool
    {
        $where = [
            new DataBaseWhere('codejercicio', $exercise->codejercicio),
            new DataBaseWhere('operacion', $type),
        ];

        $accountEntry = new Asiento();
        $accountEntry->clearExerciseCache();
        foreach ($accountEntry->all($where) as $row) {
            $row->editable = true;
            if (!$row->delete()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns an array with the balances of the accounting
     * sub-accounts for each channel.
     *
     * Structure:
     *  [channel]
     *      ['id' => value, 'code' => value, 'debit' => amount, 'credit' => amount]
     *      ['id' => value, 'code' => value, 'debit' => amount, 'credit' => amount]
     *
     * @return array
     */
    private function getBalance()
    {
        $result = [];
        foreach (self::$dataBase->selectLimit($this->getSQL(), 0) as $data) {
            $channel = $data['channel'];
            unset($data['channel']);
            $result[$channel][] = $data;
        }

        return $result;
    }

    /**
     * Create new accounting entry for channel and journal.
     * New accounting it's set into accountEntry param.
     *
     * @param Asiento $accountEntry
     * @param int $channel
     * @param int $idjournal
     *
     * @return bool
     */
    private function newAccountEntry(&$accountEntry, $channel, $idjournal): bool
    {
        $accountEntry = new Asiento();
        $this->setData($accountEntry);
        $accountEntry->canal = empty($channel) ? null : $channel;
        $accountEntry->iddiario = empty($idjournal) ? null : $idjournal;
        return $accountEntry->save();
    }
}
