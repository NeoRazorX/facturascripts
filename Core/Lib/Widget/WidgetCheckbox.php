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
 * Description of WidgetCheckbox
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class WidgetCheckbox extends BaseWidget
{

    /**
     * 
     * @param object $model
     * @param string $title
     * @param string $description
     *
     * @return string
     */
    public function edit($model, $title = '', $description = '')
    {
        $this->setValue($model);
        $checked = $this->value ? ' checked=""' : '';
        $id = 'checkbox' . $this->getUniqueId();

        $inputHtml = '<input type="checkbox" name="' . $this->fieldname . '" value="TRUE" id="' . $id . '" class="form-check-input"' . $checked . '/>';
        $labelHtml = '<label for="' . $id . '">' . static::$i18n->trans($title) . '</label>';
        $descriptionHtml = empty($description) ? '' : '<small class="form-text text-muted">' . static::$i18n->trans($description) . '</small>';

        return '<div class="form-group form-check">'
            . $inputHtml
            . $labelHtml
            . $descriptionHtml
            . '</div>';
    }

    /**
     * 
     * @param object $model
     */
    protected function setValue($model)
    {
        $this->value = isset($model->{$this->fieldname}) ? (bool) $model->{$this->fieldname} : false;
    }

    /**
     * 
     * @return string
     */
    protected function show()
    {
        if (is_null($this->value)) {
            return '-';
        }

        return $this->value ? static::$i18n->trans('yes') : static::$i18n->trans('no');
    }

    /**
     * 
     * @param string $initialClass
     * @param string $alternativeClass
     *
     * @return string
     */
    protected function tableCellClass($initialClass = '', $alternativeClass = '')
    {
        if (false === $this->value) {
            $alternativeClass = 'text-danger';
        } elseif (true === $this->value) {
            $alternativeClass = 'text-success';
        }

        return parent::tableCellClass($initialClass, $alternativeClass);
    }
}
