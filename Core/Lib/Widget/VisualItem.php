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
 * Description of VisualItem
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class VisualItem
{

    /**
     *
     * @var Translator
     */
    protected static $i18n;

    /**
     * Identifies the object with a defined name in the view
     *
     * @var string
     */
    public $id;

    /**
     * Name defined in the view as key
     *
     * @var string
     */
    public $name;

    /**
     *
     * @var int
     */
    protected static $uniqueId = -1;

    /**
     *
     */
    public function __construct($data)
    {
        if (!isset(static::$i18n)) {
            static::$i18n = new Translator();
        }

        $this->id = $data['id'] ?? '';
        $this->name = $data['name'] ?? '';
    }

    /**
     *
     * @param string $color
     * @param string $prefix
     *
     * @return string
     */
    protected function colorToClass($color, $prefix)
    {
        switch ($color) {
            case 'danger':
            case 'dark':
            case 'info':
            case 'light':
            case 'primary':
            case 'secondary':
            case 'success':
            case 'warning':
                return $prefix . $color;
        }

        return '';
    }

    /**
     *
     * @return int
     */
    protected function getUniqueId()
    {
        static::$uniqueId++;
        return static::$uniqueId;
    }

    /**
     *
     * @param array  $button
     * @param bool   $small
     * @param string $viewName
     * @param string $jsFunction
     *
     * @return string
     */
    protected function renderRowButton($button, $small = false, $viewName = '', $jsFunction = '')
    {
        $cssClass = $small ? 'btn mr-1 ' : 'btn btn-sm mr-1 ';
        $cssClass .= isset($button['color']) ? $this->colorToClass($button['color'], 'btn-') : 'btn-light';
        $icon = isset($button['icon']) ? '<i class="fas ' . $button['icon'] . ' fa-fw"></i> ' : '';
        $title = isset($button['label']) ? static::$i18n->trans($button['label']) : '';
        $label = $small ? '' : $title;
        $divID = isset($button['id']) ? ' id="' . $button['id'] . '"' : '';

        if (!isset($button['type']) || !isset($button['action'])) {
            return '';
        }

        if ($button['type'] === 'modal') {
            return '<button type="button"' . $divID . ' class="' . $cssClass . '" data-toggle="modal" data-target="#modal'
                . $button['action'] . '" title="' . $title . '">' . $icon . $label . '</button>';
        }

        if ($button['type'] === 'js') {
            return '<button type="button"' . $divID . ' class="' . $cssClass . '" onclick="' . $button['action']
                . '" title="' . $title . '">' . $icon . $label . '</button>';
        }

        /// type action
        if (empty($jsFunction)) {
            return '<button type="submit"' . $divID . ' name="action" value="' . $button['action'] . '" class="'
                . $cssClass . '" title="' . $title . '">' . $icon . $label . '</button>';
        }

        return '<button type="button"' . $divID . ' class="' . $cssClass . '" onclick="' . $jsFunction
            . '(\'' . $viewName . '\',\'' . $button['action'] . '\');" title="' . $title . '">' . $icon . $label . '</button>';
    }
}
