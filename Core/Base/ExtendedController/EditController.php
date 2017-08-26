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

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Model;

/**
 * Controlador para edición de datos
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditController extends Base\Controller
{

    /**
     * Modelo con los datos a mostrar
     * @var mixed
     */
    public $model;
    
    /**
     * Configuración de columnas y filtros
     * @var Model\PageOption
     */
    private $pageOption;

    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        $this->setTemplate("Master/EditController");
        $this->pageOption = new Model\PageOption();
    }

    /**
     * Ejecuta la lógica privada del controlador.
     */
    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);

        // Cargamos configuración de columnas y filtros
        $className = $this->getClassName();
        $this->pageOption->getForUser($className, $user->nick);

        // Cargamos datos del modelo
        $value = $this->request->get('code');
        $this->model->loadFromCode($value);

        // Bloqueamos el campo Primary Key si no es una alta
        $fieldName = $this->model->primaryColumn();
        $column = $this->pageOption->columnForField($fieldName);
        $column->widget->readOnly = (!empty($this->model->{$fieldName}));
        
        // Comprobamos si hay operaciones por realizar
        if ($this->request->isMethod('POST')) {
            $this->setActionForm();
        }                
    }

    /**
     * Aplica la acción solicitada por el usuario
     */
    private function setActionForm()
    {
        $data = $this->request->request->all();
        if (isset($data['action'])) {        
            switch ($data['action']) {
                case 'save':
                    $this->model->checkArrayData($data);
                    $this->model->loadFromData($data);
                    if ($this->model->save()) {
                        $this->miniLog->notice($this->i18n->trans('Record updated correctly!'));
                    }
                    break;

                case 'insert':
                    $this->model->{$this->model->primaryColumn()} = $this->model->newCode();
                    break;

                default:
                    break;
            }
        }
    }
    
    /**
     * Devuelve el texto para la cabecera del panel principal de datos
     *
     * @return string
     */
    public function getPanelHeader()
    {
        return $this->i18n->trans('Datos generales');
    }

    /**
     * Devuelve el texto para el pie del panel principal de datos
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
}
