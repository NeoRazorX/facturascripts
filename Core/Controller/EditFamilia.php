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

use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Producto;

/**
 * Controlador para editar un único elemento del modelo Familia
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Fco. Antonio Moreno Pérez     <famphuelva@gmail.com>
 */
class EditFamilia extends EditController
{
    public function getModelClassName(): string
    {
        return 'Familia';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'warehouse';
        $data['title'] = 'family';
        $data['icon'] = 'fa-solid fa-sitemap';
        return $data;
    }

    protected function addProductAction(): void
    {
        $codes = $this->request->request->getArray('codes');
        if (false === is_array($codes)) {
            return;
        }

        $num = 0;
        foreach ($codes as $code) {
            $product = new Producto();
            if (false === $product->loadFromCode($code)) {
                continue;
            }

            $product->codfamilia = $this->request->query('code');
            if ($product->save()) {
                $num++;
            }
        }

        Tools::log()->notice('items-added-correctly', ['%num%' => $num]);
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        // más pestañas
        $this->createViewProducts();
        $this->createViewNewProducts();
        $this->createViewFamilies();
    }

    protected function createViewFamilies(string $viewName = 'ListFamilia'): void
    {
        $this->addListView($viewName, 'Familia', 'subfamilies', 'fa-solid fa-sitemap')
            ->addOrderBy(['codfamilia'], 'code')
            // desactivamos la columna de familia padre y el botón de eliminar
            ->disableColumn('parent')
            ->setSettings('btnDelete', false);
    }

    protected function createViewNewProducts(string $viewName = 'ListProducto-new'): void
    {
        $this->addListView($viewName, 'Producto', 'add', 'fa-solid fa-folder-plus');
        $this->createViewProductsCommon($viewName);

        // botón añadir producto
        $this->tab($viewName)->addButton([
            'action' => 'add-product',
            'color' => 'success',
            'icon' => 'fa-solid fa-folder-plus',
            'label' => 'add'
        ]);
    }

    protected function createViewProducts(string $viewName = 'ListProducto'): void
    {
        $this->addListView($viewName, 'Producto', 'products', 'fa-solid fa-cubes');
        $this->createViewProductsCommon($viewName);

        // botón quitar producto
        $this->tab($viewName)->addButton([
            'action' => 'remove-product',
            'color' => 'danger',
            'confirm' => true,
            'icon' => 'fa-solid fa-folder-minus',
            'label' => 'remove-from-list'
        ]);
    }

    protected function createViewProductsCommon(string $viewName): void
    {
        $i18n = Tools::lang();
        $manufacturers = $this->codeModel->all('fabricantes', 'codfabricante', 'nombre');
        $taxes = Impuestos::codeModel();

        $this->listView($viewName)
            ->addSearchFields(['descripcion', 'referencia'])
            ->addOrderBy(['referencia'], 'reference', 1)
            ->addOrderBy(['precio'], 'price')
            ->addOrderBy(['stockfis'], 'stock')
            // filtros
            ->addFilterSelectWhere('status', [
                ['label' => $i18n->trans('only-active'), 'where' => [Where::eq('bloqueado', false)]],
                ['label' => $i18n->trans('blocked'), 'where' => [Where::eq('bloqueado', true)]],
                ['label' => $i18n->trans('public'), 'where' => [Where::eq('publico', true)]],
                ['label' => $i18n->trans('all'), 'where' => []]
            ])
            ->addFilterSelect('codfabricante', 'manufacturer', 'codfabricante', $manufacturers)
            ->addFilterNumber('min-price', 'price', 'precio', '<=')
            ->addFilterNumber('max-price', 'price', 'precio', '>=')
            ->addFilterNumber('min-stock', 'stock', 'stockfis', '<=')
            ->addFilterNumber('max-stock', 'stock', 'stockfis', '>=')
            ->addFilterSelect('codimpuesto', 'tax', 'codimpuesto', $taxes)
            ->addFilterCheckbox('nostock', 'no-stock', 'nostock')
            ->addFilterCheckbox('ventasinstock', 'allow-sale-without-stock', 'ventasinstock')
            ->addFilterCheckbox('secompra', 'for-purchase', 'secompra')
            ->addFilterCheckbox('sevende', 'for-sale', 'sevende')
            ->addFilterCheckbox('publico', 'public', 'publico')
            // desactivamos la columna familia y los botones de nuevo y eliminar
            ->disableColumn('family')
            ->setSettings('btnNew', false)
            ->setSettings('btnDelete', false);
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
     * Load view data procedure
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $codfamilia = $this->mainTabModelValue('codfamilia');
        switch ($viewName) {
            case 'ListProducto':
                $where = [Where::eq('codfamilia', $codfamilia)];
                $view->loadData('', $where);
                break;

            case 'ListProducto-new':
                $where = [Where::isNull('codfamilia')];
                $view->loadData('', $where);
                break;

            case 'ListFamilia':
                $where = [Where::eq('madre', $codfamilia)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    protected function removeProductAction(): void
    {
        $codes = $this->request->request->getArray('codes');
        if (false === is_array($codes)) {
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

        Tools::log()->notice('items-removed-correctly', ['%num%' => $num]);
    }
}
