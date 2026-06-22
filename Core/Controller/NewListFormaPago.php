<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Component\ComponentCheckbox;
use FacturaScripts\Core\Component\ComponentNumber;
use FacturaScripts\Core\Component\ComponentSelect;
use FacturaScripts\Core\Component\ComponentText;
use FacturaScripts\Core\Model\Empresa;
use FacturaScripts\Core\UIComponents\UIListController;

/**
 * Listado de formas de pago construido sobre UIListController.
 *
 * Replica el comportamiento de ListFormaPago con dos pestañas:
 *  - ListFormaPago: formas de pago (FormaPago)
 *  - ListCuentaBanco: cuentas bancarias (CuentaBanco)
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class NewListFormaPago extends UIListController
{
    public function getModelClassName(): string
    {
        return 'FormaPago';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu']  = 'accounting';
        $data['title'] = 'new-forma-pago';
        $data['icon']  = 'fa-solid fa-credit-card';
        return $data;
    }

    protected function createUI(): void
    {
        $this->createViewsPaymentMethods();
        $this->createViewsBankAccounts();
    }

    protected function createViewsPaymentMethods(string $tabName = 'ListFormaPago'): void
    {
        $tab = $this->addTab($tabName, 'FormaPago', 'payment-methods', 'fa-solid fa-credit-card');

        $tab->addColumn(ComponentText::make('codpago')->setLabel('code')->setCols(2));
        $tab->addColumn(ComponentText::make('descripcion')->setLabel('description')->setCols(5));
        $tab->addColumn(ComponentNumber::make('plazovencimiento')->setLabel('expiration')->setDecimals(0)->setCols(2));
        $tab->addColumn(
            ComponentSelect::make('tipovencimiento')
                ->setLabel('expiration-type')
                ->setValuesFromArrayKeys(['days' => 'days', 'weeks' => 'weeks', 'months' => 'months', 'years' => 'years'], true)
                ->setCols(2)
        );
        $tab->addColumn(ComponentCheckbox::make('activa')->setLabel('active'));
        $tab->addColumn(ComponentCheckbox::make('pagado')->setLabel('paid'));
        $tab->addColumn(ComponentCheckbox::make('domiciliado')->setLabel('domiciled'));

        $tab->addSearchField('codpago', 'descripcion');

        $tab->addOrderBy(['codpago'], 'code', 1);
        $tab->addOrderBy(['descripcion'], 'description');

        $tab->addColor('activa', false, 'table-warning', 'inactive');

        $tab->setNewUrl('NewEditFormaPago');
        $tab->setRowUrlCallback(fn($record) => 'NewEditFormaPago?code=' . urlencode($record->codpago));

        $empresas = (new Empresa())->all();
        if (count($empresas) > 1) {
            $options = array_map(fn($e) => ['value' => $e->idempresa, 'title' => $e->nombrecorto], $empresas);
            $tab->addFilterSelect('idempresa', 'company', 'idempresa', $options);
        }
        $tab->addFilterCheckbox('pagado', 'paid', 'pagado');
        $tab->addFilterCheckbox('domiciliado', 'domiciled', 'domiciliado');
    }

    protected function createViewsBankAccounts(string $tabName = 'ListCuentaBanco'): void
    {
        $tab = $this->addTab($tabName, 'CuentaBanco', 'bank-accounts', 'fa-solid fa-piggy-bank');

        $tab->addColumn(ComponentText::make('codcuenta')->setLabel('code')->setCols(2));
        $tab->addColumn(ComponentText::make('descripcion')->setLabel('description')->setCols(6));
        $tab->addColumn(ComponentText::make('swift')->setLabel('swift')->setCols(2));
        $tab->addColumn(ComponentCheckbox::make('activa')->setLabel('active'));

        $tab->addSearchField('descripcion', 'codcuenta');

        $tab->addOrderBy(['codcuenta'], 'code', 1);
        $tab->addOrderBy(['descripcion'], 'description');

        $tab->setNewUrl('NewEditCuentaBanco');
        $tab->setRowUrlCallback(fn($record) => 'NewEditCuentaBanco?code=' . urlencode($record->codcuenta));
    }
}
