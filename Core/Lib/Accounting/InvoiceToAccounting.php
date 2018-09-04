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
     * Document to process
     *
     * @var FacturaCliente|FacturaProveedor
     */
    protected $document;

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
            /// TODO: Client document dont exists. This case should never happen.
            return '';
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
     * Search prefis account for customers into exercise and account plan
     *
     * @return string
     */
    protected function getCustomerPrefixAccount(): string
    {
        $where = [
            new DataBaseWhere('codejercicio', $this->document->codejercicio),
            new DataBaseWhere('codcuentaesp', 'CLIENT')
        ];
        $account = new Model\Cuenta();
        $account->loadFromCode('', $where);
        return $account->codcuenta ?? '4300';
    }

    /**
     * Calculate customer account from customer code
     *
     * @return string
     */
    protected function calculateCustomerAccount(): string
    {
        $prefix = $this->getCustomerPrefixAccount();
        $exercise = new Model\Ejercicio();
        $exercise->loadFromCode($this->document->codejercicio);

        $count = $exercise->longsubcuenta - strlen($prefix) - strlen($this->document->codcliente);
        if ($count < 0) {
            /// TODO: customer code its to long
            return '';
        }

        return $prefix . str_repeat('0', $count) . $this->document->codcliente;
    }

    /**
     * Generate the accounting entry for a sales document
     *
     * @param int $idDocument
     * @return bool
     */
    public function AccountSales(int $idDocument): bool
    {
        $this->document = new Model\FacturaCliente();
        if (!$this->document->loadFromCode($idDocument)) {
            /// TODO: document dont exists
            return false;
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

        foreach ($tools->getSubtotals($lines) as $key => $subtotal) {
            $index = count($entry['lines']);
            $vat->loadFromCode($key);

            $entry['lines'][] = $this->getLine(true);
            array_replace($entry['lines'][$index], [
                'subaccount' => '477',
                'credit' => $subtotal['totaliva'] + $subtotal['totalrecargo'],
            ]);
            array_replace($entry['lines'][$index]['VAT'], [
                'document' => $this->document->codigo,
                'vat-id' => $this->document->cifnif,
                'tax-base' => $subtotal['neto'],
                'pct-vat' => $vat->iva,
                'surcharge' => $vat->recargo
            ]);
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
