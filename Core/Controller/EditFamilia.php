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
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Dinamic\Model\Producto;

/**
 * Controller to edit a single item from the Familia model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Artex Trading sa             <jcuello@artextrading.com>
 * @author Fco. Antonio Moreno Pérez    <famphuelva@gmail.com>
 */
class EditFamilia extends EditController
{

    /**
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'Familia';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'warehouse';
        $data['title'] = 'family';
        $data['icon'] = 'fas fa-sitemap';
        return $data;
    }

    protected function addProductAction()
    {
        $codes = $this->request->request->get('code', []);
        if (false === \is_array($codes)) {
            return;
        }

        $num = 0;
        foreach ($codes as $code) {
            $product = new Producto();
            if (false === $product->loadFromCode($code)) {
                continue;
            }

            $product->codfamilia = $this->request->query->get('code');
            if ($product->save()) {
                $num++;
            }
        }

        $this->toolBox()->i18nLog()->notice('items-added-correctly', ['%num%' => $num]);
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        /// more tabs
        $this->createViewProducts();
        $this->createViewNewProducts();
        $this->createViewFamilies();
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewFamilies(string $viewName = 'ListFamilia')
    {
        $this->addListView($viewName, 'Familia', 'subfamilies', 'fas fa-sitemap');
        $this->views[$viewName]->addOrderBy(['codfamilia'], 'code');

        /// disable column
        $this->views[$viewName]->disableColumn('parent');

        /// disable button
        $this->setSettings($viewName, 'btnDelete', false);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewNewProducts(string $viewName = 'ListProducto-new')
    {
        $this->addListView($viewName, 'Producto', 'add', 'fas fa-folder-plus');
        $this->createViewProductsCommon($viewName);

        /// add action button
        $this->addButton($viewName, [
            'action' => 'add-product',
            'color' => 'success',
            'icon' => 'fas fa-folder-plus',
            'label' => 'add'
        ]);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewProducts(string $viewName = 'ListProducto')
    {
        $this->addListView($viewName, 'Producto', 'products', 'fas fa-cubes');
        $this->createViewProductsCommon($viewName);

        /// add action button
        $this->addButton($viewName, [
            'action' => 'remove-product',
            'color' => 'danger',
            'confirm' => true,
            'icon' => 'fas fa-folder-minus',
            'label' => 'remove-from-list'
        ]);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewProductsCommon(string $viewName)
    {
        $this->views[$viewName]->addOrderBy(['referencia'], 'reference', 1);
        $this->views[$viewName]->addOrderBy(['precio'], 'price');
        $this->views[$viewName]->addOrderBy(['stockfis'], 'stock');
        $this->views[$viewName]->searchFields = ['referencia', 'descripcion'];

        /// disable columns and buttons
        $this->views[$viewName]->disableColumn('family');
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'btnDelete', false);
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
            case 'add-product':
                $this->addProductAction();
                return true;

            case 'remove-product':
                $this->removeProductAction();
                return true;

            default:
                return parent::execPreviousAction($action);
        }
    }

    /**
     * Load view data procedure
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $codfamilia = $this->getViewModelValue($this->getMainViewName(), 'codfamilia');
        switch ($viewName) {
            case 'ListProducto':
                $where = [new DataBaseWhere('codfamilia', $codfamilia)];
                $view->loadData('', $where);
                break;

            case 'ListProducto-new':
                $where = [new DataBaseWhere('codfamilia', null, 'IS')];
                $view->loadData('', $where);
                break;

            case 'ListFamilia':
                $where = [new DataBaseWhere('madre', $codfamilia)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    protected function removeProductAction()
    {
        $codes = $this->request->request->get('code', []);
        if (false === \is_array($codes)) {
            return;
        }

        $num = 0;
        foreach ($codes as $code) {
            $product = new Producto();
            if (false === $product->loadFromCode($code)) {
                continue;
            }

            $product->codfamilia = null;
            if ($product->save()) {
                $num++;
            }
        }

        $this->toolBox()->i18nLog()->notice('items-removed-correctly', ['%num%' => $num]);
    }
}
