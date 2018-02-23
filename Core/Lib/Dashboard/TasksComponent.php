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
 * Description of TasksComponent
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class TasksComponent extends BaseComponent implements ComponentInterface
{

    /**
     * List of tasks
     *
     * @var Model\DashboardData[]
     */
    public $tasks;

    /**
     * List of completed tasks
     *
     * @var Model\DashboardData[]
     */
    public $completed;

    /**
     * TasksComponent constructor.
     *
     * @param Model\DashboardData $data
     * @param string              $userNick
     */
    public function __construct($data, $userNick)
    {
        parent::__construct($data, $userNick);
        $this->tasks = [];
        $this->completed = [];
    }

    /**
     * Sets the special fields for the component and their initial values.
     *
     * @return array
     */
    public static function getPropertiesFields()
    {
        return [
            'description' => '',
            'color' => 'info',
            'enddate' => null,
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
            $this->generateRandomData(10, 10);
            $rows = $model->all($where, $orderBy);
        }

        foreach ($rows as $data) {
            if (empty($data->properties['enddate'])) {
                $this->tasks[] = $data;
                continue;
            }
            $this->completed[] = $data;
        }
    }

    /**
     * Data persists in the database, modifying if the record existed or inserting
     * in case the primary key does not exist.
     *
     * @param array $data
     */
    public function saveData($data)
    {
        $newItem = new Model\DashboardData();
        $newItem->component = 'Tasks';
        $newItem->nick = $this->nick;

        if (isset($data['id'])) {
            $newItem->id = $data['id'];
            $newItem->displaydate = $data['displaydate'];
            $newItem->nick = $data['nick'];
        }

        if ($this->randomData) {
            $data['enddate'] = (mt_rand(0, 2) == 0) ? date('Y-m-d') : null;
        }

        $newItem->properties = [
            'color' => $data['color'],
            'description' => $data['description'],
            'enddate' => $data['enddate'],
        ];

        $newItem->save();
    }

    /**
     * Return the URL to this component.
     *
     * @param string $id
     *
     * @return string
     */
    public function url($id)
    {
        return 'EditDashboardData?code=' . $id;
    }

    /**
     * Return the class for render the card.
     *
     * @return string
     */
    public function getCardClass()
    {
        return 'task-card';
    }
}
