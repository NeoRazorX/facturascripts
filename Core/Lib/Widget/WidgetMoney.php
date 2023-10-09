<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DivisaTools;

/**
 * Description of WidgetMoney
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class WidgetMoney extends WidgetNumber
{
    /** @var DivisaTools */
    protected static $divisaTools;

    /** @param array $data */
    public function __construct($data)
    {
        if (!isset(static::$divisaTools)) {
            static::$divisaTools = new DivisaTools();
        }

        parent::__construct($data);
    }

    public function showTableTotals(): bool
    {
        return true;
    }

    /** @param object $model */
    protected function setValue($model)
    {
        parent::setValue($model);
        static::$divisaTools->findDivisa($model);

        if ('' === $this->icon) {
            $simbol = static::$divisaTools->getSymbol();
            switch ($simbol) {
                case '€':
                    $this->icon = 'fas fa-euro-sign';
                    break;

                case 'Q':
                    $this->icon = 'fab fa-quora';
                    break;

                default:
                    $this->icon = 'fas fa-dollar-sign';
                    break;
            }
        }
    }

    protected function show(): string
    {
        if (is_null($this->value)) {
            return '-';
        }

        return (false !== stripos($this->fieldname, 'euros'))
            ? static::$divisaTools->format($this->value, $this->decimal, '€')
            : static::$divisaTools->format($this->value, $this->decimal);
    }
}
