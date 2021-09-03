<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Model\Base\Receipt;
use FacturaScripts\Core\Model\Base\TransformerDocument;

/**
 * Contains common utilities for grouping and collecting documents.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
trait ListBusinessActionTrait
{

    abstract public function addButton(string $viewName, array $btnArray);

    abstract public function redirect(string $url, int $delay = 0);

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
            'icon' => 'fas fa-calendar-check',
            'label' => 'approve-document-same-date'
        ]);

        $this->addButton($viewName, [
            'action' => 'approve-document',
            'confirm' => 'true',
            'icon' => 'fas fa-check',
            'label' => 'approve-document'
        ]);
    }

    /**
     * Adds button to group documents.
     *
     * @param string $viewName
     */
    protected function addButtonGroupDocument(string $viewName)
    {
        $this->addButton($viewName, [
            'action' => 'group-document',
            'icon' => 'fas fa-magic',
            'label' => 'group-or-split'
        ]);
    }

    /**
     * Adds button to lock invoices.
     *
     * @param string $viewName
     */
    protected function addButtonLockInvoice(string $viewName)
    {
        $this->addButton($viewName, [
            'action' => 'lock-invoice',
            'confirm' => 'true',
            'icon' => 'fas fa-lock fa-fw',
            'label' => 'lock-invoice'
        ]);
    }

    /**
     * Adds button to pay receipts.
     *
     * @param string $viewName
     */
    protected function addButtonPayReceipt(string $viewName)
    {
        $this->addButton($viewName, [
            'action' => 'pay-receipt',
            'confirm' => 'true',
            'icon' => 'fas fa-check',
            'label' => 'pay',
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
            ToolBox::i18nLog()->warning('not-allowed-modify');
            return true;
        } elseif (false === is_array($codes) || empty($model)) {
            ToolBox::i18nLog()->warning('no-selected-item');
            return true;
        }

        $dataBase->beginTransaction();
        foreach ($codes as $code) {
            if (false === $model->loadFromCode($code)) {
                ToolBox::i18nLog()->error('record-not-found');
                continue;
            }

            foreach ($model->getAvaliableStatus() as $status) {
                if (empty($status->generadoc)) {
                    continue;
                }

                $model->idestado = $status->idestado;
                if ($model->save()) {
                    break;
                }

                ToolBox::i18nLog()->error('record-save-error');
                $dataBase->rollback();
                return true;
            }
        }

        ToolBox::i18nLog()->notice('record-updated-correctly');
        $dataBase->commit();
        $model->clear();
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

        ToolBox::i18nLog()->warning('no-selected-item');
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
            ToolBox::i18nLog()->warning('not-allowed-modify');
            return true;
        } elseif (false === is_array($codes) || empty($model)) {
            ToolBox::i18nLog()->warning('no-selected-item');
            return true;
        }

        $dataBase->beginTransaction();
        foreach ($codes as $code) {
            if (false === $model->loadFromCode($code)) {
                ToolBox::i18nLog()->error('record-not-found');
                continue;
            }

            foreach ($model->getAvaliableStatus() as $status) {
                if ($status->editable) {
                    continue;
                }

                $model->idestado = $status->idestado;
                if ($model->save()) {
                    break;
                }

                ToolBox::i18nLog()->error('record-save-error');
                $dataBase->rollback();
                return true;
            }
        }

        ToolBox::i18nLog()->notice('record-updated-correctly');
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
            ToolBox::i18nLog()->warning('not-allowed-modify');
            return true;
        } elseif (false === is_array($codes) || empty($model)) {
            ToolBox::i18nLog()->warning('no-selected-item');
            return true;
        }

        $dataBase->beginTransaction();
        foreach ($codes as $code) {
            if (false === $model->loadFromCode($code)) {
                ToolBox::i18nLog()->error('record-not-found');
                continue;
            }

            $model->nick = $nick;
            $model->pagado = true;
            if (false === $model->save()) {
                ToolBox::i18nLog()->error('record-save-error');
                $dataBase->rollback();
                return true;
            }
        }

        ToolBox::i18nLog()->notice('record-updated-correctly');
        $dataBase->commit();
        $model->clear();
        return true;
    }
}
