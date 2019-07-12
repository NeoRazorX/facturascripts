<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Description of WidgetDatetime
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class WidgetDatetime extends WidgetDate
{

    /**
     * 
     * @return string
     */
    protected function show()
    {
        if (is_null($this->value)) {
            return '-';
        }

        if (is_numeric($this->value)) {
            return date('d-m-Y H:i:s', $this->value);
        }

        return date('d-m-Y H:i:s', strtotime($this->value));
    }
}
