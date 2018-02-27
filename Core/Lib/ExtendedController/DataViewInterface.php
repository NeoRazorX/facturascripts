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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExportManager;

/**
 * TODO: Uncomplete documentation
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
interface DataViewInterface
{

    /**
     * Column list and its configuration
     * (Array of ColumnItem)
     *
     * @return GroupItem[]
     */
    public function getColumns();

    /**
     * Establishes de view/edit state of a column
     *
     * @param string $columnName
     * @param bool   $disabled
     */
    public function disableColumn($columnName, $disabled);

    /**
     * Load the data in the cursor property, according to the where filter specified.
     * Adds an empty row/model at the end of the loaded data.
     *
     * @param mixed           $code
     * @param DataBaseWhere[] $where
     * @param array           $order
     * @param int             $offset
     * @param int             $limit
     */
    public function loadData($code = false, $where = [], $order = [], $offset = 0, $limit = FS_ITEM_LIMIT);

    /**
     * Method to export the view data
     *
     * @param ExportManager $exportManager
     *
     * @return null
     */
    public function export(&$exportManager);
}
