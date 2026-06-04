<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Lib\OperacionIVA;
use FacturaScripts\Dinamic\Lib\TaxExceptions;

/**
 * Controller to edit a single item from the Impuesto model
 *
 * @author Carlos García Gómez              <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal    <yopli2000@gmail.com>
 * @author Francesc Pineda Segarra          <francesc.pineda.segarra@gmail.com>
 */
class EditImpuesto extends EditController
{
    public function getModelClassName(): string
    {
        return 'Impuesto';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'tax';
        $data['icon'] = 'fa-solid fa-plus-square';
        return $data;
    }

    /**
     * Create the view to display.
     */
    protected function createViews(): void
    {
        parent::createViews();

        $this->setTabsPosition('bottom');

        $this->createViewsZones();
        $this->createViewsProducts();
        $this->createViewsAccounts();
    }

    protected function createViewsAccounts(string $viewName = 'ListSubcuenta'): void
    {
        $this->addListView($viewName, 'Subcuenta', 'subaccounts', 'fa-solid fa-folder-open')
            ->addOrderBy(['codejercicio', 'codsubcuenta'], 'code', 2)
            ->addOrderBy(['codejercicio', 'descripcion'], 'description')
            ->addOrderBy(['saldo'], 'balance')
            ->addSearchFields(['codsubcuenta', 'descripcion'])
            ->setSettings('btnNew', false)
            ->setSettings('btnDelete', false);
    }

    protected function createViewsProducts(string $viewName = 'ListProducto'): void
    {
        $this->addListView($viewName, 'Producto', 'products', 'fa-solid fa-cubes')
            ->addOrderBy(['referencia'], 'reference', 1)
            ->addOrderBy(['precio'], 'price')
            ->addOrderBy(['stockfis'], 'stock')
            ->addSearchFields(['referencia', 'descripcion', 'observaciones'])
            ->setSettings('btnNew', false)
            ->setSettings('btnDelete', false);
    }

    protected function createViewsZones(string $viewName = 'EditImpuestoZona'): void
    {
        $this->addEditListView($viewName, 'ImpuestoZona', 'zones', 'fa-solid fa-globe-americas')
            ->setInLine(true)
            ->disableColumn('tax');
    }

    /**
     * Loads the data to display.
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mvn = $this->getMainViewName();
        $code = $this->getViewModelValue($mvn, 'codimpuesto');

        switch ($viewName) {
            case 'EditImpuestoZona':
                $where = [new DataBaseWhere('codimpuesto', $code)];
                $view->loadData('', $where, ['prioridad' => 'DESC']);
                $this->loadVatExceptions($viewName);
                break;

            case 'ListProducto':
                $where = [new DataBaseWhere('codimpuesto', $code)];
                $view->loadData('', $where);
                break;

            case 'ListSubcuenta':
                // cargamos la lista de subcuentas del impuesto
                $codes = [];
                foreach (['codsubcuentarep', 'codsubcuentarepre', 'codsubcuentasop', 'codsubcuentasopre'] as $field) {
                    if ($this->getViewModelValue($mvn, $field)) {
                        $codes[] = $this->getViewModelValue($mvn, $field);
                    }
                }
                if (empty($codes)) {
                    // no hay ninguna cuenta, desactivamos la pestaña
                    $view->settings['active'] = false;
                    break;
                }
                $where = [new DataBaseWhere('codsubcuenta', implode(',', $codes), 'IN')];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                $this->loadOperations($viewName);
                break;
        }
    }

    protected function loadVatExceptions(string $viewName): void
    {
        $column = $this->views[$viewName]->columnForName('vat-exception');
        if ($column && $column->widget->getType() === 'select') {
            $column->widget->setValuesFromArrayKeys(TaxExceptions::all(), true, true);
        }
    }

    protected function loadOperations(string $viewName): void
    {
        $column = $this->views[$viewName]->columnForName('operation');
        if ($column && $column->widget->getType() === 'select') {
            $column->widget->setValuesFromArrayKeys(OperacionIVA::all(), true, true);
        }
    }
}
