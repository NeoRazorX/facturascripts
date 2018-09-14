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

use FacturaScripts\Core\Base\Translator;

/**
 * Description of RowActions
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class RowActions
{

    /**
     *
     * @var array
     */
    protected $children;

    /**
     *
     * @var Translator
     */
    protected static $i18n;

    /**
     *
     * @param array $data
     */
    public function __construct($data)
    {
        if (!isset(static::$i18n)) {
            static::$i18n = new Translator();
        }

        $this->children = $data['children'];
    }

    /**
     *
     * @param string $button
     * @param string $viewName
     * @param string $jsFunction
     *
     * @return string
     */
    protected function renderButton($button, $viewName, $jsFunction)
    {
        $color = isset($button['color']) ? $button['color'] : 'light';
        $icon = isset($button['icon']) ? '<i class="fas ' . $button['icon'] . ' fa-fw"></i> ' : '';
        $label = isset($button['label']) ? static::$i18n->trans($button['label']) : '';

        if (!isset($button['type'])) {
            return '';
        }

        if ($button['type'] === 'modal') {
            return '<button type="button" class="btn btn-' . $color . '"data-toggle="modal" data-target="#modal'
                . $button['action'] . '">' . $icon . $label . '</button>';
        }

        /// type action
        if (empty($jsFunction)) {
            return '<button type="submit" name="action" value="' . $button['action'] . '" class="btn btn-'
                . $color . '">' . $icon . $label . '</button>';
        }

        return '<button type="button" class="btn btn-' . $color . '" onclick="' . $jsFunction
            . '(\'' . $viewName . '\',\'' . $button['action'] . '\');">' . $icon . $label . '</button>';
    }

    /**
     *
     * @param string $viewName
     * @param string $jsFunction
     *
     * @return string
     */
    public function render($viewName, $jsFunction = '')
    {
        $html = '';
        foreach ($this->children as $child) {
            if ($child['tag'] == 'button') {
                $onclick = $child['onclick'] ?? $jsFunction;
                $html .= $this->renderButton($child, $viewName, $onclick);
            }
        }

        if (!empty($html)) {
            $html = '<div class="col d-flex justify-content-center">' . $html . '</div>';
        }
        return $html;
    }
}
