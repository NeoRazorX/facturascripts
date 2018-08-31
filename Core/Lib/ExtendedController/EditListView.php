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
namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExportManager;

/**
 * View definition for its use in ExtendedControllers
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditListView extends BaseView
{

    /**
     * Class constructor and initialization
     *
     * @param string $name
     * @param string $title
     * @param string $modelName
     * @param string $viewName
     * @param string $icon
     */
    public function __construct($name, $title, $modelName, $viewName, $icon)
    {
        parent::__construct($name, $title, $modelName, $icon);
        $this->template = 'Master/EditListView.html.twig';

        // Load the view configuration for the user
        ///$this->pageOption->getForUser($viewName, $userNick);
    }

    /**
     * Method to export the view data.
     *
     * @param ExportManager $exportManager
     */
    public function export(&$exportManager)
    {
        if ($this->count > 0) {
            $exportManager->generateListModelPage(
                $this->model, $this->where, $this->order, $this->offset, $this->getColumns(), $this->title
            );
        }
    }

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
    public function loadData($code = false, $where = [], $order = [], $offset = 0, $limit = FS_ITEM_LIMIT)
    {
        $this->order = empty($order) ? $this->order : $order;
        $this->count = $this->model->count($where);
        if ($this->count > 0) {
            $this->cursor = $this->model->all($where, $this->order, $offset, $limit);
        }

        // We save the values where and offset for the export
        $this->offset = $offset;
        $this->where = $where;
    }
}
