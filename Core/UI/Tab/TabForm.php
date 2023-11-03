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

use FacturaScripts\Core\Template\UI\Component;
use FacturaScripts\Core\Template\UI\SectionTab;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\UI\ActionResult;
use FacturaScripts\Core\UI\Widget\WidgetCanvas;
use FacturaScripts\Core\UI\Widget\WidgetCheckbox;
use FacturaScripts\Core\UI\Widget\WidgetColor;
use FacturaScripts\Core\UI\Widget\WidgetDate;
use FacturaScripts\Core\UI\Widget\WidgetDatetime;
use FacturaScripts\Core\UI\Widget\WidgetFilemanager;
use FacturaScripts\Core\UI\Widget\WidgetNumber;
use FacturaScripts\Core\UI\Widget\WidgetSelect;
use FacturaScripts\Core\UI\Widget\WidgetText;
use FacturaScripts\Core\UI\Widget\WidgetTextarea;
use FacturaScripts\Core\UI\Widget\WidgetTime;

class TabForm extends SectionTab
{
    /** @var array */
    protected $form;

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->icon = 'fas fa-edit';

        // añadimos algunos datos de prueba
        $this->form = [
            ['widget' => new WidgetText('name'), 'cols' => 4],
            ['widget' => new WidgetText('surname')],
            ['widget' => new WidgetDate('date'), 'cols' => 2],
            ['widget' => new WidgetNumber('age'), 'cols' => 2],
            ['widget' => new WidgetTextarea('observations'), 'cols' => 12],
            ['widget' => new WidgetCheckbox('active'), 'cols' => 12],
            ['widget' => new WidgetSelect('type')],
            ['widget' => new WidgetFilemanager('file')],
            ['widget' => new WidgetColor('color'), 'cols' => 2],
            ['widget' => new WidgetTime('time'), 'cols' => 2],
            ['widget' => new WidgetDatetime('datetime')],
            ['widget' => new WidgetCanvas('canvas'), 'cols' => 12]
        ];

        // para cada widget le añadimos el parent
        foreach ($this->form as $item) {
            $item['widget']->setParent($this);
        }
    }

    public function jsInitFunction(): string
    {
        return '';
    }

    public function jsRedrawFunction(): string
    {
        return '';
    }

    public function render(string $context = ''): string
    {
        $action = $this->id() . ':save';

        $html = '<form id="' . $this->id() . '" method="post">'
            . '<input type="hidden" name="_action" value="' . $action . '">'
            . '<div class="container-fluid mt-4 mb-4">'
            . '<div class="form-row">';

        foreach ($this->form as $item) {
            $html .= empty($item['cols']) ?
                '<div class="col-sm">' . $item['widget']->render() . '</div>' :
                '<div class="col-sm-' . $item['cols'] . '">' . $item['widget']->render() . '</div>';
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

    public function setOnSave(string $function): self
    {
        $this->addAction('save', $function);

        return $this;
    }

    public function setParent(Component $parent): Component
    {
        $return = parent::setParent($parent);

        $this->setOnSave('component:saveAction');

        return $return;
    }
}
