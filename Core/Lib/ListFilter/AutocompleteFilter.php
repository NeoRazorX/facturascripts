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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Model\CodeModel;

/**
 * Description of AutocompleteFilter
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AutocompleteFilter extends BaseFilter
{

    /**
     * @var string
     */
    public $fieldcode;

    /**
     * @var string
     */
    public $fieldtitle;

    /**
     * @var string
     */
    public $table;

    /**
     * @var array
     */
    public $where;

    /**
     * @param string $key
     * @param string $field
     * @param string $label
     * @param string $table
     * @param string $fieldcode
     * @param string $fieldtitle
     * @param array $where
     */
    public function __construct(string $key, string $field, string $label, string $table, string $fieldcode = '', string $fieldtitle = '', array $where = [])
    {
        parent::__construct($key, $field, $label);
        $this->autosubmit = true;
        $this->table = $table;
        $this->fieldcode = empty($fieldcode) ? $this->field : $fieldcode;
        $this->fieldtitle = empty($fieldtitle) ? $this->fieldcode : $fieldtitle;
        $this->where = $where;
    }

    /**
     * @param array $where
     *
     * @return bool
     */
    public function getDataBaseWhere(array &$where): bool
    {
        if ('' !== $this->value && null !== $this->value) {
            $where[] = new DataBaseWhere($this->field, $this->value);
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $label = static::$i18n->trans($this->label);
        $html = '<div class="col-sm-3 col-lg-2">'
            . '<input type="hidden" name="' . $this->name() . '" value="' . $this->value . '"/>'
            . '<div class="mb-3">'
            . '<div class="input-group">';

        if ('' === $this->value || null === $this->value) {
            $html .= '<span class="input-group-text">'
                . '<i class="fa-solid fa-search fa-fw" aria-hidden="true"></i>'
                . '</span>';
        } else {
            $html .= '<button class="btn btn-spin-action btn-warning" type="button" onclick="this.form.' . $this->name() . '.value = \'\'; this.form.onsubmit(); this.form.submit();">'
                . '<i class="fa-solid fa-times fa-fw" aria-hidden="true"></i>'
                . '</button>';
        }

        $html .= '<input type="text" value="' . $this->getDescription() . '" class="form-control filter-autocomplete"'
            . ' data-name="' . $this->name() . '" data-field="' . $this->field . '" data-source="' . $this->table . '" data-fieldcode="' . $this->fieldcode
            . '" data-fieldtitle="' . $this->fieldtitle . '" placeholder = "' . $label . '" autocomplete="off" ' . $this->readonly() . '/>'
            . '</div>'
            . '</div>'
            . '</div>';

        return $html;
    }

    /**
     * Adds need asset to the asset manager.
     */
    protected function assets()
    {
        AssetManager::addCss(FS_ROUTE . '/node_modules/jquery-ui-dist/jquery-ui.min.css', 2);
        AssetManager::addJs(FS_ROUTE . '/node_modules/jquery-ui-dist/jquery-ui.min.js', 2);
        AssetManager::addJs(FS_ROUTE . '/Dinamic/Assets/JS/ListFilterAutocomplete.js');
    }

    /**
     * @return string
     */
    protected function getDescription()
    {
        $codeModel = new CodeModel();
        return $codeModel->getDescription($this->table, $this->fieldcode, $this->value, $this->fieldtitle);
    }
}
