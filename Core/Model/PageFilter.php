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
namespace FacturaScripts\Core\Model;

/**
 * Visual filter configuration of the FacturaScripts views,
 * each PageFilter corresponds to a view or tab filter.
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class PageFilter extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Human description
     *
     * @var string
     */
    public $description;

    /**
     * Definition of filters values
     *
     * @var array
     */
    public $filters;

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
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->filters = [];
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

        return parent::install();
    }

    /**
     * Load the data from an array
     *
     * @param array $data
     * @param array $exclude
     */
    public function loadFromData(array $data = [], array $exclude = [])
    {
        array_push($exclude, 'filters', 'code', 'action');
        parent::loadFromData($data, $exclude);

        $this->filters = isset($data['filters']) ? json_decode($data['filters'], true) : [];
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
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'pages_filters';
    }

    public function test()
    {
        $this->description = $this->toolBox()->utils()->noHtml($this->description);
        if (empty($this->description)) {
            $this->toolBox()->i18nLog()->warning('description-error');
            return false;
        }

        return parent::test();
    }

    /**
     * Returns the values of the view configuration fields in JSON format
     *
     * @return array
     */
    private function getEncodeValues()
    {
        return [
            'filters' => json_encode($this->filters)
        ];
    }

    /**
     * Insert the model data in the database.
     *
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        return parent::saveInsert($this->getEncodeValues());
    }

    /**
     * Update the model data in the database.
     *
     * @param array $values
     *
     * @return bool
     */
    protected function saveUpdate(array $values = [])
    {
        return parent::saveUpdate($this->getEncodeValues());
    }
}
