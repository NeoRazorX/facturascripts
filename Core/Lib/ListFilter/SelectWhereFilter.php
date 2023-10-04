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

namespace FacturaScripts\Core\Lib\ListFilter;

/**
 * Selection filter of options where each option has a DataBaseWhere
 * associated for data filtering
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class SelectWhereFilter extends SelectFilter
{
    public function __construct(string $key, array $values = [], string $label = '')
    {
        parent::__construct($key, '', $label, $values);
    }

    public function getDataBaseWhere(array &$where): bool
    {
        $value = ($this->value == '' || $this->value == null) ? 0 : $this->value;
        foreach ($this->values[$value]['where'] as $condition) {
            $where[] = $condition;
        }

        return ($value > 0);
    }

    protected function getHtmlOptions(): string
    {
        $html = '';
        foreach ($this->values as $key => $data) {
            $extra = ('' != $this->value && $key == $this->value) ? ' selected' : '';
            $html .= '<option value="' . $key . '"' . $extra . '>' . $data['label'] . '</option>';
        }

        return $html;
    }
}
