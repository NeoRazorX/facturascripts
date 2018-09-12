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
     * @param string $formName
     *
     * @return string
     */
    public function render($viewName, $formName = '')
    {
        $html = '';
        foreach ($this->children as $child) {
            if ($child['tag'] !== 'group') {
                continue;
            }

            $html .= $this->renderGroup($child);
        }

        if (empty($formName)) {
            return '<form method="post">'
                . '<input type="hidden" name="activetab" value="' . $viewName . '"/>'
                . $html
                . '</form>';
        }

        return $html;
    }

    /**
     * 
     * @param array $button
     *
     * @return string
     */
    protected function renderButton($button)
    {
        $color = isset($button['color']) ? $button['color'] : 'light';
        $icon = isset($button['icon']) ? '<i class="fas ' . $button['icon'] . ' fa-fw"></i> ' : '';
        $label = isset($button['label']) ? static::$i18n->trans($button['label']) : '';

        $onclick = '';
        switch ($button['type']) {
            case 'action':
                $onclick = ' onclick="alert(\'' . $button['action'] . '\');"';
                break;

            case 'modal':
                $onclick = ' data-toggle="modal" data-target="#modal' . $button['action'] . '"';
                break;
        }

        return '<button type="button" class="btn btn-' . $color . '"' . $onclick . '>' . $icon . $label . '</button>';
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
        if (isset($group['header'])) {
            return '<div class="card-footer">' . static::$i18n->trans($group['header']) . '</div>';
        }

        return '';
    }

    /**
     * 
     * @param array $group
     *
     * @return string
     */
    protected function renderGroup($group)
    {
        $html = '<div class="card">'
            . $this->renderCardHeader($group)
            . '<div class="card-body">';

        foreach ($group['children'] as $child) {
            if ($child['tag'] !== 'button') {
                continue;
            }

            $html .= $this->renderButton($child);
        }

        $html .= '</div>'
            . $this->renderCardFooter($group)
            . '</div>';

        return $html;
    }
}
