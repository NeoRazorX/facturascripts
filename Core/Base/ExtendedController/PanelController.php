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

/**
 * Controlador para edición de datos mediante panel vertical
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
abstract class PanelController extends Base\Controller
{

    /**
     * Indica cual es la vista activa
     *
     * @var string
     */
    public $active;

    /**
     *
     * @var Base\ExportManager
     */
    public $exportManager;

    /**
     * Lista de vistas mostradas por el controlador
     *
     * @var BaseView[]
     */
    public $views;

    /**
     * Lista de iconos para cada una de las vistas
     *
     * @var array
     */
    public $icons;

    /**
     * Procedimiento encargado de insertar las vistas a visualizar
     */
    abstract protected function createViews();

    /**
     * Procedimiento encargado de cargar los datos a visualizar
     *
     * @param string $keyView
     * @param BaseView $view
     */
    abstract protected function loadData($keyView, $view);

    /**
     * Inicia todos los objetos y propiedades.
     *
     * @param Cache      $cache
     * @param Translator $i18n
     * @param MiniLog    $miniLog
     * @param string     $className
     */
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        $this->setTemplate('Master/PanelController');
        $this->active = $this->request->get('active', '');
        $this->exportManager = new Base\ExportManager();
        $this->views = [];
        $this->icons = [];
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

        // Creamos las vistas a visualizar
        $this->createViews();

        // Guardamos si hay operaciones por realizar
        $view = empty($this->active) ? NULL : $this->views[$this->active];
        $action = empty($view) ? '' : $this->request->get('action', '');

        // Operaciones sobre los datos antes de leerlos
        $this->execPreviousAction($view, $action);

        // Lanzamos la carga de datos para cada una de las vistas
        foreach ($this->views as $keyView => $dataView) {
            $this->loadData($keyView, $dataView);
        }

        // Operaciones generales con los datos cargados
        $this->execAfterAction($view, $action);
    }

    /**
     * Devuelve el valor para un campo del modelo de datos de la vista
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
     * Devuelve la url para el tipo indicado
     *
     * @param string $type
     * @return string
     */
    public function getURL($type)
    {
        $view = array_values($this->views)[0];
        return $view->getURL($type);
    }

    /**
     * Ejecuta las acciones que alteran los datos antes de leerlos
     *
     * @param string $action
     */
    private function execPreviousAction($view, $action)
    {
        switch ($action) {
            case 'save':
                $data = $this->request->request->all();
                $view->loadFromData($data);
                $this->editAction($view);
                break;
            
            case 'delete':
                $this->deleteAction($view);
                break;
        }
    }

    /**
     * Ejecuta las acciones del controlador
     *
     * @param string $action
     */
    private function execAfterAction($view, $action)
    {
        switch ($action) {
            case 'insert':
                $this->insertAction($view);
                break;

            case 'export':
                $this->setTemplate(false);
                $document = $view->export($this->exportManager, $this->response, $this->request->get('option'));
                $this->response->setContent($document);
                break;
        }
    }

    /**
     * Ejecuta la modificación de los datos
     *
     * @param EditView $view
     * @return boolean
     */
    protected function editAction($view)
    {
        if ($view->save()) {
            $this->miniLog->notice($this->i18n->trans('Record updated correctly!'));
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Prepara la inserción de un nuevo registro
     *
     * @param EditView $view
     */
    protected function insertAction($view)
    {
        $view->setNewCode();
    }

    /**
     * Acción de borrado de datos
     *
     * @param  BaseView $view
     * @return boolean
     */
    protected function deleteAction($view)
    {
        if ($view->delete($this->request->get('primarykey'))) {
            $this->miniLog->notice($this->i18n->trans('record-deleted-correctly'));
            return TRUE;
        }
        return FALSE;
    }
    
    /**
     * Añade una vista al controlador y carga sus datos.
     *
     * @param string $keyView
     * @param BaseView $view
     */
    private function addView($keyView, $view, $icon)
    {
        $this->views[$keyView] = $view;
        $this->icons[$keyView] = $icon;

        if (empty($this->active)) {
            $this->active = $keyView;
        }
    }

    /**
     * Añade una vista tipo EditList al controlador.
     *
     * @param string $modelName
     * @param string $viewName
     * @param string $viewTitle
     * @param string $viewIcon
     */
    protected function addEditListView($modelName, $viewName, $viewTitle, $viewIcon = 'fa-bars')
    {
        $view = new EditListView($viewTitle, $modelName, $viewName, $this->user->nick);
        $this->addView($viewName, $view, $viewIcon);
    }

    /**
     * Añade una vista tipo List al controlador.
     *
     * @param string $modelName
     * @param string $viewName
     * @param string $viewTitle
     * @param string $viewIcon
     */
    protected function addListView($modelName, $viewName, $viewTitle, $viewIcon = 'fa-bars')
    {
        $view = new ListView($viewTitle, $modelName, $viewName, $this->user->nick);
        $this->addView($viewName, $view, $viewIcon);
    }

    /**
     * Añade una vista tipo Edit al controlador.
     *
     * @param string $modelName
     * @param string $viewName
     * @param string $viewTitle
     * @param string $viewIcon
     */
    protected function addEditView($modelName, $viewName, $viewTitle, $viewIcon = 'fa-list-alt')
    {
        $view = new EditView($viewTitle, $modelName, $viewName, $this->user->nick);
        $this->addView($viewName, $view, $viewIcon);
    }

    /**
     * Añade una vista tipo Html al controlador.
     *
     * @param string $fileName
     * @param string $modelName
     * @param string $viewName
     * @param string $viewTitle
     * @param string $viewIcon
     */
    protected function addHtmlView($fileName, $modelName, $viewName, $viewTitle, $viewIcon = 'fa-html5')
    {
        $view = new HtmlView($viewTitle, $modelName, $fileName);
        $this->addView($viewName, $view, $viewIcon);
    }

    /**
     * Devuelve la clase de la vista
     *
     * @param string $view
     * @return string
     */
    public function viewClass($view)
    {
        $result = explode('\\', get_class($view));
        return end($result);
    }
}
