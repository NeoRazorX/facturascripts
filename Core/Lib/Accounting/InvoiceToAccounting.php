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
     * Accounting plan model
     *
     * @var Model\Cuenta
     */
    protected $account;

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
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->document = NULL;
        $this->subtotals = [];

        $this->exercise = new Model\Ejercicio();
        $this->account = new Model\Cuenta();
        $this->subaccount = new Model\Subcuenta();
        $this->vat = new Model\Impuesto();
    }

    /**
     * Search Customer Account into customer data and customer group data
     *
     * @return string
     */
    protected function getCustomerAccount()
    {
        $sql = 'SELECT COALESCE(clientes.codsubcuenta, gruposclientes.codsubcuenta) codsubcuenta, gruposclientes.parent,'
            . ' FROM clientes'
            . ' LEFT JOIN gruposclientes ON gruposclientes.codgrupo = clientes.codgrupo'
            . ' WHERE clientes.codcliente = ' . $this->dataBase->var2str($this->document->codcliente);

        $data = $this->dataBase->select($sql);
        if (empty($data)) {
            return ''; /// TODO: Client document dont exists. This case should never happen.
        }

        $subaccount = $data[0]['codsubcuenta'];
        if (empty($subaccount)) {
            $parent = $data[0]['parent'];
            if (!empty($parent)) {
                /// TODO: Search in the upper levels of the customer group's family tree.
            }
        }
        return $subaccount;
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
            return []; /// TODO: document dont have lines.
        }

        foreach ($data as $line) {
            if (empty($line['codsubcuenta'])) {
                $line['codsubcuenta'] = $this->getPrefixAccount($type, $default);
            }
        }
        return $data;
    }

    /**
     * Search prefix account for customers/purchases into exercise and account plan
     *
     * @param string $type
     * @param string $default
     * @return string
     */
    protected function getPrefixAccount(string $type, string $default): string
    {
        $where = [
            new DataBaseWhere('codejercicio', $this->document->codejercicio),
            new DataBaseWhere('codcuentaesp', $type)
        ];
        $this->account->loadFromCode('', $where);
        return $this->account->codcuenta ?? $default;
    }

    /**
     * Search VAT Account for VAT Code into exercise and account plan
     *
     * @return string
     */
    protected function getVatAccount(string $vat, string $type, string $default): string
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
        $prefix = $this->getPrefixAccount($type, $default);
        return $this->fillToLength($this->exercise->longsubcuenta, $prefix);
    }

    /**
     * Search document and inicializate auxiliar model class
     *
     * @param Model\Base\BusinessDocument $model
     * @param int $idDocument
     * @return bool
     */
    protected function setDocument(Model\Base\BusinessDocument $model, int $idDocument): bool
    {
        if ($model->loadFromCode($idDocument)) {
            $this->document = $model;
            $this->exercise->loadFromCode($this->document->codejercicio);

            $tools = new BusinessDocumentTools();
            $this->subtotals = $tools->getSubtotals($this->document->getLines());
            return true;
        }

        return false; /// document dont exists
    }

    /**
     * Calculate customer account from customer code
     *
     * @return string
     */
    protected function calculateCustomerAccount(): string
    {
        $prefix = $this->getPrefixAccount('CLIENT', '4300');
        return $this->fillToLength($this->exercise->longsubcuenta, $this->document->codcliente, $prefix);
    }

    /**
     * Generate the accounting entry for a sales document
     *
     * @param int $idDocument
     * @return bool
     */
    public function AccountSales(int $idDocument): bool
    {
        if (!$this->setDocument(new Model\FacturaCliente(), $idDocument)) {
            return false; /// document dont exists, nothing to do
        }

        /// Set Entry Basic Data
        $entry = $this->getEntry();
        array_replace($entry, [
            'id' => $this->document->idasiento,
            'date' => $this->document->fecha,
            'document' => $this->document->codigo,
            'concept' => $this->i18n->trans('account-sales', ['document' => $this->document->codigo, 'customer' => $this->document->nombrecliente]),
            'editable' => false
        ]);

        // Add Customer Line
        $subAccount = $this->getCustumerAccount() ?? $this->calculateCustumerAccount();

        $entry['lines'][] = $this->getLine();
        array_replace($entry['lines'][0], [
            'subaccount' => $subAccount,
            'debit' => $this->document->total,
        ]);

        // Add VAT Lines
        $index = 1;
        foreach ($this->subtotals as $key => $subtotal) {
            $this->vat->loadFromCode($key);

            $entry['lines'][] = $this->getLine(true);
            array_replace($entry['lines'][$index], [
                'subaccount' => $this->getVatAccount($this->vat->codimpuesto, 'IVAREP', '4770'),
                'credit' => $subtotal['totaliva'] + $subtotal['totalrecargo']
            ]);
            array_replace($entry['lines'][$index]['VAT'], [
                'document' => $this->document->codigo,
                'vat-id' => $this->document->cifnif,
                'tax-base' => $subtotal['neto'],
                'pct-vat' => $this->vat->iva,
                'surcharge' => $this->vat->recargo
            ]);
            ++$index;
        }

        // Add Sell Lines
        foreach ($this->getInvoiceLinesAccounts('VENTAS', '7000') as $line) {
            $entry['lines'][] = $this->getLine();
            array_replace($entry['lines'][$index], [
                'subaccount' => $line['codsubcuenta'],
                'credit' => $line['total']
            ]);
            ++$index;
        }

        return $this->AccountEntry($entry);
    }

    /**
     * Generate the accounting entry for a purchase document
     *
     * @param int $idDocument
     * @return bool
     */
    public function AccountPurchase(int $idDocument): bool
    {
        if (!$this->setDocument(new Model\FacturaProveedor(), $idDocument)) {
            return false; /// document dont exists, nothing to do
        }

        /// Set Entry Basic Data
        $entry = $this->getEntry();
        array_replace($entry, [
            'id' => $this->document->idasiento,
            'date' => $this->document->fecha,
            'document' => $this->document->codigo,
            'concept' => $this->i18n->trans('account-purchase', ['document' => $this->document->codigo, 'supplier' => $this->document->nombrecliente]),
            'editable' => false
        ]);

        // Add Purchase Line
        $subAccount = $this->getPurchaseAccount() ?? $this->calculatePurchaseAccount();

        $entry['lines'][] = $this->getLine();
        array_replace($entry['lines'][0], [
            'subaccount' => $subAccount,
            'credit' => $this->document->total,
        ]);

        // Add VAT Lines
        $index = 1;
        foreach ($this->subtotals as $key => $subtotal) {
            $this->vat->loadFromCode($key);

            $entry['lines'][] = $this->getLine(true);
            array_replace($entry['lines'][$index], [
                'subaccount' => $this->getVatAccount($this->vat->codimpuesto, 'IVASUP', '4720'),
                'debit' => $subtotal['totaliva'] + $subtotal['totalrecargo']
            ]);
            array_replace($entry['lines'][$index]['VAT'], [
                'document' => $this->document->codigo,
                'vat-id' => $this->document->cifnif,
                'tax-base' => $subtotal['neto'],
                'pct-vat' => $this->vat->iva,
                'surcharge' => $this->vat->recargo
            ]);
            ++$index;
        }

        // Add Buy Lines
        foreach ($this->getInvoiceLinesAccounts('COMPRA', '6000') as $line) {
            $entry['lines'][] = $this->getLine();
            array_replace($entry['lines'][$index], [
                'subaccount' => $line['codsubcuenta'],
                'debit' => $line['total']
            ]);
            ++$index;
        }

        return $this->AccountEntry($entry);
    }
}
