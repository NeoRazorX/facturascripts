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

namespace FacturaScripts\Core\UI\Widget;

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Template\UI\Widget;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\AssetManager;

class WidgetSelect extends Widget
{
    /** @var Widget[] */
    public $creation_form = [];

    /** @var array */
    public $options = [];

    public function __construct(string $name, ?string $field = null, ?string $label = null)
    {
        parent::__construct($name, $field, $label);

        AssetManager::add('css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        AssetManager::add('css', 'https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css');
        AssetManager::add('js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js');
        AssetManager::add('js', 'Dinamic/Assets/js/UIWidgetSelect.js');
    }

    public function createOptionForm(array $widgets): self
    {
        $this->creation_form = $widgets;

        // para cada widget añadimos el padre
        foreach ($this->creation_form as $widget) {
            $widget->setParent($this);
        }

        return $this;
    }

    public function option($key)
    {
        return $this->options[$key] ?? '';
    }

    public function render(string $context = ''): string
    {
        switch ($context) {
            default:
                return $this->renderInput();

            case 'td':
                return '<td class="text-' . $this->align . '">' . $this->option($this->value) . '</td>';

            case 'th':
                return '<th class="text-' . $this->align . '">' . $this->label . '</th>';
        }
    }

    public function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function setOptionsFromModel(ModelClass $model, array $where = [], array $orderBy = [], int $offset = 0, int $limit = 0): self
    {
        $options = [];
        foreach ($model->all($where, $orderBy, $offset, $limit) as $item) {
            $options[$item->primaryColumnValue()] = $item->primaryDescription();
        }

        $this->setOptions($options);

        return $this;
    }

    protected function renderCreationButton(): string
    {
        if (empty($this->creation_form)) {
            return '';
        }

        return '<a href="#" class="text-success ml-2" data-toggle="modal" data-target="#' . $this->id() . '_creation">'
            . '<i class="fas fa-plus-square"></i>'
            . '</a>'
            . $this->renderCreationModal();
    }

    protected function renderCreationModal(): string
    {
        $html = '<div class="modal fade" id="' . $this->id() . '_creation" tabindex="-1" aria-labelledby="'
            . $this->id() . '_creation_label" aria-hidden="true">'
            . '<div class="modal-dialog">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title" id="' . $this->id() . '_label">'
            . '<i class="fas fa-plus-square mr-1"></i> ' . $this->label
            . '</h5>'
            . '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
            . '<span aria-hidden="true">&times;</span>'
            . '</button>'
            . '</div>'
            . '<div class="modal-body">';

        foreach ($this->creation_form as $widget) {
            $html .= $widget->render();
        }

        $html .= '</div>'
            . '<div class="modal-footer">'
            . '<button type="button" class="btn btn-secondary" data-dismiss="modal">'
            . Tools::lang()->trans('close')
            . '</button>'
            . '<button type="button" class="btn btn-success">'
            . Tools::lang()->trans('new')
            . '</button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';

        return $html;
    }

    protected function renderInput(): string
    {
        return '<div class="form-group">'
            . '<label for="' . $this->id() . '">' . $this->label . $this->renderCreationButton() . '</label><br/>'
            . '<select class="form-control ui-widget-select" id="' . $this->id() . '" name="' . $this->field . '">'
            . $this->renderOptions()
            . '</select>'
            . '</div>';
    }

    protected function renderOptions(): string
    {
        $html = '';
        foreach ($this->options as $key => $value) {
            if (is_array($value)) {
                $html .= '<optgroup label="' . $value['label'] . '">';
                foreach ($value['options'] as $key2 => $value2) {
                    $html .= '<option value="' . $key2 . '">' . $value2 . '</option>';
                }
                $html .= '</optgroup>';
                continue;
            }

            $html .= '<option value="' . $key . '">' . $value . '</option>';
        }

        return $html;
    }
}
