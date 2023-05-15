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
use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Dinamic\Model\Producto;

/**
 * Controller to edit a single item from the Fabricante model
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class EditFabricante extends EditController
{
    public function getModelClassName(): string
    {
        return 'Fabricante';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'warehouse';
        $data['title'] = 'manufacturer';
        $data['icon'] = 'fas fa-industry';
        return $data;
    }

    protected function addProductAction(): void
    {
        $codes = $this->request->request->get('code', []);
        if (false === is_array($codes)) {
            return;
        }

        $num = 0;
        foreach ($codes as $code) {
            $product = new Producto();
            if (false === $product->loadFromCode($code)) {
                continue;
            }

            $product->codfabricante = $this->request->query->get('code');
            if ($product->save()) {
                $num++;
            }
        }

        $this->toolBox()->i18nLog()->notice('items-added-correctly', ['%num%' => $num]);
    }

    /**
     * Load views.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->createViewProducts();
        $this->createViewNewProducts();
    }

    protected function createViewNewProducts(string $viewName = 'ListProducto-new'): void
    {
        $this->addListView($viewName, 'Producto', 'add', 'fas fa-folder-plus');
        $this->createViewProductsCommon($viewName);

        // botón añadir producto
        $this->addButton($viewName, [
            'action' => 'add-product',
            'color' => 'success',
            'icon' => 'fas fa-folder-plus',
            'label' => 'add'
        ]);
    }

    protected function createViewProducts(string $viewName = 'ListProducto'): void
    {
        $this->addListView($viewName, 'Producto', 'products', 'fas fa-cubes');
        $this->createViewProductsCommon($viewName);

        // botón quitar producto
        $this->addButton($viewName, [
            'action' => 'remove-product',
            'color' => 'danger',
            'confirm' => true,
            'icon' => 'fas fa-folder-minus',
            'label' => 'remove-from-list'
        ]);
    }

    protected function createViewProductsCommon(string $viewName): void
    {
        $this->views[$viewName]->addSearchFields(['descripcion', 'referencia']);
        $this->views[$viewName]->addOrderBy(['referencia'], 'reference', 1);
        $this->views[$viewName]->addOrderBy(['precio'], 'price');
        $this->views[$viewName]->addOrderBy(['stockfis'], 'stock');

        // filtros
        $i18n = $this->toolBox()->i18n();
        $this->views[$viewName]->addFilterSelectWhere('status', [
            ['label' => $i18n->trans('only-active'), 'where' => [new DataBaseWhere('bloqueado', false)]],
            ['label' => $i18n->trans('blocked'), 'where' => [new DataBaseWhere('bloqueado', true)]],
            ['label' => $i18n->trans('public'), 'where' => [new DataBaseWhere('publico', true)]],
            ['label' => $i18n->trans('all'), 'where' => []]
        ]);

        $families = $this->codeModel->all('familias', 'codfamilia', 'descripcion');
        $this->views[$viewName]->addFilterSelect('codfamilia', 'family', 'codfamilia', $families);

        $this->views[$viewName]->addFilterNumber('min-price', 'price', 'precio', '<=');
        $this->views[$viewName]->addFilterNumber('max-price', 'price', 'precio', '>=');
        $this->views[$viewName]->addFilterNumber('min-stock', 'stock', 'stockfis', '<=');
        $this->views[$viewName]->addFilterNumber('max-stock', 'stock', 'stockfis', '>=');

        $taxes = Impuestos::codeModel();
        $this->views[$viewName]->addFilterSelect('codimpuesto', 'tax', 'codimpuesto', $taxes);

        $this->views[$viewName]->addFilterCheckbox('nostock', 'no-stock', 'nostock');
        $this->views[$viewName]->addFilterCheckbox('ventasinstock', 'allow-sale-without-stock', 'ventasinstock');
        $this->views[$viewName]->addFilterCheckbox('secompra', 'for-purchase', 'secompra');
        $this->views[$viewName]->addFilterCheckbox('sevende', 'for-sale', 'sevende');
        $this->views[$viewName]->addFilterCheckbox('publico', 'public', 'publico');

        // ocultamos la columna fabricante y los botones de nuevo y eliminar
        $this->views[$viewName]->disableColumn('manufacturer');
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'btnDelete', false);
    }

    /**
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
     * Load data view procedure
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListProducto':
                $codfabricante = $this->getViewModelValue($this->getMainViewName(), 'codfabricante');
                $where = [new DataBaseWhere('codfabricante', $codfabricante)];
                $view->loadData('', $where);
                break;

            case 'ListProducto-new':
                $where = [new DataBaseWhere('codfabricante', null, 'IS')];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    protected function removeProductAction(): void
    {
        $codes = $this->request->request->get('code', []);
        if (false === is_array($codes)) {
            return;
        }

        $num = 0;
        foreach ($codes as $code) {
            $product = new Producto();
            if (false === $product->loadFromCode($code)) {
                continue;
            }

            $product->codfabricante = null;
            if ($product->save()) {
                $num++;
            }
        }

        $this->toolBox()->i18nLog()->notice('items-removed-correctly', ['%num%' => $num]);
    }
}
