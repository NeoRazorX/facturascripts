<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\DocumentReportsBase;

use FacturaScripts\Core\Model;
use FacturaScripts\Core\Base\DataBase;

/**
 * Description of DocumentReportsFilterList
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class DocumentReportsFilterList
{

    /**
     * Structure data from
     */
    private $model;

    /**
     * Code value selected from list
     *
     * @var string
     */
    public $selectedValue;

    /**
     * Icon for select input
     *
     * @var string
     */
    public $icon;

    /**
     * List of posibles values
     *
     * @var array
     */
    public $listValues;

    /**
     * DocumentReportsFilterList constructor.
     *
     * @param string $modelName
     * @param string $selectedValue
     * @param string $icon
     * @param bool $allowEmpty
     */
    public function __construct($modelName, $selectedValue = '', $icon = 'fas fa-list', $allowEmpty = true)
    {
        $this->model = new $modelName();
        $this->selectedValue = $selectedValue;
        $this->icon = $icon;
        $this->loadValuesFromModel($allowEmpty);
    }

    /**
     * Load requires values from model.
     *
     * @param bool $allowEmpty
     */
    private function loadValuesFromModel($allowEmpty = true)
    {
        $tableName = $this->model->tableName();
        $fieldCode = $this->model->primaryColumn();
        $fieldDesc = $this->model->primaryDescriptionColumn();
        $rows = Model\CodeModel::all($tableName, $fieldCode, $fieldDesc, $allowEmpty);

        $this->listValues = $allowEmpty ? ['' => '------'] : [];
        foreach ($rows as $data) {
            $this->listValues[$data->code] = $data->description;
        }
        unset($rows);
    }

    /**
     * Return DataBaseWhere with needed filter.
     *
     * @param DataBaseWhere[] $where
     * @return bool
     */
    public function getWhere(&$where): bool
    {
        if (empty($this->selectedValue)) {
            return false;
        }

        $where[] = new DataBase\DataBaseWhere($this->model->primaryColumn(), $this->selectedValue);
        return true;
    }
}
