<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Description of RowBusiness
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class RowBusiness extends VisualItem
{

    /**
     * Define the columns that the group includes
     *
     * @var ColumnItem[]
     */
    protected $columns = [];

    /**
     *
     * @param array $data
     */
    public function __construct($data)
    {
        parent::__construct($data);
        $this->loadColumns($data['children']);
    }

    /**
     *
     * @param object $model
     *
     * @return string
     */
    public function edit($model)
    {
        $html = '';
        foreach ($this->columns as $col) {
            $html .= $col->edit($model);
        }

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

        return $column1->order < $column2->order ? -1 : 1;
    }

    /**
     *
     * @param array $children
     */
    protected function loadColumns($children)
    {
        $columnClass = VisualItemLoadEngine::getNamespace() . 'ColumnItem';
        foreach ($children as $child) {
            if ($child['tag'] !== 'column') {
                continue;
            }

            $columnItem = new $columnClass($child);
            $this->columns[$columnItem->name] = $columnItem;
        }

        \uasort($this->columns, ['self', 'sortColumns']);
    }
}
