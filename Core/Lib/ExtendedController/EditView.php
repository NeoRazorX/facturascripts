<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Lib\ExportManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * View definition for its use in ExtendedControllers
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class EditView extends BaseView
{

    const EDIT_TEMPLATE = 'Master/EditView.html.twig';
    const READONLY_TEMPLATE = 'Master/EditReadOnlyView.html.twig';

    /**
     * EditView constructor and initialization.
     *
     * @param string $name
     * @param string $title
     * @param string $modelName
     * @param string $icon
     */
    public function __construct($name, $title, $modelName, $icon)
    {
        parent::__construct($name, $title, $modelName, $icon);
        $this->template = self::EDIT_TEMPLATE;
    }

    /**
     * Method to export the view data.
     *
     * @param ExportManager $exportManager
     *
     * @return bool
     */
    public function export(&$exportManager): bool
    {
        return $exportManager->addModelPage($this->model, $this->getColumns(), $this->title);
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
    public function loadData($code = '', $where = [], $order = [], $offset = 0, $limit = \FS_ITEM_LIMIT)
    {
        if ($this->newCode !== null) {
            $code = $this->newCode;
        }

        if (empty($code) && empty($where)) {
            return;
        }

        if ($this->model->loadFromCode($code, $where, $order)) {
            $this->count = 1;
        }
    }

    /**
     *
     * @param Request $request
     * @param string  $case
     */
    public function processFormData($request, $case)
    {
        switch ($case) {
            case 'edit':
                foreach ($this->getColumns() as $group) {
                    $group->processFormData($this->model, $request);
                }
                break;

            case 'load':
                $exclude = ['action', 'code', 'option'];
                foreach ($request->query->all() as $key => $value) {
                    if (!in_array($key, $exclude)) {
                        $this->model->{$key} = $value;
                    }
                }
                break;
        }
    }

    /**
     * Allows you to set the view as read only
     *
     * @param bool $readOnly
     */
    public function setReadOnly(bool $readOnly)
    {
        $this->template = $readOnly ? self::READONLY_TEMPLATE : self::EDIT_TEMPLATE;
    }
}
