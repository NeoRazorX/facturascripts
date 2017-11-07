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

/**
 * Controlador para edición de datos
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditController extends Base\Controller
{
    /**
     * Objeto para exportar datos
     *
     * @var Base\ExportManager
     */
    public $exportManager;

    /**
     * Vista mostrada por el controlador
     *
     * @var EditView
     */
    public $view;

    /**
     * Nombre del modelo de datos
     *
     * @var string
     */
    protected $modelName;

    /**
     * Inicia todos los objetos y propiedades.
     *
     * @param Base\Cache      $cache
     * @param Base\Translator $i18n
     * @param Base\MiniLog    $miniLog
     * @param string     $className
     */
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        $this->setTemplate('Master/EditController');
        $this->exportManager = new Base\ExportManager();
    }

    /**
     * Ejecuta la lógica privada del controlador.
     *
     * @param mixed $response
     * @param mixed $user
     */
    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);

        // Creamos la vista a visualizar
        $viewName = $this->getClassName();
        $title = $this->getPageData()['title'];
        $this->view = new EditView($title, $this->modelName, $viewName, $user->nick);

        // Guardamos si hay operaciones por realizar
        $action = $this->request->get('action', '');

        // Operaciones sobre los datos antes de leerlos
        $this->execPreviousAction($action);

        // Cargamos datos del modelo
        $value = $this->request->get('code');
        $this->view->loadData($value);

        // Operaciones generales con los datos cargados
        $this->execAfterAction($action);
    }

    /**
     * Ejecuta las acciones que alteran los datos antes de leerlos
     *
     * @param string $action
     */
    private function execPreviousAction($action)
    {
        switch ($action) {
            case 'save':
                $data = $this->request->request->all();
                $this->view->loadFromData($data);
                $this->editAction();
                break;
        }
    }

    /**
     * Ejecuta las acciones del controlador
     *
     * @param string $action
     */
    private function execAfterAction($action)
    {
        switch ($action) {
            case 'insert':
                $this->insertAction();
                break;

            case 'export':
                $this->setTemplate(false);
                $document = $this->view->export($this->exportManager, $this->response, $this->request->get('option'));
                $this->response->setContent($document);
                break;
        }
    }

    /**
     * Devuelve el valor de un campo para el modelo de datos cargado
     *
     * @param mixed $model
     * @param string $field
     * @return mixed
     */
    public function getFieldValue($model, $field)
    {
        return $model->{$field};
    }

    /**
     * Ejecuta la modificación de los datos
     *
     * @return boolean
     */
    protected function editAction()
    {
        if ($this->view->save()) {
            $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));
            return true;
        }
        return false;
    }

    /**
     * Prepara la inserción de un nuevo registro
     */
    protected function insertAction()
    {
        
    }

    /**
     * Devuelve el texto para la cabecera del panel principal de datos
     *
     * @return string
     */
    public function getPanelHeader()
    {
        return $this->i18n->trans('general-data');
    }

    /**
     * Devuelve el texto para el pie del panel principal de datos
     *
     * @return string
     */
    public function getPanelFooter()
    {
        return !empty($this->view->getPanelFooter()) ? $this->i18n->trans($this->view->getPanelFooter()) : '';
    }

    /**
     * Puntero al modelo de datos
     *
     * @return mixed
     */
    public function getModel()
    {
        return $this->view->getModel();
    }

    /**
     * Devuelve la url para el tipo indicado
     *
     * @param string $type
     * @return string
     */
    public function getURL($type)
    {
        return $this->view->getURL($type);
    }
}
