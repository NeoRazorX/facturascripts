<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

class WidgetSeconds extends WidgetNumber
{
    protected function show()
    {
        if (is_null($this->value)) {
            return '-';
        }

        // por debajo de 60, mostramos los segundos
        if ($this->value < 60) {
            return Tools::number($this->value) . ' s';
        }

        // por debajo de 3600, mostramos los minutos
        if ($this->value < 3600) {
            return Tools::number($this->value / 60) . ' m';
        }

        // por debajo de 86400, mostramos las horas
        if ($this->value < 86400) {
            return Tools::number($this->value / 3600) . ' h';
        }

        // mostramos los días
        return Tools::number($this->value / 86400) . ' d';
    }
}
