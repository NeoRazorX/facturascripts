<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\DataSrc\Divisas;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\Accounting\PaymentToAccounting;
use FacturaScripts\Dinamic\Model\PagoProveedor;

/**
 * Controlador para editar un único elemento del modelo ReciboProveedor
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditReciboProveedor extends EditController
{
    public function getModelClassName(): string
    {
        return 'ReciboProveedor';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'purchases';
        $data['title'] = 'receipt';
        $data['icon'] = 'fa-solid fa-piggy-bank';
        return $data;
    }

    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        // desactivamos selects con una sola opción
        if (Empresas::count() <= 1) {
            $this->mainTab()->disableColumn('company');
        }
        if (Divisas::count() <= 1) {
            $this->mainTab()->disableColumn('currency');
        }

        // desactivamos el botón nuevo
        $this->setSettings($this->mainTabName(), 'btnNew', false);

        $this->createViewPayments();
    }

    protected function createViewPayments($viewName = 'ListPagoProveedor'): void
    {
        $this->addListView($viewName, 'PagoProveedor', 'payments')
            ->addOrderBy(['fecha', 'hora'], 'date', 1)
            // desactivamos el botón nuevo
            ->setSettings('btnNew', false);

        // añadimos el botón de generar asiento
        $this->tab($viewName)->addButton([
            'action' => 'generate-accounting',
            'icon' => 'fa-solid fa-wand-magic-sparkles',
            'label' => 'generate-accounting-entry'
        ]);
    }

    protected function generateAccountingAction(): void
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return;
        } elseif (false === $this->validateFormToken()) {
            return;
        }

        $codes = $this->request->request->getArray('codes');
        if (empty($codes) || false === is_array($codes)) {
            Tools::log()->warning('no-selected-item');
            return;
        }

        foreach ($codes as $code) {
            $pago = new PagoProveedor();
            if (false === $pago->loadFromCode($code)) {
                Tools::log()->warning('record-not-found');
                continue;
            } elseif ($pago->idasiento) {
                Tools::log()->warning('record-already-exists');
                continue;
            }

            $tool = new PaymentToAccounting();
            $tool->generate($pago);
            if (empty($pago->idasiento) || false === $pago->save()) {
                Tools::log()->error('record-save-error');
                return;
            }
        }

        Tools::log()->notice('record-updated-correctly');
    }

    protected function execPreviousAction($action): bool
    {
        if ($action === 'generate-accounting') {
            $this->generateAccountingAction();
            return true;
        }

        return parent::execPreviousAction($action);
    }

    /**
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListPagoProveedor':
                $id = $this->tabModelValue('EditReciboProveedor', 'idrecibo');
                $where = [Where::eq('idrecibo', $id)];
                $view->loadData('', $where);
                break;

            case 'EditReciboProveedor':
                parent::loadData($viewName, $view);
                $view->model->nick = $this->user->nick;
                if ($view->model->pagado) {
                    $view->disableColumn('amount', false, 'true');
                    $view->disableColumn('payment', false, 'true');
                }
                break;
        }
    }
}
