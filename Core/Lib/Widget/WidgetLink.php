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
 * Description of WidgetLink
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class WidgetLink extends BaseWidget
{

    /**
     *
     * @param string $inside
     * @param string $titleurl
     *
     * @return string
     */
    protected function onclickHtml($inside, $titleurl = '')
    {
        if (empty($this->onclick) || is_null($this->value)) {
            return empty($titleurl) ? $inside : '<a href="' . $titleurl . '">' . $inside . '</a>';
        }

        return '<a target="_blank" href="' . $this->onclick . '?code=' . rawurlencode($this->value) . '" class="cancelClickable">' . $inside . '</a>';
    }

    /**
     *
     * @param object $model
     */
    protected function setValue($model)
    {
        parent::setValue($model);
        $this->onclick = $this->value;
    }
}
