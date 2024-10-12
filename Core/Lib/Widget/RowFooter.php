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

use FacturaScripts\Core\Html;
use FacturaScripts\Core\Tools;

/**
 * Description of RowFooter
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class RowFooter extends VisualItem
{

    /**
     * @var array
     */
    protected $children;

    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->children = $data['children'];
    }

    public function addButton(array $btnArray)
    {
        if (!isset($btnArray['tag'])) {
            $btnArray['tag'] = 'button';
        }

        $group = $btnArray['row'];
        $this->children[$group]['children'][] = $btnArray;
    }

    /**
     * @param string $viewName
     * @param string $jsFunction
     * @param object $controller
     *
     * @return string
     */
    public function render(string $viewName, string $jsFunction = '', &$controller = null): string
    {
        $html = '';
        foreach ($this->children as $child) {
            if ($child['tag'] === 'group') {
                $html .= $this->renderGroup($child, $viewName, $jsFunction, $controller);
            }
        }

        if (empty($jsFunction)) {
            return '<form method="post" onsubmit="animateSpinner(\'add\')">'
                . '<input type="hidden" name="action"/>'
                . '<input type="hidden" name="activetab" value="' . $viewName . '"/>'
                . $html
                . '</form>';
        }

        return $html;
    }

    protected function renderCardFooter(array $group): string
    {
        if (isset($group['footer'])) {
            return '<div class="card-footer">' . Tools::lang()->trans($group['footer']) . '</div>';
        }

        return '';
    }

    protected function renderCardHeader(array $group): string
    {
        if (isset($group['title'])) {
            return '<div class="card-header">' . Tools::lang()->trans($group['title']) . '</div>';
        }

        return '';
    }

    /**
     * @param array $group
     * @param string $viewName
     * @param string $jsFunction
     * @param object $controller
     *
     * @return string
     */
    protected function renderGroup(array $group, string $viewName, string $jsFunction, &$controller = null): string
    {
        $colClass = isset($group['numcolumns']) ? 'col-sm-' . $group['numcolumns'] : 'col';
        $class = isset($group['class']) ? ' ' . $group['class'] : '';
        $divID = empty($group['id']) ? '' : ' id="' . $group['id'] . '"';
        $html = '<div' . $divID . ' class="' . $colClass . $class . '">'
            . '<div class="card shadow">'
            . $this->renderCardHeader($group)
            . '<div class="card-body">';

        foreach ($group['children'] as $child) {
            if ($child['tag'] === 'button') {
                $button = new RowButton($child);
                $html .= $button->render(false, $viewName, $jsFunction);
            }
        }

        if (isset($group['html'])) {
            $html .= Html::render($group['html'], ['fsc' => $controller]);
        }

        if (isset($group['label'])) {
            $html .= '<p>' . Tools::lang()->trans($group['label']) . '</p>';
        }

        $html .= '</div>'
            . $this->renderCardFooter($group)
            . '</div>'
            . '</div>';

        return $html;
    }
}
