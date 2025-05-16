<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\AssetManager;

/**
 * Description of WidgetPassword
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class WidgetPassword extends WidgetText
{

    /**
     * Adds assets to the asset manager.
     */
    protected function assets()
    {
        AssetManager::addJs(FS_ROUTE . '/Dinamic/Assets/JS/WidgetPassword.js', 2);
        AssetManager::addCss(FS_ROUTE . '/Dinamic/Assets/CSS/WidgetPassword.css', 2);
    }

    /**
     * @param object $model
     * @param string $title
     * @param string $description
     * @param string $titleurl
     *
     * @return string
     */
    public function edit($model, $title = '', $description = '', $titleurl = ''): string
    {
        $this->setValue($model);
        $descriptionHtml = empty($description) ? '' : '<small class="form-text text-muted">' . Tools::lang()->trans($description) . '</small>';
        $labelHtml = '<label class="mb-0">' . $this->onclickHtml(Tools::lang()->trans($title), $titleurl) . '</label>';

        return '<div class="mb-3">'
            . $labelHtml
            . '<div class="input-group">'
            . '<span class="input-group-text edit-psw"><i class="fa-solid fa-eye fa-fw"></i></span>'
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
        return '<input type="text" name="' . $this->fieldname . '" value="' . $this->value
            . '" class="fs-psw ' . $class . '"' . $this->inputHtmlExtraParams() . '/>';
    }

    /**
     * @return string
     */
    protected function show()
    {
        return is_null($this->value) ? '' : '<span><span class="fs-psw pass">' . $this->value . '</span> <i class="list-psw fa-solid fa-eye"></i></span>';
    }
}
