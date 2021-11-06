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
use FacturaScripts\Dinamic\Lib\BusinessDocumentTools;
use FacturaScripts\Dinamic\Lib\ExtendedController\PurchaseDocumentController;
use FacturaScripts\Dinamic\Lib\ReceiptGenerator;
use FacturaScripts\Dinamic\Model\FacturaProveedor;

/**
 * Controller to edit a single item from the FacturaProveedor model
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 * @author Rafael San José Tovar    <rafael.sanjose@x-netdigital.com>
 */
class EditFacturaProveedor extends PurchaseDocumentController
{

    /**
     * Return the document class name.
     *
     * @return string
     */
    public function getModelClassName()
    {
        return 'FacturaProveedor';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'purchases';
        $data['title'] = 'invoice';
        $data['icon'] = 'fas fa-file-invoice-dollar';
        return $data;
    }

    /**
     * @param string $viewName
     */
    protected function createAccountsView(string $viewName = 'ListAsiento')
    {
        $this->addListView($viewName, 'Asiento', 'accounting-entries', 'fas fa-balance-scale');

        // buttons
        $this->addButton($viewName, [
            'action' => 'generate-accounting',
            'icon' => 'fas fa-magic',
            'label' => 'generate-accounting-entry'
        ]);

        // settings
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     * @param string $viewName
     */
    protected function createReceiptsView(string $viewName = 'ListReciboProveedor')
    {
        $this->addListView($viewName, 'ReciboProveedor', 'receipts', 'fas fa-dollar-sign');
        $this->views[$viewName]->addOrderBy(['vencimiento'], 'expiration');

        // buttons
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

        // disable columns
        $this->views[$viewName]->disableColumn('invoice');
        $this->views[$viewName]->disableColumn('supplier');

        // settings
        $this->setSettings($viewName, 'modalInsert', 'generate-receipts');
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();

        // prevent users to change readonly property of numero field
        $editViewName = 'Edit' . $this->getModelClassName();
        $this->views[$editViewName]->disableColumn('number', false, 'true');

        $this->createReceiptsView();
        $this->createAccountsView();
        $this->addHtmlView('refunds', 'Tab/RefundFacturaProveedor', 'FacturaProveedor', 'refunds', 'fas fa-share-square');
    }

    /**
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'generate-accounting':
                return $this->generateAccountingAction();

            case 'generate-receipts':
                return $this->generateReceiptsAction();

            case 'new-refund':
                return $this->newRefundAction();

            case 'paid':
                return $this->paidAction();
        }

        return parent::execPreviousAction($action);
    }

    /**
     * @return bool
     */
    protected function generateAccountingAction(): bool
    {
        $invoice = new FacturaProveedor();
        if (false === $invoice->loadFromCode($this->request->query->get('code'))) {
            $this->toolBox()->i18nLog()->warning('record-not-found');
            return true;
        } elseif (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        $generator = new InvoiceToAccounting();
        $generator->generate($invoice);
        if (empty($invoice->idasiento)) {
            $this->toolBox()->i18nLog()->error('record-save-error');
            return true;
        }

        if ($invoice->save()) {
            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            return true;
        }

        $this->toolBox()->i18nLog()->error('record-save-error');
        return true;
    }

    /**
     * @return bool
     */
    protected function generateReceiptsAction(): bool
    {
        $invoice = new FacturaProveedor();
        if (false === $invoice->loadFromCode($this->request->query->get('code'))) {
            $this->toolBox()->i18nLog()->warning('record-not-found');
            return true;
        } elseif (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        $generator = new ReceiptGenerator();
        $number = (int)$this->request->request->get('number', '0');
        if ($generator->generate($invoice, $number)) {
            $generator->update($invoice);
            $invoice->save();

            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            return true;
        }

        $this->toolBox()->i18nLog()->error('record-save-error');
        return true;
    }

    /**
     * Load data view procedure
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mvn = $this->getMainViewName();

        switch ($viewName) {
            case 'ListReciboProveedor':
                $where = [new DataBaseWhere('idfactura', $this->getViewModelValue($mvn, 'idfactura'))];
                $view->loadData('', $where);
                break;

            case 'ListAsiento':
                $where = [new DataBaseWhere('idasiento', $this->getViewModelValue($mvn, 'idasiento'))];
                $view->loadData('', $where);
                break;

            case 'refunds':
                if ($this->getViewModelValue($mvn, 'idfacturarect')) {
                    $this->setSettings($viewName, 'active', false);
                    break;
                }
                $where = [new DataBaseWhere('idfacturarect', $this->getViewModelValue($mvn, 'idfactura'))];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    /**
     * @return bool
     */
    protected function newRefundAction(): bool
    {
        $invoice = new FacturaProveedor();
        if (false === $invoice->loadFromCode($this->request->request->get('idfactura'))) {
            $this->toolBox()->i18nLog()->warning('record-not-found');
            return true;
        } elseif (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        $lines = [];
        foreach ($invoice->getLines() as $line) {
            $quantity = (float)$this->request->request->get('refund_' . $line->primaryColumnValue(), '0');
            if (!empty($quantity)) {
                $lines[] = $line;
            }
        }
        if (empty($lines)) {
            $this->toolBox()->i18nLog()->warning('no-selected-item');
            return true;
        }

        $this->dataBase->beginTransaction();

        if ($invoice->editable) {
            foreach ($invoice->getAvaliableStatus() as $status) {
                if ($status->editable) {
                    continue;
                }

                $invoice->idestado = $status->idestado;
                if (false === $invoice->save()) {
                    $this->toolBox()->i18nLog()->error('record-save-error');
                    $this->dataBase->rollback();
                    return true;
                }
            }
        }

        $newRefund = new FacturaProveedor();
        $newRefund->setAuthor($this->user);
        $newRefund->setSubject($invoice->getSubject());
        $newRefund->codigorect = $invoice->codigo;
        $newRefund->codserie = $this->request->request->get('codserie');
        $newRefund->idfacturarect = $invoice->idfactura;
        $newRefund->numproveedor = $this->request->request->get('numproveedor');
        $newRefund->observaciones = $this->request->request->get('observaciones');
        $newRefund->setDate($this->request->request->get('fecha'), date(FacturaProveedor::HOUR_STYLE));
        if (false === $newRefund->save()) {
            $this->toolBox()->i18nLog()->error('record-save-error');
            $this->dataBase->rollback();
            return true;
        }

        foreach ($lines as $line) {
            $newLine = $newRefund->getNewLine($line->toArray());
            $newLine->cantidad = 0 - (float)$this->request->request->get('refund_' . $line->primaryColumnValue(), '0');
            $newLine->idlinearect = $line->idlinea;
            if (false === $newLine->save()) {
                $this->toolBox()->i18nLog()->error('record-save-error');
                $this->dataBase->rollback();
                return true;
            }
        }

        $tool = new BusinessDocumentTools();
        $tool->recalculate($newRefund);
        $newRefund->idestado = $invoice->idestado;
        if (false === $newRefund->save()) {
            $this->toolBox()->i18nLog()->error('record-save-error');
            $this->dataBase->rollback();
            return true;
        }

        $this->dataBase->commit();
        $this->toolBox()->i18nLog()->notice('record-updated-correctly');
        $this->redirect($newRefund->url() . '&action=save-ok');
        return false;
    }

    /**
     * @return bool
     */
    protected function paidAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        $codes = $this->request->request->get('code');
        $model = $this->views[$this->active]->model;
        if (false === is_array($codes) || empty($model)) {
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
