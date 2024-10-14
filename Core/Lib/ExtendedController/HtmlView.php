<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Request;
use FacturaScripts\Dinamic\Lib\ExportManager;
use FacturaScripts\Dinamic\Lib\Widget\VisualItemLoadEngine;
use FacturaScripts\Dinamic\Model\User;
use const FS_ITEM_LIMIT;

/**
 * View definition for its use in ExtendedControllers
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class HtmlView extends BaseView
{
    /**
     * HtmlView constructor and initialization.
     *
     * @param string $name
     * @param string $title
     * @param string $modelName
     * @param string $fileName
     * @param string $icon
     */
    public function __construct(string $name, string $title, string $modelName, string $fileName, string $icon)
    {
        parent::__construct($name, $title, $modelName, $icon);
        $this->template = $fileName . '.html.twig';
    }

    /**
     * Method to export the view data.
     *
     * @param ExportManager $exportManager
     * @param mixed $codes
     *
     * @return bool
     */
    public function export(&$exportManager, $codes): bool
    {
        return true;
    }

    /**
     * @param string $code
     * @param array  $where
     * @param array  $order
     * @param int    $offset
     * @param int    $limit
     */
    public function loadData($code = '', $where = [], $order = [], $offset = 0, $limit = FS_ITEM_LIMIT)
    {
        if (empty($code) && empty($where)) {
            return;
        }

        $this->model->loadFromCode($code, $where, $order);
        if (false === empty($where)) {
            $this->count = $this->model->count($where);
            $this->cursor = $this->model->all($where, $order, $offset, $limit);
        }
    }

    /**
     * @param User|false $user
     */
    public function loadPageOptions($user = false)
    {
        VisualItemLoadEngine::loadArray($this->columns, $this->modals, $this->rows, $this->pageOption);
    }

    /**
     * @param Request $request
     * @param string  $case
     */
    public function processFormData($request, $case)
    {
    }
}
