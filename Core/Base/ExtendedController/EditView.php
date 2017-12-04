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

namespace FacturaScripts\Core\Base\ExtendedController;

use FacturaScripts\Core\Lib\ExportManager;

/**
 * View definition for its use in ExtendedControllers
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditView extends BaseView
{
    /**
     * Class constructor and initialization
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
     * Returns the text for the data panel header
     * Returns the
     *
     * @return string
     */
    public function getPanelHeader()
    {
        return $this->title;
    }

    /**
     * Returns the text for the data panel footer
     *
     *
     * @return string
     */
    public function getPanelFooter()
    {
        return '';
    }

    /**
     * Returns the column configuration
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->pageOption->columns;
    }

    /**
     * Establishes the column edit state
     * 
     * @param string $columnName
     * @param boolean $disabled
     */
    public function disableColumn($columnName, $disabled)
    {
        $column = $this->columnForName($columnName);
        if (!empty($column)) {
            $column->widget->readOnly = $disabled;
        }
    }

    /**
     * Establishes and loads the model data according to its Primary Key
     *
     * @param string|array $code
     */
    public function loadData($code)
    {
        $this->model->loadFromCode($code);

        $fieldName = $this->model->primaryColumn();
        $this->count = empty($this->model->{$fieldName}) ? 0 : 1;

        // Bloqueamos el campo Primary Key si no es una alta
        $column = $this->columnForField($fieldName);
        if (!empty($column)) {
            $column->widget->readOnly = (!empty($this->model->{$fieldName}));
        }
    }

    /**
     * Method to export the view data
     *
     * @param ExportManager $exportManager
     */
    public function export(&$exportManager)
    {
        $exportManager->generateModelPage($this->model, $this->getColumns(), $this->title);
    }
}
