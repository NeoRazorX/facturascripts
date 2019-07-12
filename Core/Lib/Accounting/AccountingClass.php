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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Partida;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Base class for creation of accounting processes
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
abstract class AccountingClass extends AccountingAccounts
{

    /**
     *
     * @var ModelClass
     */
    protected $document;

    /**
     * Multi-language translator.
     *
     * @var Translator
     */
    protected $i18n;

    /**
     * Manage the log of all controllers, models and database.
     *
     * @var MiniLog
     */
    protected $miniLog;

    /**
     * Class constructor and initializate auxiliar model class.
     */
    public function __construct()
    {
        parent::__construct();
        $this->i18n = new Translator();
        $this->miniLog = new MiniLog();
    }

    /**
     * Method to launch the accounting process
     *
     * @param ModelClass $model
     */
    public function generate($model)
    {
        $this->document = $model;
        $this->exercise->idempresa = $model->idempresa ?? AppSettings::get('default', 'idempresa');
    }

    /**
     * Add a standard line to the accounting entry based on the reported sub-account
     *
     * @param Asiento   $accountEntry
     * @param Subcuenta $subaccount
     * @param bool      $isDebit
     * @param float     $amount
     *
     * @return bool
     */
    protected function addBasicLine($accountEntry, $subaccount, $isDebit, $amount = null): bool
    {
        $line = $this->getBasicLine($accountEntry, $subaccount, $isDebit, $amount);
        return $line->save();
    }

    /**
     * Add a line of taxes to the accounting entry based on the sub-account
     * and values reported
     *
     * @param Asiento   $accountEntry
     * @param Subcuenta $subaccount
     * @param bool      $isDebit
     * @param array     $values
     *
     * @return bool
     */
    protected function addTaxLine($accountEntry, $subaccount, $isDebit, $values): bool
    {
        /// add basic data
        $amount = (float) $values['totaliva'] + (float) $values['totalrecargo'];
        $line = $this->getBasicLine($accountEntry, $subaccount, $isDebit, $amount);

        /// add tax register data
        $line->baseimponible = (float) $values['neto'];
        $line->iva = (float) $values['iva'];
        $line->recargo = (float) $values['recargo'];
        $line->cifnif = $this->document->cifnif;
        $line->codserie = $this->document->codserie;
        $line->documento = $this->document->codigo;
        $line->factura = $this->document->numero;

        /// save new line
        return $line->save();
    }

    /**
     * Obtain a standard line to the accounting entry based on the reported sub-account
     *
     * @param Asiento   $accountEntry
     * @param Subcuenta $subaccount
     * @param bool      $isDebit
     * @param float     $amount
     *
     * @return Partida
     */
    protected function getBasicLine($accountEntry, $subaccount, $isDebit, $amount = null)
    {
        $line = $accountEntry->getNewLine();
        $line->idsubcuenta = $subaccount->idsubcuenta;
        $line->codsubcuenta = $subaccount->codsubcuenta;

        $total = ($amount === null) ? $this->document->total : $amount;
        if ($isDebit) {
            $line->debe = $total;
        } else {
            $line->haber = $total;
        }
        return $line;
    }
}
