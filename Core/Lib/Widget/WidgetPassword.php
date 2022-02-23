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

use FacturaScripts\Dinamic\Lib\AssetManager;

/**
 * Description of WidgetPassword
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class WidgetPassword extends WidgetText
{
    /**
     * @param array $data
     */
    public function __construct($data)
    {
        parent::__construct($data);
        $this->autocomplete = isset($data['autocomplete']) && $data['autocomplete'] == 'true' || isset($data['autocomplete']) === false ? true : false;
    }

    /**
     * Adds assets to the asset manager.
     */
    protected function assets()
    {
        AssetManager::add('js', \FS_ROUTE . '/Dinamic/Assets/JS/WidgetPassword.js', 2);
        AssetManager::add('css', \FS_ROUTE . '/Dinamic/Assets/CSS/WidgetPassword.css', 2);

    }

    /**
     * @param object $model
     * @param string $title
     * @param string $description
     * @param string $titleurl
     *
     * @return string
     */
    public function edit($model, $title = '', $description = '', $titleurl = '')
    {
        $this->setValue($model);
        $descriptionHtml = empty($description) ? '' : '<small class="form-text text-muted">' . static::$i18n->trans($description) . '</small>';
        $labelHtml = '<label class="mb-1">' . $this->onclickHtml(static::$i18n->trans($title), $titleurl) . '</label>';

        if (empty($this->icon)) {
            return '<div class="form-group">'
                . $labelHtml
                . $this->inputHtml()
                . $descriptionHtml
                . '</div>';
        }

        $cssPsw = $this->autocomplete === false ? 'edit-psw' : '';

        return '<div class="form-group">'
            . $labelHtml
            . '<div class="input-group">'
            . '<div class="' . $this->css('input-group-prepend') . ' d-flex d-sm-none d-xl-flex">'
            . '<span class="input-group-text ' . $cssPsw . '"><i class="' . $this->icon . ' fa-fw"></i></span>'
            . '</div>'
            . $this->inputHtml()
            . '</div>'
            . $descriptionHtml
            . '</div>';
    }

    /**
     * @param string $type
     * @param string $extraClass
     *
     * @return string
     */
    protected function inputHtml($type = 'password', $extraClass = '')
    {
        $class = $this->combineClasses($this->css('form-control'), $this->class, $extraClass);

        if ($this->autocomplete) {
            return '<input type="' . $type . '" name="' . $this->fieldname . '" value="' . $this->value
                . '" class="' . $class . '"' . $this->inputHtmlExtraParams() . '/>';
        }

        return '<input type="text" name="' . $this->fieldname . '" value="' . $this->value
            . '" class="fs-psw ' . $class . '"' . $this->inputHtmlExtraParams() . '/>';
    }

    /**
     * @return string
     */
    protected function show()
    {
        return is_null($this->value) ? '' : '<span><span class="fs-psw pass">' . $this->value . '</span> <i class="list-psw fas fa-eye"></i></span>';
    }
}