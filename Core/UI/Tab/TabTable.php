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
        $html = '<div class="table-responsive">'
            . '<table class="table table-striped table-hover table-sm">'
            . '<thead>'
            . '<tr>';

        foreach ($this->widgets as $widget) {
            $html .= '<th>' . $widget->label() . '</th>';
        }

        $html .= '</tr>'
            . '</thead>'
            . '<tbody>';

        foreach ($this->data as $row) {
            $html .= '<tr>';

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

    public function setModel(ModelClass $model): self
    {
        $this->model = $model;

        return $this;
    }
}
