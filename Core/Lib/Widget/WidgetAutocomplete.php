<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Description of WidgetAutocomplete
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class WidgetAutocomplete extends WidgetSelect
{
    
    public function __construct($data)
    {
        parent::__construct($data);
        static::$assets['js'][] = FS_ROUTE . '/Dinamic/Assets/JS/WidgetAutocomplete.js';
    }

    public function edit($model, $title = '', $description = '')
    {
        $this->setValue($model);
        $id = $this->getUniqueId();
        $descriptionHtml = empty($description) ? '' : '<small class="form-text text-muted">' . static::$i18n->trans($description) . '</small>';
        $inputHtml = $this->inputHtml();
        $labelHtml = '<label>' . static::$i18n->trans($title) . '</label>';

        return '<input type="hidden" name="' . $this->fieldname . '" value="' . $this->value . '" id="' . $id . 'Autocomplete"/>'
            . '<div class="form-group">'
            . $labelHtml
            . '<div class="input-group">'
            . '<div class="input-group-prepend">'
            . '<span class="input-group-text"><i class="far fa-keyboard fa-fw"></i></span>'
            . '</div>'
            . $inputHtml
            . '</div>'
            . $descriptionHtml
            . '</div>';
    }

    protected function inputHtml($type = 'text', $extraClass = 'widget-autocomplete')
    {
        $class = empty($extraClass) ? 'form-control' : 'form-control ' . $extraClass;
        $selected = static::$codeModel->getDescription($this->source, $this->fieldcode, $this->value, $this->fieldtitle);
        return '<input type="' . $type . '" value="' . $selected . '" class="' . $class . '" data-field="' . $this->fieldname
            . '" data-source="' . $this->source . '" data-fieldcode="' . $this->fieldcode . '" data-fieldtitle="' . $this->fieldtitle
            . '" autocomplete="off"' . $this->inputHtmlExtraParams() . '/>';
    }
}
