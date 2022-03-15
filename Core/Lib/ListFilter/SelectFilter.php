<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
     * @var string
     */
    public $icon = '';

    /**
     * @var array
     */
    public $values;

    public function __construct(string $key, string $field, string $label, array $values = [])
    {
        parent::__construct($key, $field, $label);
        $this->autosubmit = true;
        $this->values = $values;
    }

    public function getDataBaseWhere(array &$where): bool
    {
        if ('' !== $this->value && null !== $this->value) {
            $where[] = new DataBaseWhere($this->field, $this->value);
            return true;
        }

        return false;
    }

    public function render(): string
    {
        if (empty($this->icon)) {
            return '<div class="col-sm-3 col-lg-2">'
                . '<div class="form-group">'
                . '<select name="' . $this->name() . '" class="form-control"' . $this->onChange() . $this->readonly()
                . ' title="' . static::$i18n->trans($this->label) . '">' . $this->getHtmlOptions()
                . '</select>'
                . '</div>'
                . '</div>';
        }

        return '<div class="col-sm-3 col-lg-2">'
            . '<div class="form-group">'
            . '<div class="input-group" title="' . static::$i18n->trans($this->label) . '">'
            . '<span class="input-group-prepend">'
            . '<span class="input-group-text">'
            . '<i class="' . $this->icon . ' fa-fw" aria-hidden="true"></i>'
            . '</span>'
            . '</span>'
            . '<select name="' . $this->name() . '" class="form-control"' . $this->onChange() . $this->readonly() . '>'
            . $this->getHtmlOptions()
            . '</select>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    protected function getHtmlOptions(): string
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
