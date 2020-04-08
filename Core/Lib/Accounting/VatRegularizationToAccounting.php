<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
     * Sum of the items accounting on the credit
     *
     * @var float
     */
    private $credit = 0.0;

    /**
     * Sum of the items accounting on the debit
     *
     * @var float
     */
    private $debit = 0.0;

    /**
     * Regularization that is accounting
     *
     * @var RegularizacionImpuesto
     */
    protected $document;

    /**
     *
     * @var SubAccountTools
     */
    private $subAccTools;

    /**
     * Method to launch the accounting process
     *
     * @param RegularizacionImpuesto $model
     */
    public function generate($model)
    {
        parent::generate($model);
        $this->subAccTools = new SubAccountTools();
        if (!$this->initialChecks()) {
            return;
        }

        /// Create accounting entry
        $accEntry = new Asiento();
        $accEntry->codejercicio = $this->document->codejercicio;
        $accEntry->concepto = $this->toolBox()->i18n()->trans('vat-regularization') . ' ' . $this->document->periodo;
        $accEntry->fecha = $this->document->fechafin;
        $accEntry->idempresa = $this->document->idempresa;
        if (false === $accEntry->save()) {
            $this->toolBox()->i18nLog()->warning('accounting-entry-error');
            return;
        }

        if ($this->addAccountingTaxLines($accEntry) && $this->addAccountingResultLine($accEntry) && $accEntry->isBalanced()) {
            $this->document->idasiento = $accEntry->primaryColumnValue();
            $this->document->fechaasiento = $accEntry->fecha;

            $accEntry->importe = \max([$this->debit, $this->credit]);
            $accEntry->save();
            return;
        }

        $this->toolBox()->i18nLog()->warning('accounting-lines-error');
        $accEntry->delete();
    }

    /**
     * Add the game with the result of regularization
     *
     * @param Asiento $accEntry
     *
     * @return bool
     */
    protected function addAccountingResultLine($accEntry): bool
    {
        $subaccount = new Subcuenta();
        if ($this->debit >= $this->credit) {
            $subaccount->loadFromCode($this->document->idsubcuentaacr);
            return $this->addBasicLine($accEntry, $subaccount, false, $this->debit - $this->credit);
        }

        $subaccount->loadFromCode($this->document->idsubcuentadeu);
        return $this->addBasicLine($accEntry, $subaccount, true, $this->credit - $this->debit);
    }

    /**
     * Add the items with the tax amounts
     *
     * @param Asiento $accEntry
     *
     * @return bool
     */
    protected function addAccountingTaxLines($accEntry): bool
    {
        $subaccount = new Subcuenta();

        foreach ($this->getSubtotals() as $idsubcuenta => $total) {
            if (false === $subaccount->loadFromCode($idsubcuenta)) {
                continue;
            }

            $newLine = $accEntry->getNewLine();
            $newLine->setAccount($subaccount);
            $newLine->debe = \round($total['debe'], \FS_NF0);
            $newLine->haber = \round($total['haber'], \FS_NF0);
            if ($newLine->save()) {
                $this->debit += $newLine->debe;
                $this->credit += $newLine->haber;
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * Obtain the cumulative list of amounts by tax
     *
     * @return array
     */
    protected function getSubtotals()
    {
        $field = 'COALESCE(subcuentas.codcuentaesp, cuentas.codcuentaesp)';
        $where = [
            new DataBaseWhere('asientos.codejercicio', $this->document->codejercicio),
            new DataBaseWhere('asientos.fecha', $this->document->fechainicio, '>='),
            new DataBaseWhere('asientos.fecha', $this->document->fechafin, '<='),
            $this->subAccTools->whereForSpecialAccounts($field, SubAccountTools::SPECIAL_GROUP_TAX_ALL)
        ];
        $orderby = [
            $field => 'ASC',
            'partidas.iva' => 'ASC',
            'partidas.recargo' => 'ASC'
        ];

        $subtotals = [];
        $inputTaxGroup = $this->subAccTools->specialAccountsForGroup(SubAccountTools::SPECIAL_GROUP_TAX_INPUT);
        $outputTaxGroup = $this->subAccTools->specialAccountsForGroup(SubAccountTools::SPECIAL_GROUP_TAX_OUTPUT);
        $totals = new PartidaImpuestoResumen();
        foreach ($totals->all($where, $orderby) as $row) {
            if (!isset($subtotals[$row->idsubcuenta])) {
                $subtotals[$row->idsubcuenta] = ['debe' => 0.0, 'haber' => 0.0];
            }

            if (\in_array($row->codcuentaesp, $outputTaxGroup)) {
                $subtotals[$row->idsubcuenta]['debe'] += $row->cuotaiva + $row->cuotarecargo;
            } elseif (\in_array($row->codcuentaesp, $inputTaxGroup)) {
                $subtotals[$row->idsubcuenta]['haber'] += $row->cuotaiva + $row->cuotarecargo;
            }
        }

        return $subtotals;
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

        if ($this->exercise->loadFromCode($this->document->codejercicio) && $this->exercise->isOpened()) {
            return true;
        }

        $this->toolBox()->i18nLog()->warning('closed-exercise', ['%exerciseName%' => $this->document->codejercicio]);
        return false;
    }
}
