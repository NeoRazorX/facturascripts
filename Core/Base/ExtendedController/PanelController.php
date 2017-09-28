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

        // Comprobamos si hay operaciones por realizar
        if ($this->request->get('action', false)) {
            $this->setActionForm();
        }
    }

    /**
     * Aplica la acción solicitada por el usuario
     */
    private function setActionForm()
    {
        if (empty($this->active)) {
            return;
        }

        $view = $this->views[$this->active];
        $data = $this->request->request->all();

        switch ($this->request->get('action')) {
            case 'save':
                $view->loadFromData($data);
                $this->editAction($view, $data);
                break;

            case 'insert':
                $this->insertAction($view, $data);
                break;

            case 'export':
                $this->setTemplate(false);
                $document = $view->export($this->exportManager, $this->request->get('option'));
                $this->response->setContent($document);
                break;
        }
    }

    /**
     * Devuelve el valor para un campo del modelo de datos de la vista
     * 
     * @param EditView $view
     * @param string $field
     * @return mixed
     */
    public function getFieldValue($view, $field)
    {
        return $view->getFieldValue($field);
    }

    /**
     * Devuelve la url para el tipo indicado
     * 
     * @param string $type
     * @return string
     */
    public function getURL($type)
    {
        $view = $this->views[$this->active];
        return $view->getURL($type);
    }
        
    /**
     * Ejecuta la modificación de los datos
     *
     * @param EditView $view
     * @param array $data
     * @return boolean
     */
    protected function editAction($view, $data)
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
     * @param array $data
     */
    protected function insertAction($view, $data)
    {
        $view->setNewCode();
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
        $this->loadData($keyView, $view);

        if (empty($this->active)) {
            $this->active = $keyView;
        }
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
