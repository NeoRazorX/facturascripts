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
 * Description of RowStatistics
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class RowStatistics
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
     * @param object $controller
     *
     * @return string
     */
    public function render(&$controller)
    {
        $html = '';
        foreach ($this->children as $child) {
            if ($child['tag'] === 'datalabel') {
                $html .= $this->renderDatalabel($controller, $child);
            }
        }

        return $html;
    }

    /**
     * 
     * @param object $controller
     * @param array  $data
     *
     * @return string
     */
    protected function renderDatalabel(&$controller, $data)
    {
        $color = isset($data['color']) ? $data['color'] : 'light';
        $icon = isset($data['icon']) ? '<i class="fas ' . $data['icon'] . ' fa-fw"></i> ' : '';
        $label = isset($data['label']) ? static::$i18n->trans($data['label']) : '';
        $link = isset($data['link']) ? $data['link'] : '#';

        if (!isset($data['function'])) {
            return ' ERROR';
        }

        $value = method_exists($controller, $data['function']) ? $controller->{$data['function']}() : '-';
        return ' <a href="' . $link . '" class="btn btn-' . $color . '">' . $icon . $label . ' ' . $value . '</a>';
    }
}
