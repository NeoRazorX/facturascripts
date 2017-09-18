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

use FacturaScripts\Core\Model as Model;

/**
 * Definición de vista para uso en ListController
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditView
{
    /**
     * Modelo con los datos a mostrar
     *
     * @var mixed
     */
    public $model;

    /**
     * Configuración de columnas y filtros
     *
     * @var Model\PageOption
     */
    private $pageOption;
    
    /**
     * Título identificativo de la vista
     * 
     * @var string
     */
    public $title;
    
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
    
        $this->title = $title;
        $this->model = new $modelName;
    
        // Carga configuración de la vista para el usuario
        $this->pageOption = new Model\PageOption();
        $this->pageOption->getForUser($viewName, $userNick);        
    }
    
    /**
     * Establece y carga los datos del modelo en base a su PK
     * 
     * @param string $code
     */
    public function setCode($code)
    {
        $this->model->loadFromCode($code);
        
        // Bloqueamos el campo Primary Key si no es una alta
        $fieldName = $this->model->primaryColumn();
        $column = $this->pageOption->columnForField($fieldName);
        $column->widget->readOnly = (!empty($this->model->{$fieldName}));
    }

    /**
     * Calcula y establece un nuevo código para la PK del modelo
     */
    public function setNewCode()
    {
        $this->model->{$this->model->primaryColumn()} = $this->model->newCode();        
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
     * Si existe, devuelve el tipo de row especificado
     *
     * @param string $key
     *
     * @return RowItem
     */
    public function getRow($key)
    {
        return empty($this->pageOption->rows) ? NULL : $this->pageOption->rows[$key];
    }

    /**
     * Devuelve la configuración de columnas
     *
     * @return array
     */
    public function getGroupColumns()
    {
        return $this->pageOption->columns;
    }
    
    /**
     * Verifica la estructura y carga en el modelo los datos informados en un array
     * 
     * @param array $data
     */
    public function loadFromData(&$data)
    {
        $this->model->checkArrayData($data);
        $this->model->loadFromData($data);        
    }
    
    /**
     * Persiste los datos del modelo en la base de datos
     * 
     * @return boolean
     */
    public function save()
    {
        return $this->model->save();
    }    
}
