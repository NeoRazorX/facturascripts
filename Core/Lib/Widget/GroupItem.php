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

use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\User;

/**
 * Description of GroupItem
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class GroupItem
{

    /**
     * Define the columns that the group includes
     *
     * @var ColumnItem[]
     */
    public $columns = [];

    /**
     *
     * @var Translator
     */
    protected static $i18n;

    /**
     * Icon used as the value or accompaining the group title
     *
     * @var string
     */
    public $icon;

    /**
     *
     * @var string
     */
    public $name;

    /**
     *
     * @var int
     */
    public $numcolumns;

    /**
     *
     * @var int
     */
    public $order;

    /**
     *
     * @var string
     */
    public $title;

    /**
     * 
     * @param array $data
     */
    public function __construct($data)
    {
        if (!isset(static::$i18n)) {
            static::$i18n = new Translator();
        }

        $this->icon = isset($data['icon']) ? $data['icon'] : '';
        $this->name = $data['name'];
        $this->numcolumns = isset($data['numcolumns']) ? (int) $data['numcolumns'] : 0;
        $this->order = isset($data['order']) ? (int) $data['order'] : 0;
        $this->title = isset($data['title']) ? $data['title'] : '';
        $this->loadColumns($data['children']);
    }

    /**
     * 
     * @param object    $model
     * @param User|bool $user
     *
     * @return string
     */
    public function edit($model, $user = false)
    {
        $divClass = ($this->numcolumns > 0) ? 'col-md-' . $this->numcolumns : 'col';
        $html = '<div class="' . $divClass . '"><div class="form-row">';

        if (!empty($this->title)) {
            $icon = empty($this->icon) ? '' : '<i class="fas ' . $this->icon . ' fa-fw"></i> ';
            $html .= '<legend>' . $icon . static::$i18n->trans($this->title) . '</legend>';
        }

        foreach ($this->columns as $col) {
            if ($col->hiddeTo($user)) {
                continue;
            }

            $html .= $col->edit($model);
        }

        $html .= '</div><br/></div>';
        return $html;
    }

    /**
     * Sorts the columns
     *
     * @param ColumnItem $column1
     * @param ColumnItem $column2
     *
     * @return int
     */
    public static function sortColumns($column1, $column2)
    {
        if ($column1->order === $column2->order) {
            return 0;
        }

        return ($column1->order < $column2->order) ? -1 : 1;
    }

    /**
     * 
     * @param array $children
     */
    protected function loadColumns($children)
    {
        foreach ($children as $child) {
            if ($child['tag'] !== 'column') {
                continue;
            }

            $columnItem = new ColumnItem($child);
            $this->columns[$columnItem->name] = $columnItem;
        }

        uasort($this->columns, ['self', 'sortColumns']);
    }
}
