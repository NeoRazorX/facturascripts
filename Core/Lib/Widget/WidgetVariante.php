<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\Widget;

use FacturaScripts\Core\DataSrc\Fabricantes;
use FacturaScripts\Core\DataSrc\Familias;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Model\Familia;
use FacturaScripts\Dinamic\Model\Join\VarianteProducto;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Widget para seleccionar una variante de producto desde un modal con búsqueda.
 */
class WidgetVariante extends WidgetText
{
    /** @var string Campo de la variante usado para comparar y guardar el valor seleccionado. */
    public $match;

    /** @param array $data Configuración del widget. */
    public function __construct($data)
    {
        parent::__construct($data);

        $this->match = $data['match'] ?? 'referencia';
    }

    public function edit($model, $title = '', $description = '', $titleurl = '')
    {
        $this->setValue($model);

        // obtenemos un nuevo ID cada vez
        $this->id = $this->getUniqueId();

        $descriptionHtml = empty($description) ?
            '' :
            '<small class="form-text text-muted">' . Tools::trans($description) . '</small>';
        $label = Tools::trans($title);
        $labelHtml = $this->onclickHtml($label, $titleurl);
        $icon = empty($this->icon) ? 'fa-solid fa-cubes' : $this->icon;

        // hay que cargar el producto para mostrar su referencia
        $variante = new Variante();
        if ($this->value !== null && $variante->loadWhereEq($this->match, $this->value) && $this->onclick === 'EditProducto') {
            $labelHtml = '<a href="' . $this->escapeHtml(Tools::config('route') . '/' . $variante->url()) . '">' . $label . '</a>';
        }

        $safeValue = $this->escapeHtml($this->value);
        $safeReference = $this->escapeHtml($variante->referencia ?? Tools::trans('select'));
        if ($this->readonly()) {
            return '<div class="mb-3 d-grid">'
                . '<input type="hidden" id="' . $this->id . '" name="' . $this->fieldname . '" value="' . $safeValue . '">'
                . $labelHtml
                . '<a href="' . $this->escapeHtml($variante->url()) . '" class="btn btn-outline-secondary">'
                . '<i class="' . $icon . ' fa-fw"></i> ' . $safeReference
                . '</a>'
                . $descriptionHtml
                . '</div>';
        }

        return '<div class="mb-3 d-grid">'
            . '<input type="hidden" id="' . $this->id . '" name="' . $this->fieldname . '" value="' . $safeValue . '">'
            . $labelHtml
            . '<a href="#" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modal_' . $this->id . '">'
            . '<i class="' . $icon . ' fa-fw"></i> '
            . '<span id="modal_span_' . $this->id . '">' . $safeReference . '</span>'
            . '</a>'
            . $descriptionHtml
            . '</div>'
            . $this->renderModal($icon, $label);
    }

    /**
     * @param object $model Modelo donde se guarda el valor seleccionado.
     * @param Request $request Petición con los datos del formulario.
     */
    public function processFormData(&$model, $request)
    {
        $value = $request->request->get($this->fieldname, '');
        $model->{$this->fieldname} = ('' === $value) ? null : $value;
    }

    public function tableCell($model, $display = 'left')
    {
        $this->setValue($model);
        $class = $this->combineClasses($this->tableCellClass('text-' . $display), $this->class);

        // hay que cargar el producto para mostrar su referencia
        $variante = new Variante();
        if ($this->value !== null && $variante->loadWhereEq($this->match, $this->value) && $this->onclick === 'EditProducto') {
            return '<td class="' . $class . '">'
                . '<a href="' . $this->escapeHtml(Tools::config('route') . '/' . $variante->url()) . '" class="cancelClickable">'
                . $this->escapeHtml($variante->referencia) . '</a>'
                . '</td>';
        }

        return '<td class="' . $class . '">' . $this->onclickHtml($this->escapeHtml($variante->referencia)) . '</td>';
    }

    /**
     * @param string $query Texto de búsqueda.
     * @param string $codfabricante Código del fabricante para filtrar.
     * @param string $codfamilia Código de la familia para filtrar.
     * @param string $sort Orden aplicado a los resultados.
     * @return Variante[] Variantes encontradas.
     */
    public function variantes(string $query = '', string $codfabricante = '', string $codfamilia = '', string $sort = 'ref-asc'): array
    {
        $list = [];
        $where = [
            Where::eq('productos.bloqueado', false),
        ];

        // cargamos y añadimos la variante seleccionada
        $model = new Variante();
        if ($this->value && $model->load($this->value)) {
            $list[] = $model;
            $where[] = Where::notEq('variantes.referencia', $model->referencia);
        }

        $joinModel = new VarianteProducto();
        if ($query) {
            $where[] = Where::like('variantes.referencia|productos.descripcion', $query);
        }

        if ($codfabricante) {
            $where[] = Where::eq('codfabricante', $codfabricante);
        }

        if ($codfamilia) {
            $where[] = Where::eq('codfamilia', $codfamilia);
        }

        switch ($sort) {
            case 'ref-desc':
                $orderBy = ['referencia' => 'DESC'];
                break;

            case 'price-asc':
                $orderBy = ['precio' => 'ASC'];
                break;

            case 'price-desc':
                $orderBy = ['precio' => 'DESC'];
                break;

            case 'stock-asc':
                $orderBy = ['stockfis' => 'ASC'];
                break;

            case 'stock-desc':
                $orderBy = ['stockfis' => 'DESC'];
                break;

            case 'ref-asc':
            default:
                $orderBy = ['referencia' => 'ASC'];
                break;
        }

        foreach ($joinModel->all($where, $orderBy, 0, 50) as $item) {
            $list[] = $model->get($item->idvariante);
        }

        return $list;
    }

    protected function assets(): void
    {
        $route = Tools::config('route');
        AssetManager::addJs($route . '/Dinamic/Assets/JS/WidgetVariante.js?v=' . Tools::date());
    }

    protected function renderFamilyFilter(): string
    {
        $options = [
            '<option value="">' . Tools::trans('family') . '</option>',
            '<option value="">------</option>',
        ];

        foreach (Familias::children() as $item) {
            $options[] = $this->familyOption($item);
        }

        return '<select class="form-select mb-2" id="modal_' . $this->id . '_fam" onchange="widgetVarianteSearch(\'' . $this->id . '\');">'
            . implode('', $options)
            . '</select>';
    }

    private function familyOption(Familia $family, int $level = 0, array $visited = []): string
    {
        $visited[$family->codfamilia] = true;
        $prefix = $level > 0 ? str_repeat('-', $level) . ' ' : '';
        $html = '<option value="' . $this->escapeHtml($family->codfamilia) . '">'
            . $prefix . $this->escapeHtml($family->descripcion) . '</option>';

        // añadimos las subfamilias de forma recursiva
        foreach (Familias::children($family->codfamilia) as $child) {
            if (isset($visited[$child->codfamilia])) {
                continue;
            }

            $html .= $this->familyOption($child, $level + 1, $visited);
        }

        return $html;
    }

    protected function renderManufacturerFilter(): string
    {
        $options = [
            '<option value="">' . Tools::trans('manufacturer') . '</option>',
            '<option value="">------</option>',
        ];

        foreach (Fabricantes::all() as $item) {
            $options[] = '<option value="' . $this->escapeHtml($item->codfabricante) . '">' . $this->escapeHtml($item->nombre) . '</option>';
        }

        return '<select class="form-select mb-2" id="modal_' . $this->id . '_fab" onchange="widgetVarianteSearch(\'' . $this->id . '\');">'
            . implode('', $options)
            . '</select>';
    }

    protected function renderModal(string $icon, string $label): string
    {
        return '<div class="modal fade" id="modal_' . $this->id . '" tabindex="-1" aria-labelledby="modal_'
            . $this->id . '_label" aria-hidden="true">'
            . '<div class="modal-dialog modal-xl">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title" id="modal_' . $this->id . '_label">'
            . '<i class="' . $icon . ' me-1"></i> ' . $label
            . '</h5>'
            . '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">'
            . ''
            . '</button>'
            . '</div>'
            . '<div class="modal-body">'
            . '<div class="row g-3">'
            . '<div class="col">' . $this->renderQueryFilter() . '</div>'
            . '<div class="col">' . $this->renderManufacturerFilter() . '</div>'
            . '<div class="col">' . $this->renderFamilyFilter() . '</div>'
            . '<div class="col">' . $this->renderSortFilter() . '</div>'
            . '</div>'
            . '</div>'
            . $this->renderVariantList()
            . '<div class="modal-footer p-3">'
            . '<div class="w-100 d-flex gap-2">'
            . $this->renderNewProductBtn()
            . $this->renderSelectNoneBtn()
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    protected function renderQueryFilter(): string
    {
        return '<div class="input-group mb-2">'
            . '<input type="text" id="modal_' . $this->id . '_q" class="form-control" placeholder="'
            . Tools::trans('search') . '" oninput="widgetVarianteSearchKp(\'' . $this->id . '\', event);" '
            . 'onkeydown="if(event.key===\'Enter\'){event.preventDefault();widgetVarianteSearch(\'' . $this->id . '\');}" autofocus>'
            . '<button type="button" class="btn btn-primary" onclick="widgetVarianteSearch(\'' . $this->id . '\');">'
            . '<i class="fa-solid fa-search"></i>'
            . '</button>'
            . '</div>';
    }

    protected function renderNewProductBtn(): string
    {
        $producto = new Producto();
        return '<a href="' . $this->escapeHtml($producto->url('new')) . '" target="_blank" class="btn btn-success">'
            . '<i class="fa-solid fa-plus me-1"></i> ' . Tools::trans('new-product')
            . '</a>';
    }

    protected function renderSelectNoneBtn(): string
    {
        if ($this->required) {
            return '';
        }

        return '<button type="button" class="btn btn-secondary ms-auto" onclick="widgetVarianteSelect(\'' . $this->id . '\', \'\'); return false;">'
            . '<i class="fa-solid fa-times me-1"></i> ' . Tools::trans('none')
            . '</button>';
    }

    protected function renderSortFilter(): string
    {
        return '<select class="form-select mb-2" id="modal_' . $this->id . '_s" onchange="widgetVarianteSearch(\'' . $this->id . '\');">'
            . '<option value="ref-asc" selected>' . Tools::trans('sort-by-ref-asc') . '</option>'
            . '<option value="ref-desc">' . Tools::trans('sort-by-ref-desc') . '</option>'
            . '<option value="price-asc">' . Tools::trans('sort-by-price-asc') . '</option>'
            . '<option value="price-desc">' . Tools::trans('sort-by-price-desc') . '</option>'
            . '<option value="stock-asc">' . Tools::trans('sort-by-stock-asc') . '</option>'
            . '<option value="stock-desc">' . Tools::trans('sort-by-stock-desc') . '</option>'
            . '</select>';
    }

    protected function renderVariantList(): string
    {
        $items = [];
        foreach ($this->variantes() as $item) {
            $match = $this->escapeHtml($item->{$this->match});
            $description = $this->escapeHtml(Tools::textBreak($item->description(), 300));
            $reference = $this->escapeHtml($item->referencia);
            $url = $this->escapeHtml($item->url());

            // Determinar la clase de color para el precio
            $priceClass = '';
            if ($item->precio < 0) {
                $priceClass = ' text-danger';
            } elseif ($item->precio == 0) {
                $priceClass = ' text-warning';
            }

            // Determinar la clase de color para el stock
            $stockClass = '';
            if ($item->stockfis < 0) {
                $stockClass = ' text-danger';
            } elseif ($item->stockfis == 0) {
                $stockClass = ' text-warning';
            }

            $items[] = '<tr class="clickableRow widget-variante-option" data-widget-variante-id="' . $this->id . '" data-widget-variante-value="' . $match . '">'
                . '<td class="text-center">'
                . '<a href="' . $url . '" target="_blank" class="widget-variante-link">'
                . '<i class="fa-solid fa-external-link-alt fa-fw"></i>'
                . '</a>'
                . '</td>'
                . '<td><b>' . $reference . '</b> ' . $description . '</td>'
                . '<td class="text-end text-nowrap' . $priceClass . '">' . Tools::money($item->precio) . '</td>'
                . '<td class="text-end text-nowrap' . $stockClass . '">' . Tools::number($item->stockfis, 0) . '</td>'
                . '</tr>';
        }

        return '<div class="table-responsive">'
            . '<table class="table table-hover mb-0">'
            . '<thead>'
            . '<tr>'
            . '<th class="text-center"></th>'
            . '<th>' . Tools::trans('product') . '</th>'
            . '<th class="text-end">' . Tools::trans('price') . '</th>'
            . '<th class="text-end">' . Tools::trans('stock') . '</th>'
            . '</tr>'
            . '</thead>'
            . '<tbody id="list_' . $this->id . '">' . implode('', $items) . '</tbody>'
            . '</table>'
            . '</div>';
    }
}
