<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Lib\SubAccountTools;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\ModelView\PartidaImpuestoResumen;
use FacturaScripts\Dinamic\Model\RegularizacionImpuesto;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Class for the accounting of tax regularizations
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class VatRegularizationToAccounting extends AccountingClass
{

    /**
     * Regularization that is accounting
     *
     * @var RegularizacionImpuesto
     */
    protected $document;

    /**
     * Tax Subtotals Lines array
     *
     * @var PartidaImpuestoResumen[]
     */
    protected $subtotals;

    /**
     *
     * @var SubAccountTools
     */
    private $subAccountTools;

    /**
     * Sub-account on which the item is accounting
     *
     * @var Subcuenta
     */
    private $subaccount;

    /**
     * Sum of the items accounting on the debit
     *
     * @var double
     */
    private $debit;

    /**
     * Sum of the items accounting on the credit
     *
     * @var double
     */
    private $credit;

    /**
     * Method to launch the accounting process
     *
     * @param RegularizacionImpuesto $model
     */
    public function generate($model)
    {
        parent::generate($model);
        if (!$this->initialChecks()) {
            return;
        }

        $this->subAccountTools = new SubAccountTools();
        $this->subaccount = new Subcuenta();
        $this->subtotals = $this->getSubtotals();
        $this->debit = 0.00;
        $this->credit = 0.00;

        $this->vatAccountingEntry();
    }

    /**
     * Add the game with the result of regularization
     *
     * @param Asiento $accountEntry
     *
     * @return bool
     */
    protected function addAccountingResultLine($accountEntry): bool
    {
        if ($this->debit >= $this->credit) {
            $this->subaccount->idsubcuenta = $this->document->idsubcuentaacr;
            $this->subaccount->codsubcuenta = $this->document->codsubcuentaacr;
            return $this->addBasicLine($accountEntry, $this->subaccount, false, $this->debit - $this->credit);
        }

        $this->subaccount->idsubcuenta = $this->document->idsubcuentadeu;
        $this->subaccount->codsubcuenta = $this->document->codsubcuentadeu;
        return $this->addBasicLine($accountEntry, $this->subaccount, true, $this->credit - $this->debit);
    }

    /**
     * Add the items with the tax amounts
     *
     * @param Asiento $accountEntry
     *
     * @return bool
     */
    protected function addAccountingTaxLines($accountEntry): bool
    {
        $inputTaxGroup = $this->subAccountTools->specialAccountsForGroup(SubAccountTools::SPECIAL_GROUP_TAX_INPUT);
        $outputTaxGroup = $this->subAccountTools->specialAccountsForGroup(SubAccountTools::SPECIAL_GROUP_TAX_OUTPUT);

        foreach ($this->subtotals as $row) {
            $amount = $row->cuotaiva + $row->cuotarecargo;
            $this->subaccount->idsubcuenta = $row->idsubcuenta;
            $this->subaccount->codsubcuenta = $row->codsubcuenta;

            if (in_array($row->codcuentaesp, $outputTaxGroup)) {
                if (!$this->addBasicLine($accountEntry, $this->subaccount, true, $amount)) {
                    return false;
                }
                $this->debit += $amount;
                continue;
            }

            if (in_array($row->codcuentaesp, $inputTaxGroup)) {
                if (!$this->addBasicLine($accountEntry, $this->subaccount, false, $amount)) {
                    return false;
                }
                $this->credit += $amount;
            }
        }
        return true;
    }

    /**
     * Perform the initial checks to continue with the accounting process
     *
     * @return bool
     */
    protected function initialChecks(): bool
    {
        if (!empty($this->document->idasiento)) {
            return false;
        }

        if (!$this->exercise->loadFromCode($this->document->codejercicio) || !$this->exercise->isOpened()) {
            $this->toolBox()->i18nLog()->warning('closed-exercise', ['%exerciseName%' => $this->document->codejercicio]);
            return false;
        }

        return true;
    }

    /**
     * Obtain the cumulative list of amounts by tax
     *
     * @return PartidaImpuestoResumen[]
     */
    protected function getSubtotals()
    {
        $field = 'COALESCE(subcuentas.codcuentaesp, cuentas.codcuentaesp)';
        $where = [
            new DataBaseWhere('asientos.codejercicio', $this->document->codejercicio),
            new DataBaseWhere('asientos.fecha', $this->document->fechainicio, '>='),
            new DataBaseWhere('asientos.fecha', $this->document->fechafin, '<='),
            $this->subAccountTools->whereForSpecialAccounts($field, SubAccountTools::SPECIAL_GROUP_TAX_ALL)
        ];

        $orderby = [
            $field => 'ASC',
            'partidas.iva' => 'ASC',
            'partidas.recargo' => 'ASC'
        ];

        $totals = new PartidaImpuestoResumen();
        return $totals->all($where, $orderby);
    }

    /**
     * Assign the document data to the accounting entry
     *
     * @param Asiento $accountEntry
     * @param string  $concept
     */
    protected function setAccountingData(&$accountEntry, $concept)
    {
        $accountEntry->codejercicio = $this->document->codejercicio;
        $accountEntry->concepto = $concept;
        $accountEntry->fecha = date('d-m-Y');
        $accountEntry->idempresa = $this->document->idempresa;
    }

    /**
     * Generates the regularization entry
     */
    protected function vatAccountingEntry()
    {
        $accountEntry = new Asiento();
        $this->setAccountingData($accountEntry, $this->toolBox()->i18n()->trans('vat-regularization') . ' ' . $this->document->periodo);
        if (!$accountEntry->save()) {
            $this->toolBox()->i18nLog()->warning('accounting-entry-error');
            return;
        }

        if ($this->addAccountingTaxLines($accountEntry) &&
            $this->addAccountingResultLine($accountEntry)) {
            $this->document->idasiento = $accountEntry->primaryColumnValue();
            $this->document->fechaasiento = $accountEntry->fecha;
            return;
        }

        $this->toolBox()->i18nLog()->warning('accounting-lines-error');
        $accountEntry->delete();
    }
}
