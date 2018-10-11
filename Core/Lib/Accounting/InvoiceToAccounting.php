<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\BusinessDocumentTools;
use FacturaScripts\Dinamic\Model;

/**
 * Class for the generation of accounting entries of a sale/purchase document
 * and the settlement of your receipts.
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class InvoiceToAccounting extends AccountingGenerator
{

    /**
     * Document Model with data to process
     *
     * @var Model\Base\BusinessDocument
     */
    protected $document;

    /**
     * Document Subtotals Lines array
     *
     * @var array
     */
    protected $subtotals;

    /**
     * Accounting exercise model
     *
     * @var Model\Ejercicio
     */
    protected $exercise;

    /**
     * Subaccounting plan model
     *
     * @var Model\Subcuenta
     */
    protected $subaccount;

    /**
     * VAT model
     *
     * @var Model\Impuesto
     */
    protected $vat;

    /**
     * Class constructor and inicializate auxiliar model class
     *
     * @param Model\FacturaCli|Model\FacturaProv $model
     */
    public function __construct(&$model)
    {
        parent::__construct();

        $this->document = $model;

        $tools = new BusinessDocumentTools();
        $this->subtotals = $tools->getSubtotals($this->document->getLines());

        $this->exercise = new Model\Ejercicio();
        $this->exercise->loadFromCode($this->document->codejercicio);

        $this->subaccount = new Model\Subcuenta();
        $this->vat = new Model\Impuesto();
    }

    /**
     * Search Customer Account into customer data and customer group data
     *
     * @return string|null
     */
    protected function getCustomerAccount()
    {
        $sql = 'SELECT COALESCE(clientes.codsubcuenta, gruposclientes.codsubcuenta) codsubcuenta, gruposclientes.parent'
            . ' FROM clientes'
            . ' LEFT JOIN gruposclientes ON gruposclientes.codgrupo = clientes.codgrupo'
            . ' WHERE clientes.codcliente = ' . $this->dataBase->var2str($this->document->codcliente);

        $data = $this->dataBase->select($sql);
        if (empty($data)) {
            return null; /// Client document dont exists. This case should never happen.
        }

        $subaccount = $data[0]['codsubcuenta'];
        if (empty($subaccount)) {
            if (!empty($data[0]['parent'])) {
                /// TODO: Search in the upper levels of the customer group's family tree.
            }
            return null;
        }
        return $subaccount;
    }

    /**
     * Search Customer Account into customer data and customer group data
     *
     * @return string|null
     */
    protected function getPurchaseAccount()
    {
        $sql = 'SELECT codsubcuenta FROM proveedores'
            . ' WHERE codproveedor = ' . $this->dataBase->var2str($this->document->codproveedor);

        $data = $this->dataBase->select($sql);
        if (empty($data)) {
            return null; /// Purchase document dont exists. This case should never happen.
        }

        $subaccount = $data[0]['codsubcuenta'];
        return empty($subaccount) ? null : $subaccount;
    }

    protected function getInvoiceLinesAccounts(string $type, string $default): array
    {
        $docTable = $this->document->tableName();
        $sql = 'SELECT COALESCE(productos.codsubcuentaven, familias.codsubcuentaven) codsubcuenta, SUM(lineas.pvptotal) total'
            . ' FROM ' . $docTable . ' doc'
            . ' LEFT JOIN lineas' . $docTable . ' lineas ON lineas.idfactura = doc.idfactura'
            . ' LEFT JOIN productos ON productos.referencia = lineas.referencia'
            . ' LEFT JOIN familias ON familias.codfamilia = productos.codfamilia'
            . ' WHERE doc.idfactura = ' . $this->document->idfactura
            . ' GROUP BY 1';

        $data = $this->dataBase->select($sql);
        if (empty($data)) {
            return []; /// document dont have lines. This case should never happen
        }

        foreach ($data as $key => $line) {
            if (empty($line['codsubcuenta'])) {
                $prefix = $this->getPrefixAccount($this->document->codejercicio, $type, $default);
                $data[$key]['codsubcuenta'] = $this->fillToLength($this->exercise->longsubcuenta, '', $prefix);
            }
        }
        return $data;
    }

    /**
     * Search VAT Account for VAT Code into exercise and account plan.
     * 
     * @param string $vat
     * @param string $type
     * @param string $default
     *
     * @return string
     */
    protected function getVatAccount($vat, $type, $default): string
    {
        $where = [
            new DataBaseWhere('codejercicio', $this->document->codejercicio),
            new DataBaseWhere('codimpuesto', $vat),
            new DataBaseWhere('codcuentaesp', $type)
        ];

        if ($this->subaccount->loadFromCode('', $where)) {
            return $this->subaccount->codsubcuenta;
        }

        /// Calculate subaccount from account plan or default account
        $prefix = $this->getPrefixAccount($this->document->codejercicio, $type, $default);
        return $this->fillToLength($this->exercise->longsubcuenta, '', $prefix);
    }

    /**
     * Calculate customer account from customer code
     *
     * @return string
     */
    protected function calculateCustomerAccount(): string
    {
        $prefix = $this->getPrefixAccount($this->document->codejercicio, 'CLIENT', '4300');
        return $this->fillToLength($this->exercise->longsubcuenta, $this->document->codcliente, $prefix);
    }

    /**
     * Calculate purchase account from purchase code
     *
     * @return string
     */
    protected function calculatePurchaseAccount(): string
    {
        $prefix = $this->getPrefixAccount($this->document->codejercicio, 'PROVEE', '4000');
        return $this->fillToLength($this->exercise->longsubcuenta, $this->document->codproveedor, $prefix);
    }

    /**
     * Generate the accounting entry for a sales document
     *
     * @return bool
     */
    public function accountSales(): bool
    {
        if (empty($this->subtotals)) {
            return false; /// document dont have lines, nothing to do
        }

        /// Set Entry Basic Data
        $entry = array_replace($this->getEntry(), [
            'id' => $this->document->idasiento,
            'date' => $this->document->fecha,
            'document' => $this->document->codigo,
            'concept' => $this->i18n->trans('account-sales', ['document' => $this->document->codigo, 'customer' => $this->document->nombrecliente]),
            'editable' => false,
            'total' => $this->document->total
        ]);

        // Add Customer Line
        $subAccount = $this->getCustomerAccount() ?? $this->calculateCustomerAccount();

        $entry['lines'][] = array_replace($this->getLine(), [
            'subaccount' => $subAccount,
            'debit' => $this->document->total,
        ]);

        // Add VAT Lines.
        foreach ($this->subtotals as $key => $subtotal) {
            $this->vat->loadFromCode($key);

            $entry['lines'][] = array_replace($this->getLine(true), [
                'subaccount' => $this->getVatAccount($this->vat->codimpuesto, 'IVAREP', '4770'),
                'offsetting' => $subAccount,
                'credit' => $subtotal['totaliva'] + $subtotal['totalrecargo'],
                'VAT' => [
                    'document' => $this->document->codigo,
                    'vat-id' => $this->document->cifnif,
                    'tax-base' => $subtotal['neto'],
                    'pct-vat' => $this->vat->iva,
                    'surcharge' => $this->vat->recargo
                ]
            ]);
        }

        // Add Sell Lines
        foreach ($this->getInvoiceLinesAccounts('VENTAS', '7000') as $line) {
            $entry['lines'][] = array_replace($this->getLine(), [
                'subaccount' => $line['codsubcuenta'],
                'offsetting' => $subAccount,
                'credit' => $line['total']
            ]);
        }

        /// Generate Account Entry and set id into sale document
        if ($this->accountEntry($entry)) {
            $this->document->idasiento = $entry['id'];
            return true;
        }
        return false;
    }

    /**
     * Generate the accounting entry for a purchase document
     *
     * @return bool
     */
    public function accountPurchase(): bool
    {
        if (empty($this->subtotals)) {
            return false; /// document dont have lines, nothing to do
        }

        /// Set Entry Basic Data
        $entry = array_replace($this->getEntry(), [
            'id' => $this->document->idasiento,
            'date' => $this->document->fecha,
            'document' => $this->document->codigo,
            'concept' => $this->i18n->trans('account-purchase', ['document' => $this->document->codigo, 'supplier' => $this->document->nombre]),
            'editable' => false,
            'total' => $this->document->total
        ]);

        // Add Purchase Line
        $subAccount = $this->getPurchaseAccount() ?? $this->calculatePurchaseAccount();

        $entry['lines'][] = array_replace($this->getLine(), [
            'subaccount' => $subAccount,
            'credit' => $this->document->total,
        ]);

        // Add VAT Lines.
        foreach ($this->subtotals as $key => $subtotal) {
            $this->vat->loadFromCode($key);

            $entry['lines'][] = array_replace($this->getLine(true), [
                'subaccount' => $this->getVatAccount($this->vat->codimpuesto, 'IVASUP', '4720'),
                'offsetting' => $subAccount,
                'debit' => $subtotal['totaliva'] + $subtotal['totalrecargo'],
                'VAT' => [
                    'document' => $this->document->codigo,
                    'vat-id' => $this->document->cifnif,
                    'tax-base' => $subtotal['neto'],
                    'pct-vat' => $this->vat->iva,
                    'surcharge' => $this->vat->recargo
                ]
            ]);
        }

        // Add Sell Lines
        foreach ($this->getInvoiceLinesAccounts('COMPRA', '6000') as $line) {
            $entry['lines'][] = array_replace($this->getLine(), [
                'subaccount' => $line['codsubcuenta'],
                'offsetting' => $subAccount,
                'debit' => $line['total']
            ]);
        }

        /// Generate Account Entry and set id into sale document
        if ($this->accountEntry($entry)) {
            $this->document->idasiento = $entry['id'];
            return true;
        }
        return false;
    }
}
