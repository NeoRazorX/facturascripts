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

namespace FacturaScripts\Core\Lib\Widget;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\AssetManager;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Subcuenta;

class WidgetSubcuenta extends WidgetText
{
    /** @var string */
    public $match;

    /** @param array $data */
    public function __construct($data)
    {
        parent::__construct($data);

        $this->match = $data['match'] ?? 'codsubcuenta';
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
        $icon = empty($this->icon) ? 'fa-solid fa-calculator' : $this->icon;

        // hay que cargar la subcuenta para mostrar su nombre
        $subcuenta = new Subcuenta();
        if (false === empty($this->value)) {
            $subcuenta->loadWhere([
                new DataBaseWhere($this->match, $this->value)
            ]);
        }

        if ($this->readonly()) {
            return '<div class="mb-3 d-grid">'
                . '<input type="hidden" id="' . $this->id . '" name="' . $this->fieldname . '" value="' . $this->value . '">'
                . $labelHtml
                . '<a href="' . $subcuenta->url() . '" class="btn btn-outline-secondary">'
                . '<i class="' . $icon . ' fa-fw"></i> ' . ($subcuenta->nombre ?? $this->value ?? Tools::trans('select'))
                . '</a>'
                . $descriptionHtml
                . '</div>';
        }

        $html = '<div class="mb-3 d-grid">'
            . '<input type="hidden" id="' . $this->id . '" name="' . $this->fieldname . '" value="' . $this->value . '">'
            . $labelHtml
            . '<a href="#" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modal_' . $this->id . '">'
            . '<i class="' . $icon . ' fa-fw"></i> '
            . '<span id="modal_span_' . $this->id . '">' . ($subcuenta->nombre ?? $this->value ?? Tools::trans('select')) . '</span>'
            . '</a>'
            . $descriptionHtml
            . '</div>';
        $html .= $this->renderModal($icon, $label);
        return $html;
    }

    /**
     * @param string $query
     * @param string $codfabricante
     * @param string $codfamilia
     * @param string $sort
     * @return Subcuenta[]
     */
    public function subcuentas(string $query = '', string $sort = 'ref-asc'): array
    {
        $list = [];
        $where = [];

        // cargamos y añadimos la subcuenta seleccionada
        $model = new Subcuenta();
        if ($this->value && $model->loadWhere([new DataBaseWhere($this->match, $this->value)])) {
            $list[] = clone $model;
            $where[] = new DataBaseWhere($model->primaryColumn(), $model->id, '<>');
        }

        if ($query) {
            $where[] = new DataBaseWhere('codsubcuenta|descripcion', $query, 'LIKE');
        }

        switch ($sort) {
            case 'ref-desc':
            case 'cod-desc':
                $orderBy = ['codsubcuenta' => 'DESC'];
                break;

            default:
                $orderBy = ['codsubcuenta' => 'ASC'];
                break;
        }

        foreach ($model->all($where, $orderBy, 0, 50) as $item) {
            $list[] = $item;
        }
        
        return $list;
    }

    /**
     * @param object $model
     * @param Request $request
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

        $subcuenta = new Subcuenta();
        if (false === empty($this->value)) {
            $subcuenta->loadWhere([
                new DataBaseWhere($this->match, $this->value)
            ]);
        }

        return '<td class="' . $class . '">' . $this->onclickHtml($subcuenta->nombre ?? $this->value) . '</td>';
    }

    public function search(string $query, string $codejercicio, string $sort): array
    {
        $where = [];
        if (false === empty($query)) {
            $where[] = new DataBaseWhere('codsubcuenta', $query, 'LIKE');
            $where[] = new DataBaseWhere('descripcion', $query, 'LIKE');
        }

        if (false === empty($codejercicio)) {
            $where[] = new DataBaseWhere('codejercicio', $codejercicio);
        }

        switch ($sort) {
            case 'cod-desc':
                $orderBy = ['codsubcuenta' => 'DESC'];
                break;

            default:
                $orderBy = ['codsubcuenta' => 'ASC'];
                break;
        }

        $subcuenta = new Subcuenta();
        return $subcuenta->fetchAll($where, $orderBy, 0, 100);
    }

    protected function assets(): void
    {
        $route = Tools::config('route');
        AssetManager::addJs($route . '/Core/Assets/JS/WidgetSubcuenta.js');
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
            . '</div>'
            . '</div>'
            . $this->renderSubaccountList()
            . '<div class="modal-footer p-2 d-grid">' . $this->renderSubaccountNoneBtn() . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    protected function renderQueryFilter(): string
    {
        return '<div class="input-group mb-2">'
            . '<input type="text" id="modal_' . $this->id . '_q" class="form-control" placeholder="'
            . Tools::trans('search') . '" onkeydown="widgetSubaccountSearchKp(\'' . $this->id . '\', event);" autofocus>'
            . '<button type="button" class="btn btn-primary" onclick="widgetSubaccountSearch(\'' . $this->id . '\');">'
            . '<i class="fa-solid fa-search"></i>'
            . '</button>'
            . '</div>';
    }

    protected function renderSubaccountList(): string
    {
        $items = [];
        foreach ($this->subcuentas() as $item) {
            $match = $item->codsubcuenta;
            $items[] = '<tr class="clickableRow" onclick="widgetSubaccountSelect(\'' . $this->id . '\', \'' . $match . '\');">'
                . '<td><b>' . $item->codsubcuenta . '</b> ' . '</td>'
                . '<td class="text-center text-nowrap">' . $item->descripcion . '</td>'
                . '</tr>';
        }

        return '<div class="table-responsive">'
            . '<table class="table table-hover mb-0">'
            . '<thead>'
            . '<tr>'
            . '<th>' . Tools::trans('Subcuenta') . '</th>'
            . '<th class="text-center">' . Tools::trans('description') . '</th>'
            . '</tr>'
            . '</thead>'
            . '<tbody id="list_' . $this->id . '">' . implode('', $items) . '</tbody>'
            . '</table>'
            . '</div>';
    }

    protected function renderSubaccountNoneBtn(): string
    {
        if ($this->required) {
            return '';
        }

        return '<a href="#" class="btn btn-secondary" onclick="widgetSubaccountSelect(\'' . $this->id . '\', \'\');">'
            . '<i class="fa-solid fa-times me-1"></i>' . Tools::trans('none')
            . '</a>';
    }

}
