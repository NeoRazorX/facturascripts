<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib\ExtendedController;

/**
 * Description of GroupItem
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class GroupItem extends VisualItem implements VisualItemInterface
{

    /**
     * Define the columns that the group includes
     *
     * @var ColumnItem[]
     */
    public $columns;

    /**
     * Icon used as the value or accompaining the group title
     *
     * @var string
     */
    public $icon;

    /**
     * Class construct and initialization
     */
    public function __construct()
    {
        parent::__construct();

        $this->icon = null;
        $this->columns = [];
    }

    /**
     * Check and apply special operations on the group
     */
    public function applySpecialOperations()
    {
        foreach ($this->columns as $column) {
            $column->applySpecialOperations();
        }
    }

    /**
     * Generates the HTML code to display the visual element's header
     *
     * @param string $value
     *
     * @return string
     */
    public function getHeaderHTML($value)
    {
        return $this->getIconHTML() . parent::getHeaderHTML($value);
    }

    /**
     * Loads the attributes structure from a JSON file
     *
     * @param array $group
     */
    public function loadFromJSON($group)
    {
        parent::loadFromJSON($group);
        $this->icon = (string) $group['icon'];

        foreach ($group['columns'] as $column) {
            $columnItem = ColumnItem::newFromJSON($column);
            $this->columns[$columnItem->name] = $columnItem;
        }
        uasort($this->columns, ['self', 'sortColumns']);
    }

    /**
     * Loads the attributes structure from a XML file
     *
     * @param \SimpleXMLElement $group
     */
    public function loadFromXML($group)
    {
        parent::loadFromXML($group);

        $group_atributes = $group->attributes();
        $this->icon = (string) $group_atributes->icon;
        $this->loadFromXMLColumns($group);
    }

    /**
     * Loads the groups from the columns
     *
     * @param \SimpleXMLElement $group
     */
    public function loadFromXMLColumns($group)
    {
        if (isset($group->column)) {
            foreach ($group->column as $column) {
                $columnItem = ColumnItem::newFromXML($column);
                $this->columns[$columnItem->name] = $columnItem;
            }
            uasort($this->columns, ['self', 'sortColumns']);
        }
    }

    /**
     * Create and load the group structure from the database
     *
     * @param array $group
     *
     * @return GroupItem
     */
    public static function newFromJSON($group)
    {
        $result = new self();
        $result->loadFromJSON($group);

        return $result;
    }

    /**
     * Create and load the group structure from a XML file
     *
     * @param \SimpleXMLElement $group
     *
     * @return GroupItem
     */
    public static function newFromXML($group)
    {
        $result = new self();
        $result->loadFromXML($group);

        return $result;
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
     * Returns the HTML code to display an icon
     *
     * @return string
     */
    private function getIconHTML()
    {
        if (empty($this->icon)) {
            return '';
        }

        if (strpos($this->icon, 'fa-') === 0) {
            return '<i class="fa ' . $this->icon . '" aria-hidden="true">&nbsp;&nbsp;</i></span>';
        }

        return '<i aria-hidden="true">' . $this->icon . '</i>&nbsp;&nbsp;</span>';
    }
}
