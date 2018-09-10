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
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->document = NULL;
        $this->exercise = new Model\Ejercicio();
        $this->account = new Model\Cuenta();
        $this->subaccount = new Model\Subcuenta();
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
        $tools = new BusinessDocumentTools();
        $vat = new Model\Impuesto();
        $lines = $this->document->getLines();
        $index = 1;

        foreach ($tools->getSubtotals($lines) as $key => $subtotal) {
            $vat->loadFromCode($key);

            $entry['lines'][] = $this->getLine(true);
            array_replace($entry['lines'][$index], [
                'subaccount' => $this->getVatAccount($vat->codimpuesto, 'IVAREP', '4770'),
                'credit' => $subtotal['totaliva'] + $subtotal['totalrecargo'],
            ]);
            array_replace($entry['lines'][$index]['VAT'], [
                'document' => $this->document->codigo,
                'vat-id' => $this->document->cifnif,
                'tax-base' => $subtotal['neto'],
                'pct-vat' => $vat->iva,
                'surcharge' => $vat->recargo
            ]);
            ++$index;
        }

        // Add Sell Lines
//        $where = new DataBase\DataBaseWhere('idfactura', $idDocument);
//        ESTOY AQUI!!!
//        Mejor crear un model view que recoja los datos
//        $linesModel = new Model\LineaFacturaCliente();
//        $lines = $linesModel->all($where, $order, $idDocument, $limit);

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
        $document = new Model\FacturaProveedor();
        $document->loadFromCode($idDocument);

        $entry = $this->getEntry();
        array_replace($entry, [
            'date' => $document->fecha,
            'document' => $document->codigo,
            'concept' => $this->i18n->trans('account-purchase', ['document' => $document->codigo, 'supplier' => $document->nombre]),
            'editable' => false
        ]);



        return $this->AccountEntry($entry);
    }
}
