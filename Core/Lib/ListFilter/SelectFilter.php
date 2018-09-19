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
namespace FacturaScripts\Core\Lib\ListFilter;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Description of SelectFilter
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class SelectFilter extends BaseFilter
{

    /**
     *
     * @var array
     */
    public $values;

    /**
     * 
     * @param string $key
     * @param string $field
     * @param string $label
     * @param array  $values
     */
    public function __construct($key, $field, $label, $values = [])
    {
        parent::__construct($key, $field, $label);
        $this->values = $values;
    }

    /**
     * 
     * @param array $where
     *
     * @return array
     */
    public function getDataBaseWhere(array &$where)
    {
        if ('' !== $this->value && null !== $this->value) {
            $where[] = new DataBaseWhere($this->key, $this->value);
        }

        return $where;
    }

    /**
     * 
     * @return string
     */
    public function render()
    {
        return '<div class="col-sm-2">'
            . '<div class="form-group">'
            . '<select name="' . $this->name() . '" class="form-control" onchange="this.form.submit()">'
            . $this->getHtmlOptions()
            . '</select>'
            . '</div>'
            . '</div>';
    }

    /**
     * 
     * @return string
     */
    protected function getHtmlOptions()
    {
        $html = '<option value="">' . static::$i18n->trans($this->label) . '</option>';
        foreach ($this->values as $data) {
            if (is_array($data)) {
                $extra = ('' != $this->value && $data['code'] == $this->value) ? ' selected=""' : '';
                $html .= '<option value="' . $data['code'] . '"' . $extra . '>' . $data['description'] . '</option>';
                continue;
            }

            $extra = ('' != $this->value && $data->code == $this->value) ? ' selected=""' : '';
            $html .= '<option value="' . $data->code . '"' . $extra . '>' . $data->description . '</option>';
        }

        return $html;
    }
}
