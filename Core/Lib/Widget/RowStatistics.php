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

/**
 * Description of RowStatistics
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class RowStatistics extends VisualItem
{

    /** @var array */
    protected $children;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->children = $data['children'];
    }

    /**
     * @param object $controller
     *
     * @return string
     */
    public function render(&$controller): string
    {
        $html = '';
        foreach ($this->children as $child) {
            if ($child['tag'] === 'datalabel') {
                $html .= $this->renderDataLabel($controller, $child);
            }
        }

        return $html;
    }

    /**
     * @param object $controller
     * @param array $data
     *
     * @return string
     */
    protected function renderDataLabel(&$controller, array $data): string
    {
        $color = isset($data['color']) ? $this->colorToClass($data['color'], 'btn-') : 'btn-light';
        $icon = isset($data['icon']) ? '<i class="' . $data['icon'] . ' fa-fw"></i> ' : '';
        $label = isset($data['label']) ? Tools::lang()->trans($data['label']) : '';
        $link = $data['link'] ?? '#';
        $divID = empty($data['id']) ? '' : ' id="' . $data['id'] . '"';
        $class = empty($data['class']) ? '' : $data['class'];

        if (!isset($data['function'])) {
            return ' ERROR';
        }

        $value = method_exists($controller, $data['function']) ? $controller->{$data['function']}() : '-';
        return ' <a href="' . $link . '"' . $divID . ' class="btn ' . $color . ' ' . $class . ' mb-2">' . $icon . $label . ' ' . $value . '</a>';
    }
}
