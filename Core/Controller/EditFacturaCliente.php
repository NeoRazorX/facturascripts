<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Dinamic\Lib\Accounting\InvoiceToAccounting;
use FacturaScripts\Dinamic\Lib\BusinessDocumentGenerator;
use FacturaScripts\Dinamic\Lib\ExtendedController\SalesDocumentController;
use FacturaScripts\Dinamic\Lib\ReceiptGenerator;
use FacturaScripts\Dinamic\Model\FacturaCliente;

/**
 * Controller to edit a single item from the FacturaCliente model
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Luis Miguel Pérez        <luismi@pcrednet.com>
 * @author Rafael San José Tovar    <rafael.sanjose@x-netdigital.com>
 */
class EditFacturaCliente extends SalesDocumentController
{

    /**
     * Return the document class name.
     *
     * @return string
     */
    public function getModelClassName()
    {
        return 'FacturaCliente';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'invoice';
        $data['icon'] = 'fas fa-file-invoice-dollar';
        return $data;
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createAccountsView(string $viewName = 'ListAsiento')
    {
        $this->addListView($viewName, 'Asiento', 'accounting-entries', 'fas fa-balance-scale');

        /// buttons
        $this->addButton($viewName, [
            'action' => 'generate-accounting',
            'icon' => 'fas fa-magic',
            'label' => 'generate-accounting-entry'
        ]);

        /// settings
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createReceiptsView(string $viewName = 'ListReciboCliente')
    {
        $this->addListView($viewName, 'ReciboCliente', 'receipts', 'fas fa-dollar-sign');
        $this->views[$viewName]->addOrderBy(['vencimiento'], 'expiration');

        /// buttons
        $this->addButton($viewName, [
            'action' => 'generate-receipts',
            'confirm' => 'true',
            'icon' => 'fas fa-magic',
            'label' => 'generate-receipts'
        ]);

        $this->addButton($viewName, [
            'action' => 'paid',
            'confirm' => 'true',
            'icon' => 'fas fa-check',
            'label' => 'paid'
        ]);

        /// disable columns
        $this->views[$viewName]->disableColumn('customer');
        $this->views[$viewName]->disableColumn('invoice');

        /// settings
        $this->setSettings($viewName, 'modalInsert', 'generate-receipts');
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();

        /// prevent users to change readonly property of numero field
        $editViewName = 'Edit' . $this->getModelClassName();
        $this->views[$editViewName]->disableColumn('number', false, 'true');

        $this->createReceiptsView();
        $this->createAccountsView();
        $this->addHtmlView('Refund', 'Tab/RefundFacturaCliente', 'FacturaCliente', 'refunds', 'fas fa-share-square');
    }

    /**
     * 
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'generate-accounting':
                $this->generateAccountingAction();
                break;

            case 'generate-receipts':
                $this->generateReceiptsAction();
                break;

            case 'new-refund':
                $this->newRefundAction();
                break;

            case 'paid':
                return $this->paidAction();
        }

        return parent::execPreviousAction($action);
    }

    /**
     * 
     * @return bool
     */
    protected function generateAccountingAction()
    {
        $invoice = new FacturaCliente();
        if (false === $invoice->loadFromCode($this->request->query->get('code'))) {
            $this->toolBox()->i18nLog()->warning('record-not-found');
            return false;
        }

        $generator = new InvoiceToAccounting();
        $generator->generate($invoice);
        if (empty($invoice->idasiento)) {
            $this->toolBox()->i18nLog()->error('record-save-error');
            return false;
        }

        if ($invoice->save()) {
            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            return true;
        }

        $this->toolBox()->i18nLog()->error('record-save-error');
        return false;
    }

    /**
     * 
     * @return bool
     */
    protected function generateReceiptsAction()
    {
        $invoice = new FacturaCliente();
        if (false === $invoice->loadFromCode($this->request->query->get('code'))) {
            $this->toolBox()->i18nLog()->warning('record-not-found');
            return false;
        }

        $generator = new ReceiptGenerator();
        $number = (int) $this->request->request->get('number', '0');
        if ($generator->generate($invoice, $number)) {
            $generator->update($invoice);
            $invoice->save();

            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            return true;
        }

        $this->toolBox()->i18nLog()->error('record-save-error');
        return false;
    }

    /**
     * Load data view procedure
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'Refund':
            case 'ListReciboCliente':
                $where = [new DataBaseWhere('idfactura', $this->getViewModelValue($this->getLineXMLView(), 'idfactura'))];
                $view->loadData('', $where);
                break;

            case 'ListAsiento':
                $where = [new DataBaseWhere('idasiento', $this->getViewModelValue($this->getLineXMLView(), 'idasiento'))];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
        }
    }

    /**
     * 
     * @return bool
     */
    protected function newRefundAction()
    {
        $invoice = new FacturaCliente();
        if (false === $invoice->loadFromCode($this->request->request->get('idfactura'))) {
            $this->toolBox()->i18nLog()->warning('record-not-found');
            return false;
        }

        $lines = [];
        $quantities = [];
        foreach ($invoice->getLines() as $line) {
            $quantity = (float) $this->request->request->get('refund_' . $line->primaryColumnValue(), '0');
            if (empty($quantity)) {
                continue;
            }

            $quantities[$line->primaryColumnValue()] = 0 - $quantity;
            $lines[] = $line;
        }

        if (empty($quantities)) {
            $this->toolBox()->i18nLog()->warning('no-selected-item');
            return false;
        }

        $generator = new BusinessDocumentGenerator();
        $properties = [
            'codigorect' => $invoice->codigo,
            'codserie' => $this->request->request->get('codserie'),
            'fecha' => $this->request->request->get('fecha'),
            'idfacturarect' => $invoice->idfactura,
            'observaciones' => $this->request->request->get('observaciones')
        ];
        if ($generator->generate($invoice, $invoice->modelClassName(), $lines, $quantities, $properties)) {
            foreach ($generator->getLastDocs() as $doc) {
                $this->toolBox()->i18nLog()->notice('record-updated-correctly');
                $this->redirect($doc->url() . '&action=save-ok');
                return true;
            }
        }

        $this->toolBox()->i18nLog()->error('record-save-error');
        return false;
    }

    /**
     * 
     * @return bool
     */
    protected function paidAction()
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return true;
        }

        $codes = $this->request->request->get('code');
        $model = $this->views[$this->active]->model;
        if (false === \is_array($codes) || empty($model)) {
            $this->toolBox()->i18nLog()->warning('no-selected-item');
            return true;
        }

        foreach ($codes as $code) {
            if (false === $model->loadFromCode($code)) {
                $this->toolBox()->i18nLog()->error('record-not-found');
                continue;
            }

            $model->nick = $this->user->nick;
            $model->pagado = true;
            if (false === $model->save()) {
                $this->toolBox()->i18nLog()->error('record-save-error');
                return true;
            }
        }

        $this->toolBox()->i18nLog()->notice('record-updated-correctly');
        $model->clear();
        return true;
    }
}
