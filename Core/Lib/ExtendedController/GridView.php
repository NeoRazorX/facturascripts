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
namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Base;

/**
 * Description of GridView
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class GridView extends BaseView
{
    private $gridData;

    /**
     * EditView constructor and initialization.
     *
     * @param string $title
     * @param string $modelName
     * @param string $viewName
     * @param string $userNick
     */
    public function __construct($title, $modelName, $viewName, $userNick)
    {
        parent::__construct($title, $modelName);

        // Loads the view configuration for the user
        $this->pageOption->getForUser($viewName, $userNick);
    }

    /**
     * Returns the pointer to the data model
     *
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }

    public function getGridData()
    {
        return json_encode($this->gridData);
    }

    private function getColumns()
    {
        $data = [
            'headers' => [],
            'columns' => [],
            'hidden'  => []
        ];

        $columns = $this->pageOption->columns['root']->columns;
        foreach ($columns as $col) {
            $item = [
                'data' => $col->widget->fieldName,
                'type' => $col->widget->type,
            ];

            if ($item['type'] === 'number' || $item['type'] === 'money') {
                $item['type'] = 'numeric';
                $item['format'] = Base\DivisaTools::gridMoneyFormat();
            }

            switch ($col->display) {
                case 'none':
                    $data['hidden'][] = $item;
                    break;

                default:
                    $data['headers'][] = self::$i18n->trans($col->title);
                    $data['columns'][] = $item;
                    break;
            }
        }

        return $data;
    }

    /**
     * Load the data in the cursor property, according to the where filter specified.
     * Adds an empty row/model at the end of the loaded data.
     *
     * @param mixed           $code
     * @param DataBaseWhere[] $where
     * @param array           $order
     */
    public function loadData($code = false, $where = [], $order = [])
    {
        // load columns configuration
        $this->gridData = $this->getColumns();

        // load model data
        $this->gridData['rows'] = [];
        $count = $this->model->count($where);
        if ($count > 0) {
            foreach ($this->model->all($where, $order, 0, 0) as $line) {
                $this->gridData['rows'][] = (array) $line;
            }
        }
    }
}
