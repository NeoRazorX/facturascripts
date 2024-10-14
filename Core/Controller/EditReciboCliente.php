<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\DataSrc\Divisas;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Accounting\PaymentToAccounting;
use FacturaScripts\Dinamic\Model\PagoCliente;

/**
 * Description of EditReciboCliente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditReciboCliente extends EditController
{
    public function getModelClassName(): string
    {
        return 'ReciboCliente';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'receipt';
        $data['icon'] = 'fa-solid fa-piggy-bank';
        return $data;
    }

    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        // desactivamos selects con una sola opción
        if (count(Empresas::all()) <= 1) {
            $this->views[$this->getMainViewName()]->disableColumn('company');
        }
        if (count(Divisas::all()) <= 1) {
            $this->views[$this->getMainViewName()]->disableColumn('currency');
        }

        // desactivamos el botón nuevo
        $this->setSettings($this->getMainViewName(), 'btnNew', false);

        $this->createViewPayments();
    }

    protected function createViewPayments($viewName = 'ListPagoCliente'): void
    {
        $this->addListView($viewName, 'PagoCliente', 'payments');
        $this->views[$viewName]->addOrderBy(['fecha', 'hora'], 'date', 1);

        // desactivamos el botón nuevo
        $this->setSettings($viewName, 'btnNew', false);

        // añadimos el botón de generar asiento
        $this->addButton($viewName, [
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
            $pago = new PagoCliente();
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
            case 'ListPagoCliente':
                $id = $this->getViewModelValue('EditReciboCliente', 'idrecibo');
                $where = [new DataBaseWhere('idrecibo', $id)];
                $this->views[$viewName]->loadData('', $where);
                break;

            case 'EditReciboCliente':
                parent::loadData($viewName, $view);
                $this->views[$viewName]->model->nick = $this->user->nick;
                if ($this->views[$viewName]->model->pagado) {
                    $this->views[$viewName]->disableColumn('amount', false, 'true');
                    $this->views[$viewName]->disableColumn('expenses', false, 'true');
                    $this->views[$viewName]->disableColumn('payment', false, 'true');
                }
                break;
        }
    }
}
