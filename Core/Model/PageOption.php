<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Visual configuration of the FacturaScripts views,
 * each PageOption corresponds to a controller.
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class PageOption extends Base\ModelClass
{

    use Base\ModelTrait {
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
    public static function tableName()
    {
        return 'pages_options';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
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
        parent::clear();
        $this->columns = [];
        $this->modals = [];
        $this->filters = [];
        $this->rows = [];
    }

    /**
     * Load the data from an array
     *
     * @param array $data
     * @param array $exclude
     */
    public function loadFromData(array $data = [], array $exclude = [])
    {
        array_push($exclude, 'columns', 'modals', 'filters', 'rows', 'code', 'action');
        $this->traitLoadFromData($data, $exclude);

        $columns = json_decode($data['columns'], true);
        $modals = json_decode($data['modals'], true);
        $rows = json_decode($data['rows'], true);
        ExtendedController\VisualItemLoadEngine::loadJSON($columns, $modals, $rows, $this);
    }

    /**
     * Returns the values of the view configuration fields in JSON format
     *
     * @return array
     */
    private function getEncodeValues()
    {
        return [
            'columns' => json_encode($this->columns),
            'modals' => json_encode($this->modals),
            'filters' => json_encode($this->filters),
            'rows' => json_encode($this->rows),
        ];
    }

    /**
     * Insert the model data in the database.
     *
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert($values = [])
    {
        $values = $this->getEncodeValues();

        return parent::saveInsert($values);
    }

    /**
     * Update the model data in the database.
     *
     * @param array $values
     *
     * @return bool
     */
    protected function saveUpdate($values = [])
    {
        $values = $this->getEncodeValues();

        return parent::saveUpdate($values);
    }

    /**
     * Returns the where filter to locate the view configuration
     *
     * @param string $name
     * @param string $nick
     *
     * @return Database\DataBaseWhere[]
     */
    private function getPageFilter($name, $nick)
    {
        return [
            new DataBase\DataBaseWhere('nick', $nick),
            new DataBase\DataBaseWhere('name', $name),
            new DataBase\DataBaseWhere('nick', 'NULL', 'IS', 'OR'),
            new DataBase\DataBaseWhere('name', $name),
        ];
    }

    /**
     * Get the settings for the driver and user
     *
     * @param string $name
     * @param string $nick
     */
    public function getForUser($name, $nick)
    {
        $where = $this->getPageFilter($name, $nick);
        $orderby = ['nick' => 'ASC'];

        // Load data from database, if not exist install xmlview
        if (!$this->loadFromCode('', $where, $orderby)) {
            $this->name = $name;

            if (!ExtendedController\VisualItemLoadEngine::installXML($name, $this)) {
                self::$miniLog->critical(self::$i18n->trans('error-processing-xmlview', ['%fileName%' => $name]));

                return;
            }
        }

        /// Apply values to dynamic Select widgets
        ExtendedController\VisualItemLoadEngine::applyDynamicSelectValues($this);
    }
}
