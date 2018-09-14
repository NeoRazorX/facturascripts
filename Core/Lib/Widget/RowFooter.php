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

use FacturaScripts\Core\App\WebRender;
use FacturaScripts\Core\Base\Translator;

/**
 * Description of RowFooter
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class RowFooter
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
     * @param string $viewName
     * @param string $jsFunction
     *
     * @return string
     */
    public function render($viewName, $jsFunction = '')
    {
        $html = '';
        foreach ($this->children as $child) {
            if ($child['tag'] === 'group') {
                $html .= $this->renderGroup($child, $viewName, $jsFunction);
            }
        }

        if (empty($jsFunction)) {
            return '<form method="post">'
                . '<input type="hidden" name="activetab" value="' . $viewName . '"/>'
                . $html
                . '</form>';
        }

        return $html;
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

        if (!isset($button['type']) || !isset($button['action'])) {
            return '';
        }

        if ($button['type'] === 'modal') {
            return '<button type="button" class="btn btn-' . $color . '" data-toggle="modal" data-target="#modal'
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
     * @param array $group
     *
     * @return string
     */
    protected function renderCardFooter($group)
    {
        if (isset($group['footer'])) {
            return '<div class="card-footer">' . static::$i18n->trans($group['footer']) . '</div>';
        }

        return '';
    }

    /**
     * 
     * @param array $group
     *
     * @return string
     */
    protected function renderCardHeader($group)
    {
        if (isset($group['title'])) {
            return '<div class="card-header">' . static::$i18n->trans($group['title']) . '</div>';
        }

        return '';
    }

    /**
     * 
     * @param string $group
     * @param string $viewName
     * @param string $jsFunction
     *
     * @return string
     */
    protected function renderGroup($group, $viewName, $jsFunction)
    {
        $colClass = isset($group['numcolumns']) ? 'col-sm-' . $group['numcolumns'] : 'col';
        $html = '<div class="' . $colClass . '">'
            . '<div class="card">'
            . $this->renderCardHeader($group)
            . '<div class="card-body">';

        foreach ($group['children'] as $child) {
            if ($child['tag'] === 'button') {
                $html .= $this->renderButton($child, $viewName, $jsFunction);
            }
        }

        if (isset($group['html'])) {
            $webRender = new WebRender();
            $html .= $webRender->render($group['html']);
        }

        if (isset($group['label'])) {
            $html .= '<p>' . static::$i18n->trans($group['label']) . '</p>';
        }

        $html .= '</div>'
            . $this->renderCardFooter($group)
            . '</div>'
            . '</div>';

        return $html;
    }
}
