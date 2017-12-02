<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\ExtendedController;

/**
 * Visual configuration of the FacturaScripts views,
 * each PageOption corresponds to a controller.
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class PageOption
{

    use Base\ModelTrait {
        clear as traitClear;
        loadFromData as traitLoadFromData;
    }

    /**
     * Identifier
     *
     * @var int
     */
    public $id;

    /**
     * Name of the page (controller).
     *
     * @var string
     */
    public $name;

    /**
     * User Identifier.
     *
     * @var string
     */
    public $nick;

    /**
     * Definition for special treatment of rows
     *
     * @var array
     */
    public $rows;

    /**
     * Definition of modal forms
     *
     * @var array
     */
    public $modals;

    /**
     * Definition of the columns. It is called columns but it always
     * contains GroupItem, which contains the columns.
     *
     * @var array
     */
    public $columns;

    /**
     * Defining custom filters
     *
     * @var array
     */
    public $filters;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public function tableName()
    {
        return 'fs_pages_options';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'id';
    }

    /**
     * This function is called when creating the model table.
     * Returns the SQL that will be executed after the creation of the table,
     * useful to insert default values.
     *
     * @return string
     */
    public function install()
    {
        new Page();
        new User();

        return '';
    }

    /**
     * Reset values of all model properties.
     */
    public function clear()
    {
        $this->traitClear();
        $this->columns = [];
        $this->modals = [];
        $this->filters = [];
        $this->rows = [];
    }

    /**
     * Load the column structure from the JSON
     *
     * @param \SimpleXMLElement $groups
     * @param array $target
     */
    private function getJSONGroupsColumns($groups, &$target)
    {
        if (!empty($groups)) {
            foreach ($groups as $item) {
                $groupItem = ExtendedController\GroupItem::newFromJSON($item);
                $target[$groupItem->name] = $groupItem;
                unset($groupItem);
            }
        }
    }

    /**
     * Load the data from an array
     *
     * @param array $data
     */
    public function loadFromData($data)
    {
        $this->traitLoadFromData($data, ['columns', 'modals', 'filters', 'rows']);

        $groups = json_decode($data['columns'], true);
        $this->getJSONGroupsColumns($groups, $this->columns);

        $modals = json_decode($data['modals'], true);
        $this->getJSONGroupsColumns($modals, $this->modals);

        $rows = json_decode($data['rows'], true);
        if (!empty($rows)) {
            foreach ($rows as $item) {
                $rowItem = ExtendedController\RowItem::newFromJSON($item);
                $this->rows[$rowItem->type] = $rowItem;
                unset($rowItem);
            }
        }
    }

    /**
     * Update the model data in the database.
     *
     * @return bool
     */
    private function saveUpdate()
    {
        $columns = json_encode($this->columns);
        $modals = json_encode($this->modals);
        $filters = json_encode($this->filters);
        $rows = json_encode($this->rows);

        $sql = 'UPDATE ' . $this->tableName() . ' SET columns = ' . $this->dataBase->var2str($columns)
            . ', modals = ' . $this->dataBase->var2str($modals)
            . ', filters = ' . $this->dataBase->var2str($filters)
            . ', rows = ' . $this->dataBase->var2str($rows)
            . '  WHERE id = ' . $this->id . ';';

        return $this->dataBase->exec($sql);
    }

    /**
     * Insert the model data in the database.
     *
     * @return bool
     */
    private function saveInsert()
    {
        $columns = json_encode($this->columns);
        $modals = json_encode($this->modals);
        $filters = json_encode($this->filters);
        $rows = json_encode($this->rows);

        $sql = 'INSERT INTO ' . $this->tableName()
            . ' (id, name, nick, columns, modals, filters, rows) VALUES ('
            . "nextval('fs_pages_options_id_seq')" . ','
            . $this->dataBase->var2str($this->name) . ','
            . $this->dataBase->var2str($this->nick) . ','
            . $this->dataBase->var2str($columns) . ','
            . $this->dataBase->var2str($modals) . ','
            . $this->dataBase->var2str($filters) . ','
            . $this->dataBase->var2str($rows)
            . ');';

        if ($this->dataBase->exec($sql)) {
            $lastVal = $this->dataBase->lastval();
            if ($lastVal === FALSE) {
                return false;
            }

            $this->id = $lastVal;
            return true;
        }

        return false;
    }

    /**
     * Load the column structure from the XML
     *
     * @param \SimpleXMLElement $columns
     * @param array $target
     */
    private function getXMLGroupsColumns($columns, &$target)
    {
        // if group dont have elements
        if ($columns->count() === 0) {
            return;
        }

        // if have elements but dont have groups
        if (!isset($columns->group)) {
            $groupItem = ExtendedController\GroupItem::newFromXML($columns);
            $target[$groupItem->name] = $groupItem;
            unset($groupItem);
            return;
        }

        // exists columns grouped
        foreach ($columns->group as $group) {
            $groupItem = ExtendedController\GroupItem::newFromXML($group);
            $target[$groupItem->name] = $groupItem;
            unset($groupItem);
        }
    }

    /**
     * Load the special conditions for the rows from XML file
     *
     * @param \SimpleXMLElement $rows
     */
    private function getXMLRows($rows)
    {
        if (!empty($rows)) {
            foreach ($rows->row as $row) {
                $rowItem = ExtendedController\RowItem::newFromXML($row);
                $this->rows[$rowItem->type] = $rowItem;
                unset($rowItem);
            }
        }
    }

    /**
     * Add to the configuration of a controller
     *
     * @param string $name
     */
    public function installXML($name)
    {
        if ($this->name != $name) {
            $this->miniLog->critical($this->i18n->trans('error-install-name-xmlview'));
            return;
        }

        $file = "Core/XMLView/{$name}.xml";
        /**
         * This can be affected by a PHP bug #62577 (https://bugs.php.net/bug.php?id=62577)
         * Reports 'simplexml_load_file(...)' calls, which may be affected by this PHP bug.
         * $xml = simplexml_load_file($file);
         */
        $xml = @simplexml_load_string(file_get_contents($file));

        if ($xml === false) {
            $this->miniLog->critical($this->i18n->trans('error-processing-xmlview', [$file]));
            return;
        }

        $this->getXMLGroupsColumns($xml->columns, $this->columns);
        $this->getXMLGroupsColumns($xml->modals, $this->modals);
        $this->getXMLRows($xml->rows);
    }

    /**
     * Get the settings for the driver and user
     *
     * @param string $name
     * @param string $nick
     */
    public function getForUser($name, $nick)
    {
        $where = [];
        $where[] = new DataBase\DataBaseWhere('nick', $nick);
        $where[] = new DataBase\DataBaseWhere('nick', 'NULL', 'IS', 'OR');
        $where[] = new DataBase\DataBaseWhere('name', $name);

        $orderby = ['nick' => 'ASC'];

        // Load data from database, if not exist install xmlview
        if (!$this->loadFromCode('', $where, $orderby)) {
            $this->name = $name;
            $this->columns = [];
            $this->modals = [];
            $this->filters = [];
            $this->rows = [];
            $this->installXML($name);
        }

        // Apply values to dynamic Select widgets
        $this->dynamicSelectValues($this->columns);

        // Apply values to dynamic Select widgets for modals forms
        if (!empty($this->modals)) {
            $this->dynamicSelectValues($this->modals);
        }
    }

    /**
     * Load the list of values for a dynamic select type widget with
     *  a database model or a range of values
     */
    private function dynamicSelectValues($items)
    {
        foreach ($items as $group) {
            $group->applySpecialOperations();
        }
    }
}
