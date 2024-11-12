<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base\Receipt;
use FacturaScripts\Core\Model\Base\TransformerDocument;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Accounting\InvoiceToAccounting;

/**
 * Contains common utilities for grouping and collecting documents.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
trait ListBusinessActionTrait
{
    abstract public function addButton(string $viewName, array $btnArray);

    abstract public function redirect(string $url, int $delay = 0);

    abstract protected function validateFormToken(): bool;

    /**
     * Adds buttons to approve documents.
     *
     * @param string $viewName
     */
    protected function addButtonApproveDocument(string $viewName)
    {
        $this->addButton($viewName, [
            'action' => 'approve-document-same-date',
            'confirm' => 'true',
            'icon' => 'fa-solid fa-calendar-check',
            'label' => 'approve-document-same-date'
        ]);

        $this->addButton($viewName, [
            'action' => 'approve-document',
            'confirm' => 'true',
            'icon' => 'fa-solid fa-check',
            'label' => 'approve-document'
        ]);
    }

    /**
     * Adds button to lock invoices.
     *
     * @param string $viewName
     * @param string|null $code
     */
    protected function addButtonGenerateAccountingInvoices(string $viewName, string $code = null): void
    {
        $model = $this->views[$viewName]->model;
        if (false === in_array($model->modelClassName(), ['FacturaCliente', 'FacturaProveedor'])) {
            return;
        }

        $where = [
            new DataBaseWhere('idasiento', null, 'IS'),
            new DataBaseWhere('fecha', Tools::date('-1 year'), '>'),
            new DataBaseWhere('total', 0, '!=')
        ];

        if (false === empty($code) && property_exists($model, 'codcliente')) {
            $where[] = new DataBaseWhere('codcliente', $code);
        } elseif (false === empty($code) && property_exists($model, 'codproveedor')) {
            $where[] = new DataBaseWhere('codproveedor', $code);
        }

        if ($model->count($where) <= 0) {
            return;
        }

        $this->addButton($viewName, [
            'action' => 'generate-accounting-entries',
            'color' => 'warning',
            'icon' => 'fa-solid fa-wand-magic-sparkles',
            'label' => 'generate-accounting-entries'
        ]);
    }

    /**
     * Adds button to group documents.
     *
     * @param string $viewName
     */
    protected function addButtonGroupDocument(string $viewName): void
    {
        $this->addButton($viewName, [
            'action' => 'group-document',
            'icon' => 'fa-solid fa-wand-magic-sparkles',
            'label' => 'group-or-split'
        ]);
    }

    /**
     * Adds button to lock invoices.
     *
     * @param string $viewName
     */
    protected function addButtonLockInvoice(string $viewName): void
    {
        $this->addButton($viewName, [
            'action' => 'lock-invoice',
            'confirm' => 'true',
            'icon' => 'fa-solid fa-lock fa-fw',
            'label' => 'lock-invoice'
        ]);
    }

    /**
     * Adds button to pay receipts.
     *
     * @param string $viewName
     */
    protected function addButtonPayReceipt(string $viewName): void
    {
        $this->addButton($viewName, [
            'action' => 'pay-receipt',
            'confirm' => 'true',
            'icon' => 'fa-solid fa-dollar-sign',
            'label' => 'paid',
            'type' => 'action'
        ]);
    }

    /**
     * Approves selected documents.
     *
     * @param mixed $codes
     * @param TransformerDocument $model
     * @param bool $allowUpdate
     * @param DataBase $dataBase
     *
     * @return bool
     */
    protected function approveDocumentAction($codes, $model, $allowUpdate, $dataBase): bool
    {
        if (false === $allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        } elseif (false === is_array($codes) || empty($model)) {
            Tools::log()->warning('no-selected-item');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        $dataBase->beginTransaction();
        foreach ($codes as $code) {
            if (false === $model->loadFromCode($code)) {
                Tools::log()->error('record-not-found');
                continue;
            }

            foreach ($model->getAvailableStatus() as $status) {
                if (empty($status->generadoc) || !$status->activo) {
                    continue;
                }

                $model->idestado = $status->idestado;
                if ($model->save()) {
                    break;
                }

                Tools::log()->error('record-save-error');
                $dataBase->rollback();
                return true;
            }
        }

        Tools::log()->notice('record-updated-correctly');
        $dataBase->commit();
        $model->clear();
        return true;
    }

    protected function generateAccountingEntriesAction($model, $allowUpdate, $dataBase): bool
    {
        if (false === $allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        if (false === in_array($model->modelClassName(), ['FacturaCliente', 'FacturaProveedor'])) {
            return true;
        }

        $dataBase->beginTransaction();
        $where = [
            new DataBaseWhere('idasiento', null, 'IS'),
            new DataBaseWhere('fecha', Tools::date('-1 year'), '>'),
            new DataBaseWhere('total', 0, '!=')
        ];
        foreach ($model->all($where, ['idfactura' => 'ASC'], 0, 300) as $invoice) {
            if (false === empty($invoice->idasiento)) {
                continue;
            }

            $generator = new InvoiceToAccounting();
            $generator->generate($invoice);
            if (empty($invoice->idasiento)) {
                Tools::log()->error('cannot-generate-accounting-entry', ['%invoice%' => $invoice->codigo]);
                $dataBase->rollback();
                return true;
            }

            if (false === $invoice->save()) {
                Tools::log()->error('record-save-error', ['invoice' => $invoice->codigo]);
                $dataBase->rollback();
                return true;
            }
        }

        Tools::log()->notice('record-updated-correctly');
        $dataBase->commit();
        return true;
    }

    /**
     * Group selected documents.
     *
     * @param mixed $codes
     * @param TransformerDocument $model
     *
     * @return bool
     */
    protected function groupDocumentAction($codes, $model): bool
    {
        if (!empty($codes) && $model) {
            $codes = implode(',', $codes);
            $url = 'DocumentStitcher?model=' . $model->modelClassName() . '&codes=' . $codes;
            $this->redirect($url);
            return false;
        }

        Tools::log()->warning('no-selected-item');
        return true;
    }

    /**
     * Locks selected invoices.
     *
     * @param mixed $codes
     * @param TransformerDocument $model
     * @param bool $allowUpdate
     * @param DataBase $dataBase
     *
     * @return bool
     */
    protected function lockInvoiceAction($codes, $model, $allowUpdate, $dataBase): bool
    {
        if (false === $allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        } elseif (false === is_array($codes) || empty($model)) {
            Tools::log()->warning('no-selected-item');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        $dataBase->beginTransaction();
        foreach ($codes as $code) {
            if (false === $model->loadFromCode($code)) {
                Tools::log()->error('record-not-found');
                continue;
            }

            foreach ($model->getAvailableStatus() as $status) {
                if ($status->editable || !$status->activo) {
                    continue;
                }

                $model->idestado = $status->idestado;
                if ($model->save()) {
                    break;
                }

                Tools::log()->error('record-save-error');
                $dataBase->rollback();
                return true;
            }
        }

        Tools::log()->notice('record-updated-correctly');
        $dataBase->commit();
        $model->clear();
        return true;
    }

    /**
     * Sets selected receipts as paid.
     *
     * @param mixed $codes
     * @param Receipt $model
     * @param bool $allowUpdate
     * @param DataBase $dataBase
     * @param string $nick
     *
     * @return bool
     */
    protected function payReceiptAction($codes, $model, $allowUpdate, $dataBase, $nick): bool
    {
        if (false === $allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        } elseif (false === is_array($codes) || empty($model)) {
            Tools::log()->warning('no-selected-item');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        $dataBase->beginTransaction();
        foreach ($codes as $code) {
            if (false === $model->loadFromCode($code)) {
                Tools::log()->error('record-not-found');
                continue;
            }

            $model->nick = $nick;
            $model->pagado = true;
            if (false === $model->save()) {
                Tools::log()->error('record-save-error');
                $dataBase->rollback();
                return true;
            }
        }

        Tools::log()->notice('record-updated-correctly');
        $dataBase->commit();
        $model->clear();
        return true;
    }
}
