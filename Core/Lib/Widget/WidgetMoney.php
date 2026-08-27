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

use FacturaScripts\Core\DataSrc\Divisas;
use FacturaScripts\Core\Tools;

/**
 * Widget para campos de importe, declarado con type="money" en las vistas XMLView.
 * Amplía WidgetNumber formateando el valor con Tools::money() según la divisa del modelo,
 * o la divisa por defecto cuando este no tiene el campo coddivisa. Los campos cuyo nombre
 * contiene euros se muestran siempre en euros.
 * Asigna automáticamente el icono correspondiente al símbolo de la divisa y activa por
 * defecto los totales de columna, salvo que se indique lo contrario con el atributo totals.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class WidgetMoney extends WidgetNumber
{
    protected $coddivisa;

    /** @param array $data */
    public function __construct($data)
    {
        parent::__construct($data);
        if (false === isset($data['totals'])) {
            $this->showTotals = true;
        }
    }

    /** @param object $model */
    protected function setValue($model)
    {
        parent::setValue($model);

        $this->coddivisa = $model->coddivisa ?? Tools::settings('default', 'coddivisa') ?? 'EUR';

        if ('' === $this->icon) {
            $simbol = Divisas::get($this->coddivisa)->simbolo;
            switch ($simbol) {
                case '€':
                    $this->icon = 'fa-solid fa-euro-sign';
                    break;

                case 'Q':
                    $this->icon = 'fa-brands fa-quora';
                    break;

                default:
                    $this->icon = 'fa-solid fa-dollar-sign';
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
            ? Tools::money($this->value, 'EUR', $this->decimal)
            : Tools::money($this->value, $this->coddivisa, $this->decimal);
    }
}
