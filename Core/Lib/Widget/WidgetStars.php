<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013 Carlos Garcia Gomez <carlos@facturascripts.com>
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

class WidgetStars extends WidgetNumber
{
    public function __construct($data)
    {
        parent::__construct($data);
        $this->decimal = 1;
        $this->icon = 'fa-solid fa-star';
        $this->max = 5;
        $this->min = 0;
        $this->step = 0.5;
    }

    public function tableCell($model, $display = 'left')
    {
        $this->setValue($model);
        $class = $this->combineClasses($this->tableCellClass('text-' . $display), $this->class);

        return '<td class="' . $class . '" title="' . $this->value . '">'
            . $this->onclickHtml($this->getStars())
            . '</td>';
    }

    private function getStars(): string
    {
        // añadimos una estrella por cada valor
        $html = str_repeat('<i class="fa-solid fa-star"></i>', (int)$this->value);

        // añadimos media estrella si el valor es decimal
        if ($this->value - floor($this->value) > 0) {
            $html .= '<i class="fa-solid fa-star-half-alt"></i>';
        }

        // añadimos estrellas vacías hasta llegar al máximo
        for ($i = ceil($this->value); $i < $this->max; $i++) {
            $html .= '<i class="far fa-star"></i>';
        }

        return $html;
    }
}
