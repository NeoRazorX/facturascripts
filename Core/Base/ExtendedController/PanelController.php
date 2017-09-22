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
        
        // Lanzamos cada una de las vistas
        foreach ($this->views as $key => $view) {
            switch ($view->viewType) {
                case 'list':
                    break;

                case 'edit':
                    break;                
            }
        }
        
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
        switch ($this->request->get('action')) {
            case 'export':
                $this->setTemplate(false);
                $view = $this->views[$this->active];
                $document = $view->export($this->exportManager, $this->request->get('option'));                                
                $this->response->setContent($document);
                break;
        }
    }
    
    /**
     * Crea y añade una vista al controlador.
     *
     * @param string $modelName
     * @param string $viewName
     * @param string $viewTitle
     */
    private function addView($viewType, $modelName, $viewName, $viewTitle)
    {
        switch ($viewType) {
            case 'list':
                $this->views[$viewName] = new ListView($viewTitle, $modelName, $viewName, $this->user->nick);
                break;

            case 'edit':
                $this->views[$viewName] = new EditView($viewTitle, $modelName, $viewName, $this->user->nick);
                break;
            
            default:
                break;
        }
        
        if (empty($this->active)) {
            $this->active = $viewName;
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
        $this->addView('list', $modelName, $viewName, $viewTitle);
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
        $this->addView('edit', $modelName, $viewName, $viewTitle);
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
