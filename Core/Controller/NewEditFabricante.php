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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Component\ActionResult;
use FacturaScripts\Core\Component\ComponentNumber;
use FacturaScripts\Core\Component\ComponentText;
use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\Model\CodeModel;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\UIComponents\UIEditController;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Producto;

/**
 * Formulario de edición de fabricantes construido sobre UIEditController.
 *
 * Replica EditFabricante con:
 *  - Formulario del fabricante (nombre, codfabricante, numproductos)
 *  - Lista de productos asignados con botón remove-product
 *  - Lista de productos sin fabricante para añadir con botón add-product
 *  - Filtros completos en ambas listas (estado, familia, precio, stock, impuesto...)
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class NewEditFabricante extends UIEditController
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
        $data['icon'] = 'fa-solid fa-industry';
        return $data;
    }

    public function listUrl(): string
    {
        return 'NewListFabricante';
    }

    protected function getViewName(): string
    {
        return 'EditFabricante';
    }

    protected function buildForm(): void
    {
        $this->loadModel();

        $this->startGroup('data');

        $this->addComponent(
            ComponentText::make('nombre')
                ->setLabel('name')
                ->setRequired()
                ->setMaxLength(100)
        );

        $this->addComponent(
            ComponentText::make('codfabricante')
                ->setLabel('code')
                ->setIcon('fa-solid fa-hashtag')
                ->setMaxLength(8)
                ->setReadOnlyDynamic()
                ->setCols(2)
        );

        $this->addComponent(
            ComponentNumber::make('numproductos')
                ->setLabel('products')
                ->setReadOnly()
                ->setDecimals(0)
                ->setCols(2)
        );

        $this->buildProductViews();

        $this->onEvent('add-product', fn() => $this->addProductAction());
        $this->onEvent('remove-product', fn() => $this->removeProductAction());
    }

    protected function modifyUI(): void
    {
        parent::modifyUI();

        $model = $this->editModel;
        if ($model === null || !$model->exists()) {
            foreach ($this->listViews() as $view) {
                $view->settings['active'] = false;
            }
            return;
        }

        $code = $model->codfabricante ?? '';

        $listAssigned = $this->listView('ListProducto');
        if ($listAssigned !== null) {
            $listAssigned->processFormData($this->request, 'load');
            $listAssigned->loadData('', [Where::eq('codfabricante', $code)]);
            $listAssigned->disableColumn('manufacturer');
        }

        $listNew = $this->listView('ListProducto-new');
        if ($listNew !== null) {
            $listNew->processFormData($this->request, 'load');
            $listNew->loadData('', [new DataBaseWhere('codfabricante', null, 'IS')]);
            $listNew->disableColumn('manufacturer');
        }
    }

    private function buildProductViews(): void
    {
        $i18n = Tools::lang();
        $families = CodeModel::all('familias', 'codfamilia', 'descripcion');
        $taxes = Impuestos::codeModel();

        $statusFilter = [
            ['label' => $i18n->trans('only-active'), 'where' => [new DataBaseWhere('bloqueado', false)]],
            ['label' => $i18n->trans('blocked'),     'where' => [new DataBaseWhere('bloqueado', true)]],
            ['label' => $i18n->trans('public'),      'where' => [new DataBaseWhere('publico', true)]],
            ['label' => $i18n->trans('all'),         'where' => []],
        ];

        // productos asignados a este fabricante
        $listAssigned = $this->addListView('ListProducto', 'Producto', 'products', 'fa-solid fa-cubes');
        $listAssigned->addSearchFields(['descripcion', 'referencia'])
            ->addOrderBy(['referencia'], 'reference', 1)
            ->addOrderBy(['precio'], 'price')
            ->addOrderBy(['stockfis'], 'stock')
            ->addFilterSelectWhere('status', $statusFilter)
            ->addFilterSelect('codfamilia', 'family', 'codfamilia', $families)
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
            ->setSettings('btnNew', false)
            ->setSettings('btnDelete', false);

        $listAssigned->addButton([
            'action'  => 'remove-product',
            'color'   => 'danger',
            'confirm' => true,
            'icon'    => 'fa-solid fa-folder-minus',
            'label'   => 'remove-from-list',
        ]);

        // productos sin fabricante (para añadir a este)
        $listNew = $this->addListView('ListProducto-new', 'Producto', 'add', 'fa-solid fa-folder-plus');
        $listNew->addSearchFields(['descripcion', 'referencia'])
            ->addOrderBy(['referencia'], 'reference', 1)
            ->addOrderBy(['precio'], 'price')
            ->addOrderBy(['stockfis'], 'stock')
            ->addFilterSelectWhere('status', $statusFilter)
            ->addFilterSelect('codfamilia', 'family', 'codfamilia', $families)
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
            ->setSettings('btnNew', false)
            ->setSettings('btnDelete', false);

        $listNew->addButton([
            'action' => 'add-product',
            'color'  => 'success',
            'icon'   => 'fa-solid fa-folder-plus',
            'label'  => 'add',
        ]);
    }

    private function addProductAction(): ActionResult
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-update');
            return ActionResult::make();
        }

        if (false === $this->validateFormToken()) {
            return ActionResult::make();
        }

        $num = 0;
        $codfabricante = $this->request->query('code');
        $codes = $this->request->request->getArray('codes', false);

        foreach ($codes as $code) {
            $product = new Producto();
            if (false === $product->loadFromCode($code)) {
                continue;
            }
            $product->codfabricante = $codfabricante;
            if ($product->save()) {
                $num++;
            }
        }

        Tools::log()->notice('items-added-correctly', ['%num%' => $num]);
        return ActionResult::make();
    }

    private function removeProductAction(): ActionResult
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-update');
            return ActionResult::make();
        }

        if (false === $this->validateFormToken()) {
            return ActionResult::make();
        }

        $num = 0;
        $codes = $this->request->request->getArray('codes', false);

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

        Tools::log()->notice('items-removed-correctly', ['%num%' => $num]);
        return ActionResult::make();
    }
}
