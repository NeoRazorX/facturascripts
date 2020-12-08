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
namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Lib\ExtendedController\GridView;
use FacturaScripts\Dinamic\Lib\Accounting\AccountingAccounts;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Impuesto;
use FacturaScripts\Dinamic\Model\Join\SubcuentaSaldo;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * A set of tools to recalculate accounting entries.
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class AccountingEntryTools
{

    const TYPE_TAX_NONE = 0;
    const TYPE_TAX_INPUT = 1;
    const TYPE_TAX_OUTPUT = 2;

    /**
     *
     * @var SubAccountTools
     */
    protected $subAccountTools;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->subAccountTools = new SubAccountTools();
    }

    /**
     * Load data and balances from subaccount
     *
     * @param string $exercise
     * @param string $codeSubAccount
     * @param int    $channel
     *
     * @return array
     */
    public function getAccountData($exercise, $codeSubAccount, $channel): array
    {
        $result = [
            'subaccount' => $codeSubAccount,
            'description' => '',
            'specialaccount' => '',
            'balance' => 0.00,
            'detail' => [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00]
        ];

        if (empty($exercise) || empty($codeSubAccount)) {
            return $result;
        }

        $where = [
            new DataBaseWhere('codsubcuenta', $codeSubAccount),
            new DataBaseWhere('codejercicio', $exercise)
        ];

        $subAccount = new Subcuenta();
        if ($subAccount->loadFromCode('', $where)) {
            $result['description'] = $subAccount->descripcion;
            $result['specialaccount'] = $subAccount->getSpecialAccountCode();
            $result['hasvat'] = $this->subAccountTools->hasTax($result['specialaccount']);

            if ($this->toolBox()->appSettings()->get('default', 'balancegraphic', false)) {
                $balance = new SubcuentaSaldo();
                $result['balance'] = $balance->setSubAccountBalance($subAccount->idsubcuenta, $channel, $result['detail']);
                $result['balance'] = $this->toolBox()->coins()->format($result['balance']);
            }
        }

        return $result;
    }

    /**
     * Calculate data document
     *
     * @param GridView $view
     * @param array    $data
     *
     * @return array
     */
    public function recalculate($view, &$data): array
    {
        $result = [
            'total' => 0.00,
            'unbalance' => 0.00,
            'lines' => [],
            'subaccount' => [],
            'vat' => []
        ];

        if (isset($data['lines'])) {
            // Prepare lines data
            $lines = $view->processFormLines($data['lines']);

            // Recalculate lines data and amounts
            $totalCredit = $totalDebit = 0.00;
            $result['lines'] = $this->recalculateLines($lines, $totalCredit, $totalDebit);
            $this->calculateAmounts($result, $totalCredit, $totalDebit);

            // If only change subaccount, search for subaccount data
            if (\count($data['changes']) === 1 && $data['changes'][0][1] === 'codsubcuenta') {
                $index = $data['changes'][0][0];
                $line = &$result['lines'][$index];
                $exercise = $data['document']['codejercicio'];
                $channel = $data['document']['canal'];
                $result['subaccount'] = $this->getAccountData($exercise, $line['codsubcuenta'], $channel);
                $result['vat'] = $this->recalculateVatRegister($line, $data['document'], (string) $result['subaccount']['specialaccount'], $result['unbalance']);
            }
        }
        $result['hasvat'] = !empty($result['vat']);
        return $result;
    }

    /**
     * Calculate unbalance and total imports
     *
     * @param array $data
     * @param float $credit
     * @param float $debit
     */
    protected function calculateAmounts(array &$data, float $credit, float $debit)
    {
        $unbalance = \round(($credit - $debit), (int) FS_NF0);
        $index = \count($data['lines']) - 1;
        $line = &$data['lines'][$index];
        $lineDebit = (double) $line['debe'] ?? 0.00;
        $lineCredit = (double) $line['haber'] ?? 0.00;

        if (($lineDebit + $lineCredit) === 0.00 && $index > 0) {
            $counterpart = $data['lines'][$index - 1]['codcontrapartida'] ?? null;
            // if the sub-account is the same as the previous counterpart
            if ($line['codsubcuenta'] === $counterpart) {
                $field = $unbalance < 0 ? 'debe' : 'haber';
                $line[$field] = abs($unbalance);
                $unbalance = 0.00;
            }
        }

        $data['unbalance'] = $unbalance;
        $data['total'] = ($credit > $debit) ? \round($credit, (int) FS_NF0) : \round($debit, (int) FS_NF0);
    }

    /**
     * Auto complete data for new account line
     *
     * @param array $line
     * @param array $previousLine
     */
    protected function checkEmptyValues(array &$line, array $previousLine)
    {
        if (isset($line['codsubcuenta']) && isset($previousLine['codsubcuenta'])) {
            if (empty($line['concepto'])) {
                $line['concepto'] = $previousLine['concepto'];
            }

            if (empty($line['codcontrapartida']) && !empty($line['codsubcuenta'])) {
                // TODO: [Fix] Go through previous lines in search of the counterpart. The sub-account that uses the counterpart for the first time is needed
                $line['codcontrapartida'] = ($line['codsubcuenta'] === $previousLine['codcontrapartida']) ? $previousLine['codsubcuenta'] : $previousLine['codcontrapartida'];
            }
        }
    }

    /**
     * Get VAT information for a sub-account
     *
     * @param string $exercise
     * @param string $codeSubAccount
     *
     * @return array
     */
    protected function getAccountVatID($exercise, $codeSubAccount): array
    {
        $result = ['group' => '', 'code' => '', 'description' => '', 'id' => '', 'surcharge' => false];
        if (empty($exercise) || empty($codeSubAccount)) {
            return $result;
        }

        $where = [
            new DataBaseWhere('codsubcuenta', $codeSubAccount),
            new DataBaseWhere('codejercicio', $exercise)
        ];

        $subAccount = new Subcuenta();
        if ($subAccount->loadFromCode('', $where)) {
            $result['group'] = $subAccount->getSpecialAccountCode();
            switch ($result['group']) {
                case AccountingAccounts::SPECIAL_CUSTOMER_ACCOUNT:
                    $client = new Cliente();
                    $this->setBusinessData($client, $codeSubAccount, $result);
                    break;

                case AccountingAccounts::SPECIAL_CREDITOR_ACCOUNT:
                case AccountingAccounts::SPECIAL_SUPPLIER_ACCOUNT:
                    $supplier = new Proveedor();
                    $this->setBusinessData($supplier, $codeSubAccount, $result);
                    break;
            }
        }
        return $result;
    }

    /**
     * Calculate data lines and credit/debit imports
     *
     * @param array $lines
     * @param float $totalCredit
     * @param float $totalDebit
     *
     * @return array
     */
    protected function recalculateLines(array $lines, float &$totalCredit, float &$totalDebit): array
    {
        // Work variables
        $result = [];
        $previous = null;
        $totalCredit = 0.00;
        $totalDebit = 0.00;

        // Check lines data
        foreach ($lines as $item) {
            if (empty($item['codsubcuenta'])) {
                $result[] = $item;
                continue;
            }

            // check empty imports
            if (empty($item['debe'])) {
                $item['debe'] = 0.00;
            }
            if (empty($item['haber'])) {
                $item['haber'] = 0.00;
            }

            // copy previous values to empty fields
            if (!empty($previous)) {
                $this->checkEmptyValues($item, $previous);
            }
            $previous = $item;

            // Acumulate imports
            $totalCredit += $item['debe'];
            $totalDebit += $item['haber'];
            $result[] = $item;
        }
        return $result;
    }

    /**
     *
     * @param string $specialAccount
     * @return int
     */
    private function getTypeVat($specialAccount)
    {
        if (empty($specialAccount)) {
            return self::TYPE_TAX_NONE;
        }

        if ($this->subAccountTools->isInputTax($specialAccount)) {
            return self::TYPE_TAX_INPUT;
        }

        if ($this->subAccountTools->isOutputTax($specialAccount)) {
            return self::TYPE_TAX_OUTPUT;
        }

        return self::TYPE_TAX_NONE;
    }

    /**
     * Calculate Vat Register data
     *
     * @param array  $line
     * @param array  $document
     * @param string $specialAccount
     * @param float  $base
     *
     * @return array
     */
    protected function recalculateVatRegister(array &$line, array $document, string $specialAccount, float $base): array
    {
        $typeVat = $this->getTypeVat($specialAccount);
        if ($typeVat === self::TYPE_TAX_NONE) {
            $line['cifnif'] = null;
            $line['documento'] = null;
            $line['baseimponible'] = null;
            $line['iva'] = null;
            $line['recargo'] = null;
            return [];
        }

        $vatModel = new Impuesto();
        $vat = $typeVat == self::TYPE_TAX_INPUT ? $vatModel->inputVatFromSubAccount($line['codsubcuenta']) : $vatModel->outputVatFromSubAccount($line['codsubcuenta']);

        $result = $this->getAccountVatID($document['codejercicio'], $line['codcontrapartida']);

        $line['documento'] = $document['documento'] ?? null;
        $line['cifnif'] = $result['id'];
        $line['iva'] = $vat->iva;
        $line['recargo'] = $result['surcharge'] ? $vat->recargo : 0.00;
        $line['baseimponible'] = ($result['group'] === AccountingAccounts::SPECIAL_CUSTOMER_ACCOUNT) ? ($base * -1) : $base;
        return $result;
    }

    /**
     *
     * @param Cliente|Proveedor $model
     * @param string            $codeSubAccount
     * @param array             $values
     */
    private function setBusinessData($model, $codeSubAccount, &$values)
    {
        $where = [new DataBaseWhere('codsubcuenta', $codeSubAccount)];
        $supplier = new Proveedor();
        if ($supplier->loadFromCode('', $where)) {
            $values['code'] = $supplier->codproveedor;
            $values['description'] = $supplier->nombre;
            $values['id'] = $supplier->cifnif;
            $values['surcharge'] = ($model->regimeniva == RegimenIVA::TAX_SYSTEM_SURCHARGE);
        }
    }

    /**
     *
     * @return ToolBox
     */
    private function toolBox()
    {
        return new ToolBox();
    }
}
