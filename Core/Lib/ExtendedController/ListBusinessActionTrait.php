<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ExtendedController\BaseView;

/**
 * Contains common utilities for grouping and collecting documents.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
trait ListBusinessActionTrait
{

    /**
     * Add buttons for approve document.
     *
     * @param string $viewName
     */
    protected function addButtonApproveDocument($viewName)
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
     * Add button for group document.
     *
     * @param string $viewName
     */
    protected function addButtonGroupDocument($viewName)
    {
        $this->addButton($viewName, [
            'action' => 'group-document',
            'icon' => 'fas fa-magic',
            'label' => 'group-or-split'
        ]);
    }

    /**
     * Add button for lock invoice document.
     *
     * @param string $viewName
     */
    protected function addButtonLockInvoice($viewName)
    {
        $this->addButton($viewName, [
            'action' => 'lock-invoice',
            'confirm' => 'true',
            'icon' => 'fas fa-lock fa-fw',
            'label' => 'lock-invoice'
        ]);
    }

    /**
     * Add button for pay receipt.
     *
     * @param string $viewName
     */
    protected function addButtonReceiptPay($viewName)
    {
        $this->addButton($viewName, [
            'action' => 'paid',
            'confirm' => 'true',
            'icon' => 'fas fa-check',
            'label' => 'pay',
            'type' => 'action'
        ]);
    }

    /**
     *
     * @return bool
     */
    protected function approveDocumentAction()
    {
        $codes = null;
        $model = null;
        if (false === $this->checkAndInit($codes, $model)) {
            return true;
        }

        $this->dataBase->beginTransaction();
        foreach ($codes as $code) {
            if (false === $model->loadFromCode($code)) {
                $this->toolBox()->i18nLog()->error('record-not-found');
                continue;
            }

            foreach ($model->getAvaliableStatus() as $status) {
                if (empty($status->generadoc)) {
                    continue;
                }

                $model->idestado = $status->idestado;
                if (false === $model->save()) {
                    $this->toolBox()->i18nLog()->error('record-save-error');
                    $this->dataBase->rollback();
                    return true;
                }
            }
        }

        $this->toolBox()->i18nLog()->notice('record-updated-correctly');
        $this->dataBase->commit();
        $model->clear();
        return true;
    }

    /**
     * Send the selected codes to the DocumentStitcher controller.
     *
     * @return bool
     */
    protected function groupDocumentAction()
    {
        $codes = $this->request->request->get('code');
        $model = $this->views[$this->active]->model;

        if (!empty($codes) && $model) {
            $codes = \implode(',', $codes);
            $url = 'DocumentStitcher?model=' . $model->modelClassName() . '&codes=' . $codes;
            $this->redirect($url);
            return false;
        }

        $this->toolBox()->i18nLog()->warning('no-selected-item');
        return true;
    }

    /**
     * Lock invoice document list.
     *
     * @return bool
     */
    protected function lockInvoiceAction()
    {
        $codes = null;
        $model = null;
        if (false === $this->checkAndInit($codes, $model)) {
            return true;
        }

        $this->dataBase->beginTransaction();
        foreach ($codes as $code) {
            if (false === $model->loadFromCode($code)) {
                $this->toolBox()->i18nLog()->error('record-not-found');
                continue;
            }

            foreach ($model->getAvaliableStatus() as $status) {
                if ($status->editable) {
                    continue;
                }

                $model->idestado = $status->idestado;
                if (false === $model->save()) {
                    $this->toolBox()->i18nLog()->error('record-save-error');
                    $this->dataBase->rollback();
                    return true;
                }
            }
        }

        $this->toolBox()->i18nLog()->notice('record-updated-correctly');
        $this->dataBase->commit();
        $model->clear();
        return true;
    }

    /**
     * Payment receipt process.
     *
     * @return bool
     */
    protected function paidAction()
    {
        $codes = null;
        $model = null;
        if (false === $this->checkAndInit($codes, $model)) {
            return true;
        }

        $this->dataBase->beginTransaction();
        foreach ($codes as $code) {
            if (false === $model->loadFromCode($code)) {
                $this->toolBox()->i18nLog()->error('record-not-found');
                continue;
            }

            $model->nick = $this->user->nick;
            $model->pagado = true;
            if (false === $model->save()) {
                $this->toolBox()->i18nLog()->error('record-save-error');
                $this->dataBase->rollback();
                return true;
            }
        }

        $this->toolBox()->i18nLog()->notice('record-updated-correctly');
        $this->dataBase->commit();
        $model->clear();
        return true;
    }

    /**
     *
     * @param array $codes
     * @param BaseView $model
     * @return bool
     */
    private function checkAndInit(&$codes, &$model): bool
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return false;
        }

        $codes = $this->request->request->get('code');
        $model = $this->views[$this->active]->model;
        if (false === \is_array($codes) || empty($model)) {
            $this->toolBox()->i18nLog()->warning('no-selected-item');
            return false;
        }

        return true;
    }
}
