<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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

/**
 * Definición de vista para uso en ExtendedControllers
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditListView extends BaseView
{

    /**
     * Cursor con los datos del modelo a mostrar
     *
     * @var array
     */
    private $cursor;

    /**
     * Almacena el offset para el cursor
     * @var integer 
     */
    private $offset;

    /**
     * Almacena el order para el cursor
     * @var string 
     */
    private $order;

    /**
     * Almacena los parámetros del where del cursor
     * @var array 
     */
    private $where;

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

        $this->order = [$this->model->primaryColumn() => 'ASC'];
        $this->offset = 0;
        $this->where = [];

        // Carga configuración de la vista para el usuario
        $this->pageOption->getForUser($viewName, $userNick);
    }

    /**
     * Devuelve la lista de datos leidos en formato Model
     *
     * @return array
     */
    public function getCursor()
    {
        return $this->cursor;
    }
    
    /**
     * Lista de columnas y su configuración
     * (Array of ColumnItem)
     * @return array
     */
    public function getColumns()
    {
        return $this->pageOption->columns;
    }

    public function loadData($where, $offset = 0, $limit = 0)
    {
        $this->count = $this->model->count($where);
        if ($this->count > 0) {
            $this->cursor = $this->model->all($where, $this->order, $offset, $limit);
        }

        /// nos guardamos los valores where y offset para la exportación
        $this->offset = $offset;
        $this->where = $where;
    }
        
    public function export(&$exportManager, &$response, $action)
    {
        return $exportManager->generateList(
                $response, $action, $this->model, $this->where, $this->order, $this->offset, $this->getColumns());
    }
}
