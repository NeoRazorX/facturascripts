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
use FacturaScripts\Core\UI\ActionResult;
use Symfony\Component\HttpFoundation\Request;

class TabForm extends SectionTab
{
    /** @var ModelClass */
    protected $model;

    /** @var Widget[] */
    protected $widgets = [];

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->icon = 'fas fa-edit';

        $this->onSave('component:saveAction');
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
        return true;
    }

    public function onSave(string $function, int $position = 0): self
    {
        $this->addEvent('save', $function, $position);

        return $this;
    }

    public function render(string $context = ''): string
    {
        $events = $this->events();
        $eventName = empty($events) ? '' : $events[0]->name();

        $html = '<form id="' . $this->id() . '" method="post">'
            . '<input type="hidden" name="_event" value="' . $eventName . '">'
            . '<div class="container-fluid mt-4 mb-4">'
            . '<div class="form-row">';

        foreach ($this->widgets as $widget) {
            $html .= empty($widget->cols()) ?
                '<div class="col-sm">' . $widget->render() . '</div>' :
                '<div class="col-sm-' . $widget->cols() . '">' . $widget->render() . '</div>';
        }

        $html .= '<div class="col-12 text-right">'
            . '<button type="button" class="btn btn-danger float-left">'
            . '<i class="fas fa-trash-alt mr-1"></i> ' . Tools::lang()->trans('delete')
            . '</button>'
            . '<button type="reset" class="btn btn-secondary">'
            . '<i class="fas fa-undo mr-1"></i> ' . Tools::lang()->trans('undo')
            . '</button>'
            . '<button type="submit" class="btn btn-primary ml-1">'
            . '<i class="fas fa-save mr-1"></i> ' . Tools::lang()->trans('save')
            . '</button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</form>';

        return $html;
    }

    public function saveAction(): ActionResult
    {
        Tools::log()->info('Función saveAction() del formulario ' . $this->id());

        return $this->actionResult();
    }

    public function setModel(ModelClass $model): self
    {
        $this->model = $model;

        return $this;
    }
}
