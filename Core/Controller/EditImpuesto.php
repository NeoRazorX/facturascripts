<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->createViewsZones();
        $this->createViewsProducts();
        $this->createViewsAccounts();
    }

    protected function createViewsAccounts(string $viewName = 'ListSubcuenta'): void
    {
        $this->addListView($viewName, 'Subcuenta', 'subaccounts', 'fa-solid fa-folder-open');
        $this->views[$viewName]->addOrderBy(['codejercicio', 'codsubcuenta'], 'code', 2);
        $this->views[$viewName]->addOrderBy(['codejercicio', 'descripcion'], 'description');
        $this->views[$viewName]->addOrderBy(['saldo'], 'balance');
        $this->views[$viewName]->addSearchFields(['codsubcuenta', 'descripcion']);

        // desactivamos los botones de nuevo y eliminar
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'btnDelete', false);
    }

    protected function createViewsProducts(string $viewName = 'ListProducto'): void
    {
        $this->addListView($viewName, 'Producto', 'products', 'fa-solid fa-cubes');
        $this->views[$viewName]->addOrderBy(['referencia'], 'reference', 1);
        $this->views[$viewName]->addOrderBy(['precio'], 'price');
        $this->views[$viewName]->addOrderBy(['stockfis'], 'stock');
        $this->views[$viewName]->addSearchFields(['referencia', 'descripcion', 'observaciones']);

        // desactivamos los botones de nuevo y eliminar
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'btnDelete', false);
    }

    protected function createViewsZones(string $viewName = 'EditImpuestoZona'): void
    {
        $this->addEditListView($viewName, 'ImpuestoZona', 'exceptions', 'fa-solid fa-globe-americas');
        $this->views[$viewName]->disableColumn('tax');
        $this->views[$viewName]->setInLine(true);
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
                break;
        }
    }
}
