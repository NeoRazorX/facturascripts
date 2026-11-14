<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Widget para campos de porcentaje, declarado con type="percentage" en las vistas XMLView.
 * Amplía WidgetNumber añadiendo el símbolo de porcentaje al valor mostrado y asignando por
 * defecto el icono de porcentaje, que puede sustituirse con el atributo icon.
 * El valor se guarda tal cual en el modelo, sin dividir entre cien.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Raúl Jiménez Jiménez <raljopa@gmail.com>
 */
class WidgetPercentage extends WidgetNumber
{
    /**
     * @param array $data
     */
    public function __construct($data)
    {
        parent::__construct($data);
        $this->icon = $data['icon'] ?? 'fa-solid fa-percentage';
    }

    /**
     * @return string
     */
    protected function show()
    {
        return is_null($this->value) ? '-' : Tools::number($this->value, $this->decimal) . '%';
    }
}
