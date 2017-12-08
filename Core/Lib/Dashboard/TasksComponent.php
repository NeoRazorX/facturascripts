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
     *
     * @var Model\DashboardData[]
     */
    public $tasks;

    /**
     *
     * @var Model\DashboardData[]
     */
    public $completed;

    /**
     *
     * @param Model\DashboardData $data
     * @param string $userNick
     */
    public function __construct($data, $userNick)
    {
        parent::__construct($data, $userNick);
        $this->tasks = [];
        $this->completed = [];
    }

    public function loadData()
    {
        $where = $this->getDataFilter();
        $orderBy = $this->getDataOrderBy();

        $model = new Model\DashboardData();
        $rows = $model->all($where, $orderBy);

        if (empty($rows)) {
            $this->genetareRandomData(10, 10);
            $rows = $model->all($where, $orderBy);
        }

        foreach ($rows as $data) {
            if ($data->properties['completed']) {
                $this->completed[] = $data;
                continue;
            }

            $this->tasks[] = $data;
        }
    }

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
            $data['completed'] = (mt_rand(0, 2) == 0);
        }

        $newItem->properties = [
                'color' => $data['color'],
                'description' => $data['description'],
                'completed' => $data['completed']
        ];

        $newItem->save();
    }

    public function url($id)
    {
        return 'index.php?page=EditDashboardData&code=' . $id;
    }

    public function getNumColumns()
    {
        return "col-4";
    }

    public function getCardClass()
    {
        return "task-card";
    }
}
