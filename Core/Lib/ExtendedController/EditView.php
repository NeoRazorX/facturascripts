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
use Symfony\Component\HttpFoundation\Request;

/**
 * View definition for its use in ExtendedControllers
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditView extends BaseView
{

    /**
     * EditView constructor and initialization.
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
        $this->template = 'Master/EditView.html.twig';
    }

    /**
     * Method to export the view data.
     *
     * @param ExportManager $exportManager
     */
    public function export(&$exportManager)
    {
        $exportManager->generateModelPage($this->model, $this->getColumns(), $this->title);
    }

    /**
     * Load the data in the model property, according to the code specified.
     *
     * @param string          $code
     * @param DataBaseWhere[] $where
     * @param array           $order
     * @param int             $offset
     * @param int             $limit
     */
    public function loadData($code = '', $where = [], $order = [], $offset = 0, $limit = FS_ITEM_LIMIT)
    {
        if ($this->newCode !== null) {
            $code = $this->newCode;
        }

        if (is_array($code)) {
            $where = [];
            foreach ($code as $fieldName => $value) {
                $where[] = new DataBaseWhere($fieldName, $value);
            }
            $this->model->loadFromCode('', $where, $order);
        } else {
            $this->model->loadFromCode($code);
        }

        $fieldName = $this->model->primaryColumn();
        $this->count = empty($this->model->{$fieldName}) ? 0 : 1;
    }

    /**
     * Process need request data.
     *
     * @param Request $request
     */
    public function processRequest($request)
    {
        ;
    }
}
