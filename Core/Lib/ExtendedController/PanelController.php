<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\User;

/**
 * Controller to edit data through the vertical panel
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
abstract class PanelController extends BaseController
{
    /**
     * Indicates if the main view has data or is empty.
     *
     * @var bool
     */
    public $hasData = false;

    /**
     * Tabs position in page: left, bottom.
     *
     * @var string
     */
    public $tabsPosition;

    /**
     * Starts all the objects and properties.
     *
     * @param string $className
     * @param string $uri
     */
    public function __construct(string $className, string $uri = '')
    {
        parent::__construct($className, $uri);
        $this->setTabsPosition('left');
    }

    public function getImageUrl(): string
    {
        return '';
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response $response
     * @param User $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        // Get any operations that have to be performed
        $action = $this->request->request->get('action', $this->request->query->get('action', ''));

        // Runs operations before reading data
        if ($this->execPreviousAction($action) === false || $this->pipeFalse('execPreviousAction', $action) === false) {
            return;
        }

        // Load the data for each view
        $mainViewName = $this->getMainViewName();
        foreach ($this->views as $viewName => $view) {
            // disable views if main view has no data
            if ($viewName != $mainViewName && false === $this->hasData) {
                $this->setSettings($viewName, 'active', false);
            }

            if (false === $view->settings['active']) {
                // exclude inactive views
                continue;
            } elseif ($this->active == $viewName) {
                $view->processFormData($this->request, 'load');
            } else {
                $view->processFormData($this->request, 'preload');
            }

            $this->loadData($viewName, $view);
            $this->pipeFalse('loadData', $viewName, $view);

            if ($viewName === $mainViewName && $view->model->exists()) {
                $this->hasData = true;
            }
        }

        // General operations with the loaded data
        $this->execAfterAction($action);
        $this->pipeFalse('execAfterAction', $action);
    }

    /**
     * Sets the tabs position, by default is set to 'left', also supported 'bottom', 'top' and 'left-bottom.
     *
     * @param string $position
     */
    public function setTabsPosition(string $position): void
    {
        $this->tabsPosition = $position;
        switch ($this->tabsPosition) {
            case 'bottom':
                $this->setTemplate('Master/PanelControllerBottom');
                break;

            case 'left-bottom':
                $this->setTemplate('Master/PanelControllerLeftBottom');
                break;

            case 'top':
                $this->setTemplate('Master/PanelControllerTop');
                break;

            default:
                $this->tabsPosition = 'left';
                $this->setTemplate('Master/PanelController');
        }

        foreach (array_keys($this->views) as $viewName) {
            $this->views[$viewName]->settings['card'] = $this->tabsPosition !== 'top';
        }
    }

    /**
     * Adds a EditList type view to the controller.
     *
     * @param string $viewName
     * @param string $modelName
     * @param string $viewTitle
     * @param string $viewIcon
     * @return EditListView
     */
    protected function addEditListView(string $viewName, string $modelName, string $viewTitle, string $viewIcon = 'fa-solid fa-bars'): EditListView
    {
        $view = new EditListView($viewName, $viewTitle, self::MODEL_NAMESPACE . $modelName, $viewIcon);
        $view->settings['card'] = $this->tabsPosition !== 'top';
        $this->addCustomView($viewName, $view);

        return $view;
    }

    /**
     * Adds an Edit type view to the controller.
     *
     * @param string $viewName
     * @param string $modelName
     * @param string $viewTitle
     * @param string $viewIcon
     * @return EditView
     */
    protected function addEditView(string $viewName, string $modelName, string $viewTitle, string $viewIcon = 'fa-solid fa-edit'): EditView
    {
        $view = new EditView($viewName, $viewTitle, self::MODEL_NAMESPACE . $modelName, $viewIcon);
        $view->settings['card'] = $this->tabsPosition !== 'top';
        $this->addCustomView($viewName, $view);

        return $view;
    }

    /**
     * Adds an HTML type view to the controller.
     *
     * @param string $viewName
     * @param string $fileName
     * @param string $modelName
     * @param string $viewTitle
     * @param string $viewIcon
     * @return HtmlView
     */
    protected function addHtmlView(string $viewName, string $fileName, string $modelName, string $viewTitle, string $viewIcon = 'fa-brands fa-html5'): HtmlView
    {
        $view = new HtmlView($viewName, $viewTitle, self::MODEL_NAMESPACE . $modelName, $fileName, $viewIcon);
        $this->addCustomView($viewName, $view);

        return $view;
    }

    /**
     * Adds a List type view to the controller.
     *
     * @param string $viewName
     * @param string $modelName
     * @param string $viewTitle
     * @param string $viewIcon
     * @return ListView
     */
    protected function addListView(string $viewName, string $modelName, string $viewTitle, string $viewIcon = 'fa-solid fa-list'): ListView
    {
        $view = new ListView($viewName, $viewTitle, self::MODEL_NAMESPACE . $modelName, $viewIcon);
        $view->settings['card'] = $this->tabsPosition !== 'top';
        $this->addCustomView($viewName, $view);

        return $view;
    }

    /**
     * Runs the data edit action.
     *
     * @return bool
     */
    protected function editAction()
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return false;
        } elseif (false === $this->validateFormToken()) {
            return false;
        }

        // loads model data
        $code = $this->request->request->get('code', '');
        if (!$this->views[$this->active]->model->loadFromCode($code)) {
            Tools::log()->error('record-not-found');
            return false;
        }

        // loads form data
        $this->views[$this->active]->processFormData($this->request, 'edit');

        // has PK value been changed?
        $this->views[$this->active]->newCode = $this->views[$this->active]->model->primaryColumnValue();
        if ($code !== $this->views[$this->active]->newCode && $this->views[$this->active]->model->test()) {
            $pkColumn = $this->views[$this->active]->model->primaryColumn();
            $this->views[$this->active]->model->{$pkColumn} = $code;
            // change in database
            if (!$this->views[$this->active]->model->changePrimaryColumnValue($this->views[$this->active]->newCode)) {
                Tools::log()->error('record-save-error');
                return false;
            }
        }

        // save in database
        if ($this->views[$this->active]->model->save()) {
            Tools::log()->notice('record-updated-correctly');
            return true;
        }

        Tools::log()->error('record-save-error');
        return false;
    }

    /**
     * Run the controller after actions.
     *
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'export':
                $this->exportAction();
                break;

            case 'save-ok':
                Tools::log()->notice('record-updated-correctly');
                break;

            case 'widget-library-search':
                $this->setTemplate(false);
                $results = $this->widgetLibrarySearchAction();
                $this->response->setContent(json_encode($results));
                break;

            case 'widget-library-upload':
                $this->setTemplate(false);
                $results = $this->widgetLibraryUploadAction();
                $this->response->setContent(json_encode($results));
                break;

            case 'widget-variante-search':
                $this->setTemplate(false);
                $results = $this->widgetVarianteSearchAction();
                $this->response->setContent(json_encode($results));
                break;
        }
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'autocomplete':
                $this->setTemplate(false);
                $results = $this->autocompleteAction();
                $this->response->setContent(json_encode($results));
                return false;

            case 'datalist':
                $this->setTemplate(false);
                $results = $this->datalistAction();
                $this->response->setContent(json_encode($results));
                return false;

            case 'delete':
            case 'delete-document':
                if ($this->deleteAction() && $this->active === $this->getMainViewName()) {
                    // al eliminar el registro principal, redirigimos al listado para mostrar ahí el mensaje de éxito
                    $listUrl = $this->views[$this->active]->model->url('list');
                    $redirect = strpos($listUrl, '?') === false ?
                        $listUrl . '?action=delete-ok' :
                        $listUrl . '&action=delete-ok';
                    $this->redirect($redirect);
                }
                break;

            case 'edit':
                if ($this->editAction()) {
                    $this->views[$this->active]->model->clear();
                }
                break;

            case 'insert':
                if ($this->insertAction() || !empty($this->views[$this->active]->model->primaryColumnValue())) {
                    // we need to clear model in these scenarios
                    $this->views[$this->active]->model->clear();
                }
                break;

            case 'select':
                $this->setTemplate(false);
                $results = $this->selectAction();
                $this->response->setContent(json_encode($results));
                return false;
        }

        return true;
    }

    /**
     * Runs data insert action.
     *
     * @return bool
     */
    protected function insertAction()
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return false;
        } elseif (false === $this->validateFormToken()) {
            return false;
        }

        // loads form data
        $this->views[$this->active]->processFormData($this->request, 'edit');
        if ($this->views[$this->active]->model->exists()) {
            Tools::log()->error('duplicate-record');
            return false;
        }

        // save in database
        if (false === $this->views[$this->active]->model->save()) {
            Tools::log()->error('record-save-error');
            return false;
        }

        // redirect to new model url only if this is the first view
        if ($this->active === $this->getMainViewName()) {
            $this->redirect($this->views[$this->active]->model->url() . '&action=save-ok');
        }

        $this->views[$this->active]->newCode = $this->views[$this->active]->model->primaryColumnValue();
        Tools::log()->notice('record-updated-correctly');
        return true;
    }

    protected function widgetLibrarySearchAction(): array
    {
        // localizamos la pestaña y el nombre de la columna
        $activeTab = $this->request->request->get('active_tab', '');
        $colName = $this->request->request->get('col_name', '');
        $widgetId = $this->request->request->get('widget_id', '');

        // si está vacío, no hacemos nada
        if (empty($activeTab) || empty($colName)) {
            return ['records' => 0, 'html' => ''];
        }

        // buscamos la columna
        $column = $this->tab($activeTab)->columnForField($colName);
        if (empty($column) || strtolower($column->widget->getType()) !== 'library') {
            return ['records' => 0, 'html' => ''];
        }

        $files = $column->widget->files(
            $this->request->request->get('query', ''),
            $this->request->request->get('sort', '')
        );

        $selectedValue = (int)$column->widget->plainText($this->tab($activeTab)->model);
        return [
            'html' => $column->widget->renderFileList($files, $selectedValue, $widgetId),
            'records' => count($files),
        ];
    }

    protected function widgetLibraryUploadAction(): array
    {
        // localizamos la pestaña y el nombre de la columna
        $activeTab = $this->request->request->get('active_tab', '');
        $colName = $this->request->request->get('col_name', '');
        $widgetId = $this->request->request->get('widget_id', '');

        // si está vacío, no hacemos nada
        if (empty($activeTab) || empty($colName)) {
            return [];
        }

        // buscamos la columna
        $column = $this->tab($activeTab)->columnForField($colName);
        if (empty($column) || strtolower($column->widget->getType()) !== 'library') {
            return [];
        }

        $file = $column->widget->uploadFile($this->request->files->get('file'));
        if (false === $file->exists()) {
            return [];
        }

        $files = $column->widget->files();
        return [
            'html' => $column->widget->renderFileList($files, $file->idfile, $widgetId),
            'records' => count($files),
            'new_file' => $file->idfile,
            'new_filename' => $file->shortFileName(),
        ];
    }

    protected function widgetVarianteSearchAction(): array
    {
        // localizamos la pestaña y el nombre de la columna
        $activeTab = $this->request->request->get('active_tab', '');
        $colName = $this->request->request->get('col_name', '');

        // si está vacío, no hacemos nada
        if (empty($activeTab) || empty($colName)) {
            return [];
        }

        // buscamos la columna
        $column = $this->tab($activeTab)->columnForField($colName);
        if (empty($column) || strtolower($column->widget->getType()) !== 'variante') {
            return [];
        }

        $variantes = $column->widget->variantes(
            $this->request->request->get('query', ''),
            $this->request->request->get('codfabricante', ''),
            $this->request->request->get('codfamilia', ''),
            $this->request->request->get('sort', '')
        );

        $results = [];
        foreach ($variantes as $variante) {
            $results[] = [
                'id_variante' => $variante->idvariante,
                'id_producto' => $variante->idproducto,
                'referencia' => $variante->referencia,
                'descripcion' => $variante->description(),
                'precio' => $variante->precio,
                'precio_str' => Tools::money($variante->precio),
                'stock' => $variante->stockfis,
                'stock_str' => Tools::number($variante->stockfis, 0),
                'match' => $variante->{$column->widget->match},
            ];
        }
        return $results;
    }
}
