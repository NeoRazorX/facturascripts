<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\UI\Tab;

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Template\UI\SectionTab;
use FacturaScripts\Core\Template\UI\Widget;
use FacturaScripts\Core\Tools;
use Symfony\Component\HttpFoundation\Request;

class TabTable extends SectionTab
{
    /** @var array */
    public $data = [];

    /** @var ModelClass */
    protected $model;

    /** @var Widget[] */
    public $widgets = [];

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->icon = 'fas fa-list';
    }

    public function addWidget(Widget $widget): self
    {
        $this->widgets[] = $widget;

        return $this;
    }

    public function jsInitFunction(): string
    {
        return '';
    }

    public function jsRedrawFunction(): string
    {
        return '';
    }

    public function load(Request $request): bool
    {
        if ($this->model) {
            $this->counter = $this->model->count();
            $this->data = $this->model->all();
        }

        return true;
    }

    public function render(string $context = ''): string
    {
        $html = '<div class="container-fluid mt-3 mb-3">'
            . '<div class="form-row">'
            . '<div class="col-sm-auto">' . $this->renderNewButton() . $this->renderDeleteButton() . '</div>'
            . '<div class="col-sm">' . $this->renderSearchForm() . '</div>'
            . '<div class="col-sm-auto">' . $this->renderFilterButton() . $this->renderSortButton() . '</div>'
            . '</div>'
            . '</div>'
            . '<div class="table-responsive">'
            . '<table class="table table-striped table-hover table-sm">'
            . '<thead>'
            . '<tr>'
            . '<th class="text-center">'
            . '<div class="form-check form-check-inline m-0 toggle-ext-link">'
            . '<input class="form-check-input listActionCB" type="checkbox">'
            . '</div>'
            . '</th>';

        foreach ($this->widgets as $widget) {
            $html .= $widget->render('th');
        }

        $html .= '</tr>'
            . '</thead>'
            . '<tbody>';

        foreach ($this->data as $row) {
            $html .= '<tr>'
                . '<td class="text-center">'
                . '<div class="form-check form-check-inline m-0 toggle-ext-link">'
                . '<input class="form-check-input listActionCB" type="checkbox">'
                . '</div>'
                . '</td>';

            foreach ($this->widgets as $widget) {
                if ($row instanceof ModelClass) {
                    $html .= $widget->setValueFromModel($row)->render('td');
                    continue;
                }

                if (is_array($row)) {
                    $html .= $widget->setValueFromArray($row)->render('td');
                    continue;
                }

                $html .= '<td>' . $row . '</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</tbody>'
            . '</table>'
            . '</div>';

        return $html;
    }

    protected function renderDeleteButton(): string
    {
        return '<a href="#" class="btn btn-danger" title="' . Tools::lang()->trans('delete')
            . '"><i class="fas fa-trash-alt"></i></a> ';
    }

    protected function renderFilterButton(): string
    {
        return '<a href="#" class="btn btn-light"><i class="fas fa-filter"></i> '
            . Tools::lang()->trans('filters') . '</a> ';
    }

    protected function renderNewButton(): string
    {
        return '<a href="#" class="btn btn-success">' . Tools::lang()->trans('new') . '</a> ';
    }

    protected function renderSearchForm(): string
    {
        return '<div class="input-group">'
            . '<input type="text" class="form-control" placeholder="' . Tools::lang()->trans('search') . '">'
            . '<div class="input-group-append">'
            . '<button class="btn btn-secondary" type="button"><i class="fas fa-search"></i></button>'
            . '</div>'
            . '</div>';
    }

    protected function renderSortButton(): string
    {
        return '<a href="#" class="btn btn-light"><i class="fas fa-sort"></i> '
            . Tools::lang()->trans('sort') . '</a> ';
    }

    public function setModel(ModelClass $model): self
    {
        $this->model = $model;

        return $this;
    }
}
