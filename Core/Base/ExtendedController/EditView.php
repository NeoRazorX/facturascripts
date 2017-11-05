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

use FacturaScripts\Core\Base;
use Symfony\Component\HttpFoundation\Response;

/**
 * Definición de vista para uso en ExtendedControllers
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditView extends BaseView
{
    /**
     * Constructor e inicializador de la clase
     *
     * @param string $title
     * @param string $modelName
     * @param string $viewName
     * @param string $userNick
     */
    public function __construct($title, $modelName, $viewName, $userNick)
    {
        parent::__construct($title, $modelName);

        // Carga configuración de la vista para el usuario
        $this->pageOption->getForUser($viewName, $userNick);
    }

    /**
     * Devuelve el texto para la cabecera del panel de datos
     *
     * @return string
     */
    public function getPanelHeader()
    {
        return $this->title;
    }

    /**
     * Devuelve el texto para el pie del panel de datos
     *
     * @return string
     */
    public function getPanelFooter()
    {
        return '';
    }

    /**
     * Devuelve la configuración de columnas
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->pageOption->columns;
    }

    /**
     * Establece el estado de edición de una columna
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
     * Establece y carga los datos del modelo en base a su PK
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
     * Método para la exportación de los datos de la vista
     *
     * @param Base\ExportManager $exportManager
     * @param Response $response
     * @param string $action
     *
     * @return mixed
     */
    public function export(&$exportManager, &$response, $action)
    {
        return $exportManager->generateDoc($response, $action, $this->model);
    }
}
