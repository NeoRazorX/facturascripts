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
                EditView($view)->loadFromData($data);
                $this->editAction(EditView($view), $data);
                break;

            case 'insert':
                $this->insertAction(EditView($view), $data);
                break;

            case 'export':
                $this->setTemplate(false);
                $document = $view->export($this->exportManager, $this->request->get('option'));
                $this->response->setContent($document);
                break;
        }
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
    private function addView($keyView, $view)
    {
        $this->views[$keyView] = $view;
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
     */
    protected function addListView($modelName, $viewName, $viewTitle)
    {
        $view = new ListView($viewTitle, $modelName, $viewName, $this->user->nick);
        $this->addView($viewName, $view);
    }

    /**
     * Añade una vista tipo Edit al controlador.
     *
     * @param string $modelName
     * @param string $viewName
     * @param string $viewTitle
     */
    protected function addEditView($modelName, $viewName, $viewTitle)
    {
        $view = new EditView($viewTitle, $modelName, $viewName, $this->user->nick);
        $this->addView($viewName, $view);
    }

    public function viewClass($keyView)
    {
        $result = explode('\\', get_class($this->views[$keyView]));
        return end($result);
    }

    public function getPanelHeader()
    {
        return $this->i18n->trans('panel-text');
    }
}
