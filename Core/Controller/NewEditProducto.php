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
use FacturaScripts\Core\Component\ComponentDate;
use FacturaScripts\Core\Component\ComponentNumber;
use FacturaScripts\Core\Component\ComponentSelect;
use FacturaScripts\Core\Component\ComponentText;
use FacturaScripts\Core\Component\ComponentTextarea;
use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\DocFilesTrait;
use FacturaScripts\Core\Lib\ExtendedController\ProductImagesTrait;
use FacturaScripts\Core\Lib\ProductType;
use FacturaScripts\Core\Lib\TaxExceptions;
use FacturaScripts\Core\Model\CodeModel;
use FacturaScripts\Core\Model\ProductoImagen;
use FacturaScripts\Core\UIComponents\UIEditController;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Atributo;

/**
 * Formulario de edición de productos construido sobre UIEditController.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class NewEditProducto extends UIEditController
{
    use DocFilesTrait;
    use ProductImagesTrait;

    public function getModelClassName(): string
    {
        return 'Producto';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'warehouse';
        $data['title'] = 'product';
        $data['icon'] = 'fa-solid fa-cube';
        return $data;
    }

    public function listUrl(): string
    {
        return 'NewListProducto';
    }

    protected function getViewName(): string
    {
        return 'EditProducto';
    }

    protected function buildForm(): void
    {
        $this->loadModel();

        CodeModel::setLimit(9999);

        // Grupo principal: identificación y clasificación
        $this->startGroup('main');

        $this->addComponent(
            ComponentText::make('referencia')
                ->setLabel('reference')
                ->setIcon('fa-solid fa-hashtag')
                ->setMaxLength(30)
                ->setReadOnlyDynamic()
                ->setCols(3)
        );

        $manufacturerOpts = [['value' => '', 'title' => '------', 'group' => '']];
        foreach (CodeModel::all('fabricantes', 'codfabricante', 'nombre', false) as $c) {
            $manufacturerOpts[] = ['value' => $c->code, 'title' => $c->description, 'group' => ''];
        }
        $this->addComponent(
            ComponentSelect::make('codfabricante')
                ->setLabel('manufacturer')
                ->setLabelUrl('ListFabricante')
                ->setSource('fabricantes', 'codfabricante', 'nombre')
                ->setOptionsResolver(fn() => $manufacturerOpts)
        );

        $familyOpts = [['value' => '', 'title' => '------', 'group' => '']];
        foreach (CodeModel::all('familias', 'codfamilia', 'descripcion', false) as $c) {
            $familyOpts[] = ['value' => $c->code, 'title' => $c->description, 'group' => ''];
        }
        $this->addComponent(
            ComponentSelect::make('codfamilia')
                ->setLabel('family')
                ->setLabelUrl('ListFamilia')
                ->setSource('familias', 'codfamilia', 'descripcion')
                ->setOptionsResolver(fn() => $familyOpts)
        );

        $taxOpts = [['value' => '', 'title' => '------', 'group' => '']];
        foreach (Impuestos::codeModel(false) as $c) {
            $taxOpts[] = ['value' => $c->code, 'title' => $c->description, 'group' => ''];
        }
        $this->addComponent(
            ComponentSelect::make('codimpuesto')
                ->setLabel('tax')
                ->setLabelUrl('ListImpuesto')
                ->setSource('impuestos', 'codimpuesto', 'descripcion')
                ->setOptionsResolver(fn() => $taxOpts)
        );

        $this->addComponent(
            ComponentSelect::make('excepcioniva')
                ->setLabel('vat-exception')
                ->setValuesFromArrayKeys(TaxExceptions::all(), false, true)
        );

        // Grupo descripción
        $this->startGroup('description');

        $this->addComponent(
            ComponentTextarea::make('descripcion')
                ->setLabel('description')
                ->setRequired()
                ->setCols(12)
        );

        $this->addComponent(
            ComponentTextarea::make('observaciones')
                ->setLabel('observations')
                ->setCols(12)
        );

        // Grupo opciones: flags booleanos alineados al fondo
        $this->startGroup('options', alignBottom: true);

        $this->addComponent(ComponentCheckbox::make('nostock')->setLabel('no-stock'));
        $this->addComponent(ComponentCheckbox::make('secompra')->setLabel('for-purchase'));
        $this->addComponent(ComponentCheckbox::make('sevende')->setLabel('for-sale'));
        $this->addComponent(ComponentCheckbox::make('ventasinstock')->setLabel('allow-sale-without-stock'));
        $this->addComponent(ComponentCheckbox::make('bloqueado')->setLabel('blocked'));
        $this->addComponent(ComponentCheckbox::make('publico')->setLabel('public'));

        // Grupo avanzado: datos técnicos y fechas
        $this->startGroup('advanced');

        $this->addComponent(
            ComponentNumber::make('stockfis')
                ->setLabel('stock')
                ->setReadOnly()
                ->setDecimals(2)
                ->setCols(2)
        );

        $this->addComponent(
            ComponentSelect::make('tipo')
                ->setLabel('type')
                ->setValuesFromArrayKeys(ProductType::all(), true, true)
                ->setCols(2)
        );

        $this->addComponent(
            ComponentDate::make('fechaalta')
                ->setLabel('creation-date')
                ->setReadOnly()
                ->setCols(2)
        );

        $this->addComponent(
            ComponentDate::make('actualizado')
                ->setLabel('last-update')
                ->setReadOnly()
                ->setDatetime()
                ->setCols(2)
        );

        // Grupo contabilidad
        $this->startGroup('accounting', title: 'accounting');

        $this->addComponent(
            ComponentText::make('codsubcuentacom')
                ->setLabel('subaccount-purchases')
                ->setLabelUrl('ListCuenta')
                ->setDescription('optional')
                ->setCols(2)
        );

        $this->addComponent(
            ComponentText::make('codsubcuentaven')
                ->setLabel('subaccount-sales')
                ->setLabelUrl('ListCuenta')
                ->setDescription('optional')
                ->setCols(2)
        );

        $this->addComponent(
            ComponentText::make('codsubcuentairpfcom')
                ->setLabel('subaccount-irpf')
                ->setLabelUrl('ListCuenta')
                ->setDescription('optional')
                ->setDisplay('none')
                ->setCols(2)
        );

        $this->addEditListView('EditVariante', 'Variante', 'variants', 'fa-solid fa-project-diagram');
        $this->addEditListView('EditStock', 'Stock', 'stock', 'fa-solid fa-dolly');
        $this->addEditListView('EditProductoProveedor', 'ProductoProveedor', 'suppliers', 'fa-solid fa-users');

        $pedidosCliente = $this->addListView('ListLineaPedidoCliente', 'LineaPedidoCliente', 'reserved', 'fa-solid fa-lock');
        $pedidosCliente->addSearchFields(['referencia', 'descripcion'])
            ->addOrderBy(['referencia'], 'reference')
            ->addOrderBy(['cantidad'], 'quantity')
            ->addOrderBy(['servido'], 'quantity-served')
            ->addOrderBy(['descripcion'], 'description')
            ->addOrderBy(['pvptotal'], 'amount')
            ->addOrderBy(['idlinea'], 'code', 2)
            ->addFilterSelect('referencia', 'reference', 'referencia', [])
            ->addFilterNumber('cantidad-gt', 'quantity', 'cantidad', '>=')
            ->addFilterNumber('cantidad-lt', 'quantity', 'cantidad', '<=')
            ->setSettings('btnNew', false)
            ->setSettings('btnDelete', false)
            ->setSettings('checkBoxes', false);
        $pedidosCliente->disableColumn('product');

        $pedidosProv = $this->addListView('ListLineaPedidoProveedor', 'LineaPedidoProveedor', 'pending-reception', 'fa-solid fa-ship');
        $pedidosProv->addSearchFields(['referencia', 'descripcion'])
            ->addOrderBy(['referencia'], 'reference')
            ->addOrderBy(['cantidad'], 'quantity')
            ->addOrderBy(['servido'], 'quantity-served')
            ->addOrderBy(['descripcion'], 'description')
            ->addOrderBy(['pvptotal'], 'amount')
            ->addOrderBy(['idlinea'], 'code', 2)
            ->addFilterSelect('referencia', 'reference', 'referencia', [])
            ->addFilterNumber('cantidad-gt', 'quantity', 'cantidad', '>=')
            ->addFilterNumber('cantidad-lt', 'quantity', 'cantidad', '<=')
            ->setSettings('btnNew', false)
            ->setSettings('btnDelete', false)
            ->setSettings('checkBoxes', false);
        $pedidosProv->disableColumn('product');

        $this->createViewsProductImages();
        $this->createViewDocFiles();
    }

    protected function execHtmlAction(string $action): void
    {
        switch ($action) {
            case 'add-image':
                $this->addImageAction();
                break;
            case 'delete-image':
                $this->deleteImageAction();
                break;
            case 'sort-images':
                $this->sortImagesAction();
                break;
            case 'add-file':
                $this->addFileAction();
                break;
            case 'delete-file':
                $this->deleteFileAction();
                break;
            case 'edit-file':
                $this->editFileAction();
                break;
            case 'unlink-file':
                $this->unlinkFileAction();
                break;
            case 'sort-files':
                $this->sortFilesAction();
                break;
        }
    }

    protected function modifyUI(): void
    {
        parent::modifyUI();

        $model = $this->editModel;
        if ($model === null || !$model->exists()) {
            foreach ($this->listViews() as $view) {
                $view->settings['active'] = false;
            }
            foreach ($this->htmlViews() as $view) {
                $view->settings['active'] = false;
            }
            return;
        }

        $where = [Where::eq('idproducto', $model->idproducto)];

        $variantesView = $this->listView('EditVariante');
        if ($variantesView !== null) {
            $variantesView->processFormData($this->request, 'load');
            $variantesView->loadData('', $where, ['idvariante' => 'DESC']);

            $attCount = (new Atributo())->count();
            if ($attCount < 4) $variantesView->disableColumn('attribute-value-4');
            if ($attCount < 3) $variantesView->disableColumn('attribute-value-3');
            if ($attCount < 2) $variantesView->disableColumn('attribute-value-2');
            if ($attCount < 1) $variantesView->disableColumn('attribute-value-1');

            $this->loadCustomAttributeWidgets('EditVariante');
        }

        $stockView = $this->listView('EditStock');
        if ($stockView !== null) {
            if ($model->nostock) {
                $stockView->settings['active'] = false;
            } else {
                $stockView->processFormData($this->request, 'load');
                $stockView->loadData('', $where, ['idstock' => 'DESC']);
                if (count(Almacenes::all()) <= 1) {
                    $stockView->disableColumn('warehouse');
                }
                $this->loadCustomReferenceWidget('EditStock');
            }
        }

        $suppliersView = $this->listView('EditProductoProveedor');
        if ($suppliersView !== null) {
            $suppliersView->processFormData($this->request, 'load');
            $suppliersView->loadData('', $where, ['id' => 'DESC']);
            $this->loadCustomReferenceWidget('EditProductoProveedor');
        }

        $pedidosClienteView = $this->listView('ListLineaPedidoCliente');
        if ($pedidosClienteView !== null) {
            $pedidosClienteView->processFormData($this->request, 'load');
            $whereCliente = array_merge($where, [Where::eq('actualizastock', -2)]);
            $this->loadReferenceFilter($pedidosClienteView, $model->idproducto);
            $pedidosClienteView->loadData('', $whereCliente);
            $pedidosClienteView->settings['active'] = $pedidosClienteView->model->count($whereCliente) > 0;
        }

        $pedidosProvView = $this->listView('ListLineaPedidoProveedor');
        if ($pedidosProvView !== null) {
            $pedidosProvView->processFormData($this->request, 'load');
            $whereProv = array_merge($where, [Where::eq('actualizastock', 2)]);
            $this->loadReferenceFilter($pedidosProvView, $model->idproducto);
            $pedidosProvView->loadData('', $whereProv);
            $pedidosProvView->settings['active'] = $pedidosProvView->model->count($whereProv) > 0;
        }

        $imagesView = $this->htmlViews()['EditProductoImagen'] ?? null;
        if ($imagesView !== null) {
            $imagesView->loadData('', $where, ['orden' => 'ASC'], 0, 0);
        }

        $docfilesView = $this->htmlViews()['docfiles'] ?? null;
        if ($docfilesView !== null) {
            $this->loadDataDocFiles($docfilesView, $this->getModelClassName(), $model->primaryColumnValue());
        }
    }

    public function extraHeaderButtons(): string
    {
        $model = $this->editModel;
        if ($model === null || !$model->exists()) {
            return '';
        }

        $url = 'CopyModel?model=' . $this->getModelClassName() . '&code=' . urlencode($model->primaryColumnValue());
        $label = \FacturaScripts\Core\Tools::lang()->trans('copy');
        return '<a href="' . $url . '" class="btn btn-sm btn-secondary ms-1">'
            . '<i class="fa-solid fa-cut fa-fw me-1" aria-hidden="true"></i>' . $label
            . '</a>';
    }

    protected function loadCustomAttributeWidgets(string $viewName): void
    {
        $columnsName = ['attribute-value-1', 'attribute-value-2', 'attribute-value-3', 'attribute-value-4'];
        $view = $this->listView($viewName);
        foreach ($columnsName as $key => $colName) {
            $column = $view?->columnForName($colName);
            if (empty($column) || $column->widget->getType() !== 'select') {
                continue;
            }

            $atributos = Atributo::all([
                Where::eq('num_selector', $key + 1),
                Where::orEq('num_selector', 0),
            ], ['nombre' => 'ASC']);

            $valoresAtributos = [];
            foreach ($atributos as $atributo) {
                foreach ($atributo->getValues() as $valor) {
                    $valoresAtributos[] = [
                        'value' => $valor->id,
                        'title' => $valor->valor,
                        'group' => $atributo->nombre,
                    ];
                }
            }

            $column->widget->setValuesFromArray($valoresAtributos, false, true, 'value', 'title', 'group');
        }
    }

    protected function loadCustomReferenceWidget(string $viewName): void
    {
        $id = $this->editModel?->idproducto;
        if (empty($id)) {
            return;
        }

        $where = [Where::eq('idproducto', $id)];
        $references = [];
        foreach (CodeModel::all('variantes', 'referencia', 'referencia', false, $where) as $code) {
            $references[] = ['value' => $code->code, 'title' => $code->description];
        }

        $column = $this->listView($viewName)?->columnForName('reference');
        if ($column && $column->widget->getType() === 'select') {
            $column->widget->setValuesFromArray($references, false);
        }
    }

    protected function loadReferenceFilter(BaseView $view, int $idproducto): void
    {
        if (!isset($view->filters['referencia'])) {
            return;
        }

        $values = [['code' => '', 'description' => '------']];
        $where = [Where::eq('idproducto', $idproducto)];
        foreach (CodeModel::all('variantes', 'referencia', 'referencia', false, $where) as $code) {
            $values[] = ['code' => $code->code, 'description' => $code->description];
        }

        $view->filters['referencia']->values = $values;
    }

    protected function sortImagesAction(): void
    {
        $idsOrdenadas = $this->request->request->getArray('orden', false);
        if (!empty($idsOrdenadas) && is_array($idsOrdenadas)) {
            $orden = 1;
            foreach ($idsOrdenadas as $idImagen) {
                $productoImagen = new ProductoImagen();
                $productoImagen->load($idImagen);
                $productoImagen->orden = $orden;
                if ($productoImagen->save()) {
                    $orden++;
                }
            }
        }

        $this->setTemplate(false);
        $this->response->json(['status' => 'ok']);
    }
}
