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
use FacturaScripts\Dinamic\Model\Ejercicio;

/**
 * Class that performs accounting closures
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class ClosingToAcounting
{

    /**
     * Indicates whether the accounting account plan should be copied
     * to the new fiscal year.
     *
     * @var bool
     */
    protected $copySubAccounts;

    /**
     * It provides direct access to the database.
     *
     * @var DataBase
     */
    protected static $dataBase;

    /**
     * Exercise where the accounting process is performed.
     *
     * @var Ejercicio
     */
    protected $exercise;

    /**
     * Journal Id for closing accounting entry.
     *
     * @var int
     */
    protected $journalClosing;

    /**
     * Journal Id for opening accounting entry.
     *
     * @var int
     */
    protected $journalOpening;

    /**
     * Class Constructor
     */
    public function __construct()
    {
        if (self::$dataBase === null) {
            self::$dataBase = new DataBase();
        }
    }

    /**
     * Execute the delete process, deleting selected entry accounts
     * and reopening exercise.
     *
     * @param Ejercicio $exercise
     * @param array     $data
     *
     * @return bool
     */
    public function delete($exercise, $data): bool
    {
        $this->exercise = $exercise;
        $closing = $data['deleteClosing'] ?? true;
        $opening = $data['deleteOpening'] ?? true;

        try {
            self::$dataBase->beginTransaction();

            if ($opening && !$this->deleteOpening()) {
                return false;
            }

            if ($closing) {
                if (!$this->deleteClosing() || !$this->deleteRegularization()) {
                    return false;
                }
                $exercise->estado = Ejercicio::EXERCISE_STATUS_OPEN;
                $exercise->save();
            }

            self::$dataBase->commit();
        } finally {
            $result = !self::$dataBase->inTransaction();
            if ($result == false) {
                self::$dataBase->rollback();
            }
        }

        return $result;
    }

    /**
     * Execute the main process of regularization, closing and opening
     * of accounts.
     *
     * @param Ejercicio $exercise
     * @param array     $data
     *
     * @return bool
     */
    public function exec($exercise, $data): bool
    {
        $this->exercise = $exercise;
        $this->journalClosing = $data['journalClosing'] ?? 0;
        $this->journalOpening = $data['journalOpening'] ?? 0;
        $this->copySubAccounts = $data['copySubAccounts'] ?? true;

        self::$dataBase->beginTransaction();

        try {
            if ($this->execRegularization() && $this->execClosing() && $this->execOpening()) {
                $exercise->estado = Ejercicio::EXERCISE_STATUS_CLOSED;
                $exercise->save();
                self::$dataBase->commit();
            }
        } finally {
            $result = !self::$dataBase->inTransaction();
            if ($result == false) {
                self::$dataBase->rollback();
            }
        }

        return $result;
    }

    /**
     * Delete closing accounting entry
     *
     * @return bool
     */
    protected function deleteClosing(): bool
    {
        $closing = new AccountingClosingClosing();
        return $closing->delete($this->exercise);
    }

    /**
     * Delete opening accounting entry
     *
     * @return bool
     */
    protected function deleteOpening(): bool
    {
        $opening = new AccountingClosingOpening();
        return $opening->delete($this->exercise);
    }

    /**
     * Delete regularization accounting entry
     *
     * @return bool
     */
    protected function deleteRegularization(): bool
    {
        $regularization = new AccountingClosingRegularization();
        return $regularization->delete($this->exercise);
    }

    /**
     * Execute account closing
     *
     * @return bool
     */
    protected function execClosing(): bool
    {
        $closing = new AccountingClosingClosing();
        return $closing->exec($this->exercise, $this->journalClosing);
    }

    /**
     * Execute account opening
     *
     * @return bool
     */
    protected function execOpening(): bool
    {
        $opening = new AccountingClosingOpening();
        $opening->setCopySubAccounts($this->copySubAccounts);
        return $opening->exec($this->exercise, $this->journalOpening);
    }

    /**
     * Execute account regularization
     *
     * @return bool
     */
    protected function execRegularization(): bool
    {
        $regularization = new AccountingClosingRegularization();
        return $regularization->exec($this->exercise, $this->journalClosing);
    }
}
