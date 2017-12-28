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
namespace FacturaScripts\Core\Lib\Dashboard;

use FacturaScripts\Core\Model;

/**
 * Description of InfoStateComponent
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class InfoStateComponent extends BaseComponent implements ComponentInterface
{
    /**
     *
     * @var array
     */
    public $group;

    /**
     *
     * @var array
     */
    public $detail;

    /**
     * InfoStateComponent constructor.
     *
     * @param Model\DashboardData $data
     * @param string $userNick
     */
    public function __construct($data, $userNick)
    {
        parent::__construct($data, NULL);
        $this->group = [];
        $this->detail = [];
    }

    /**
     * Sets the special fields for the component and their initial values.
     *
     * @return array
     */
    public static function getPropertiesFields()
    {
        return [
            'group' => '',
            'model' => '',
            'icon' => '',
            'values' => []
        ];
    }

    /**
     * Load data of component for user to put into dashboard
     */
    public function loadData()
    {
        $where = $this->getDataFilter();
        $orderBy = $this->getDataOrderBy();

        $model = new Model\DashboardData();
        $rows = $model->all($where, $orderBy);

        if (empty($rows)) {
            InfoStateInitialData::generateData($this);
            $rows = $model->all($where, $orderBy);
        }

        foreach ($rows as $data) {
            $modelInfo = $this->getModelInfo($data->properties['model']);
            $totalModel = $this->getSQLData($modelInfo['table'], $data->properties['values']);

            $this->group[$data->properties['group']] = [
                'icon' => $data->properties['icon'],
                'value' => $totalModel->totals['total'],
                'url' => $modelInfo['url']
            ];

            $this->addDetail($data->properties['group'], $data->properties['values'], $totalModel);
        }
    }

    private function addDetail($group, $values, &$totalModel)
    {
        foreach ($values as $value) {
            $name = str_replace('-', '', $value['name']);
            if ($name === 'total') {
                continue;
            }
            $this->detail[$group][$value['name']] = $totalModel->totals[$name];
        }
    }

    private function getModelInfo($modelName)
    {
        $model = self::DIR_MODEL . $modelName;
        $modelObj = new $model();
        return ['table' => $modelObj->tableName(), 'url' => $modelObj->url('list')];
    }

    private function getSQLData($table, $values)
    {
        $fields = [];
        foreach ($values as $value) {
            $name = str_replace('-', '', $value['name']);
            $fields[$name] = $value['sql'];
        }
        return Model\TotalModel::all($table, [], $fields)[0];
    }

    public function saveData($data)
    {
        $newItem = new Model\DashboardData();
        $newItem->component = 'InfoState';
        $newItem->nick = NULL;

        if (isset($data['id'])) {
            $newItem->id = $data['id'];
        }

        $newItem->properties = [
            'group' => $data['group'],
            'model' => $data['model'],
            'icon' => $data['icon'],
            'values' => $data['values']
        ];

        $newItem->save();
    }

    public function url($id)
    {
        return $this->group[$id]['url'];
    }
}
