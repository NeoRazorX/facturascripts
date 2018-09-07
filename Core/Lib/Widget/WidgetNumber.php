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

use FacturaScripts\Core\Base\NumberTools;

/**
 * Description of WidgetNumber
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class WidgetNumber extends BaseWidget
{

    /**
     *
     * @var NumberTools
     */
    protected static $numberTools;

    public function __construct($data)
    {
        if (!isset(static::$numberTools)) {
            static::$numberTools = new NumberTools();
        }

        parent::__construct($data);
    }

    public function show()
    {
        return is_null($this->value) ? '-' : static::$numberTools->format($this->value);
    }
}
