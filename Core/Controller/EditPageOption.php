<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Lib\Widget\VisualItemLoadEngine;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\Page;
use FacturaScripts\Dinamic\Model\PageOption;
use FacturaScripts\Dinamic\Model\User;

/**
 * Controlador para editar las opciones de visualización (columnas y filtros) de cualquier página.
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Jose Antonio Cuello          <yopli2000@gmail.com>
 * @author Fco. Antonio Moreno Pérez    <famphuelva@gmail.com>
 */
class EditPageOption extends Controller
{
    /**
     * Contains the url to go back.
     *
     * @var string
     */
    public $backPage;

    /**
     * @var array
     */
    public $columns = [];

    /**
     * @var array
     */
    public $modals = [];

    /**
     * Details of the view configuration
     *
     * @var PageOption
     */
    public $model;

    /**
     * @var array
     */
    public $rows = [];

    /**
     * Identificador de la personalización con nombre que se edita; 0 = "por defecto".
     *
     * @var int
     */
    public $selectedId = 0;

    /**
     * Selected user, for which the controller columns are created or modified
     *
     * @var string
     */
    public $selectedUser;

    /**
     * Selected view, for which columns are created or modified
     *
     * @var string
     */
    public $selectedViewName;

    /**
     * Indica si se permiten personalizaciones con nombre: solo en listados y para un usuario concreto.
     *
     * @return bool
     */
    public function allowNamed(): bool
    {
        return str_starts_with($this->selectedViewName, 'List') && false === empty($this->selectedUser);
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'options';
        $data['icon'] = 'fa-solid fa-wrench';
        $data['showonmenu'] = false;
        return $data;
    }

    /**
     * Devuelve la lista para el selector de personalizaciones: la "por defecto"
     * y las personalizaciones con nombre del usuario seleccionado.
     *
     * @return array
     */
    public function getPageOptionList(): array
    {
        $result = [0 => Tools::trans('default')];
        if (false === $this->allowNamed()) {
            return $result;
        }

        $where = [
            Where::eq('name', $this->selectedViewName),
            Where::eq('nick', $this->selectedUser),
            Where::isNotNull('description'),
        ];
        foreach (PageOption::all($where, ['description' => 'ASC']) as $pageOption) {
            $result[$pageOption->id] = $pageOption->description;
        }

        return $result;
    }

    /**
     * Get the list of users, excluding the user admin unless it is the selected user
     *
     * @return array
     */
    public function getUserList(): array
    {
        $result = [];
        $users = CodeModel::all(User::tableName(), 'nick', 'nick', false);
        foreach ($users as $codeModel) {
            if ($codeModel->code != 'admin' || $codeModel->code === $this->selectedUser) {
                $result[$codeModel->code] = $codeModel->description;
            }
        }

        return $result;
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

        $this->model = new PageOption();
        $this->loadSelectedViewName();
        $this->setBackPage();
        $this->selectedUser = $this->user->admin ?
            $this->request->inputOrQuery('nick') :
            $this->user->nick;
        $this->selectedId = (int)$this->request->queryOrInput('id', 0);
        $this->loadPageOptions();

        $action = $this->request->inputOrQuery('action', '');
        switch ($action) {
            case 'delete':
                $this->deleteAction();
                break;

            case 'save':
                $this->saveAction();
                break;

            case 'save-new':
                $this->saveNewAction();
                break;
        }
    }

    /**
     * Delete configuration for view
     */
    protected function deleteAction(): void
    {
        if (false === $this->permissions->allowDelete) {
            Tools::log()->warning('not-allowed-delete');
            return;
        } elseif (false === $this->validateFormToken()) {
            return;
        }

        if ($this->model->delete()) {
            Tools::log()->notice('record-deleted-correctly');
            $this->loadPageOptions();
            return;
        }

        Tools::log()->warning('default-not-deletable');
    }

    /**
     * Load the display options to edit.
     * If it does not find them in the database,
     * it loads the default options of the xml view.
     */
    protected function loadPageOptions(): void
    {
        // si la personalización con nombre no es válida, se edita la "por defecto"
        if ($this->selectedId > 0 && false === $this->loadPageOptionsNamed()) {
            $this->selectedId = 0;
        }

        // comprobamos si existen personalizaciones guardadas
        if ($this->selectedId > 0) {
            $customized = true;
        } else {
            $customized = $this->selectedUser ?
                $this->loadPageOptionsForUser() :
                $this->loadPageOptionsForAll();
        }

        // partimos de la estructura actual del XML y, si hay personalización, aplicamos sus cambios sobre ella
        if ($customized) {
            $custom = clone $this->model;
            VisualItemLoadEngine::installXML($this->selectedViewName, $this->model);
            VisualItemLoadEngine::mergeCustomization($this->model, $custom);
        } else {
            VisualItemLoadEngine::installXML($this->selectedViewName, $this->model);
        }

        // creamos la estructura visual
        VisualItemLoadEngine::loadArray($this->columns, $this->modals, $this->rows, $this->model);
    }

    protected function loadSelectedViewName(): void
    {
        $code = $this->request->queryOrInput('code', '');
        if (false === strpos($code, '-')) {
            $this->selectedViewName = $code;
            return;
        }

        $parts = explode('-', $code);
        $this->selectedViewName = empty($parts) ? $code : $parts[0];
    }

    /**
     * Guarda la configuración editada. Si tiene nombre, la renombra y la marca como elegida;
     * si es la "por defecto" de un usuario, desmarca sus personalizaciones con nombre.
     * Tras guardar vuelve a la página de origen.
     */
    protected function saveAction(): void
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return;
        } elseif (false === $this->validateFormToken()) {
            return;
        }

        if (null !== $this->model->description) {
            $description = trim($this->request->input('description', ''));
            if ($description === '') {
                Tools::log()->warning('description-error');
                return;
            }

            $this->model->description = $description;
            $this->model->selected = true;
        }

        $this->setColumnsFromForm();
        if (false === $this->model->save()) {
            Tools::log()->error('record-save-error');
            return;
        }

        if (null === $this->model->description && false === empty($this->model->nick)) {
            PageOption::unselect($this->model->name, $this->model->nick);
        }

        Tools::log()->notice('record-updated-correctly');
        $this->redirect($this->backPage);
    }

    /**
     * Crea una nueva personalización con nombre para el usuario seleccionado
     * a partir de los valores del formulario, la marca como elegida y vuelve a la página de origen.
     */
    protected function saveNewAction(): void
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return;
        } elseif (false === $this->validateFormToken() || false === $this->allowNamed()) {
            return;
        }

        $description = trim($this->request->input('description', ''));
        if ($description === '') {
            Tools::log()->warning('description-error');
            return;
        }

        $this->setColumnsFromForm();
        $this->model->id = null;
        $this->model->description = $description;
        $this->model->nick = $this->selectedUser;
        $this->model->selected = true;
        if (false === $this->model->save()) {
            Tools::log()->error('record-save-error');
            return;
        }

        Tools::log()->notice('record-updated-correctly');
        $this->redirect($this->backPage);
    }

    /**
     * Loads the general display options for all users,
     * and indicates if they exist or not.
     *
     * @return bool
     */
    private function loadPageOptionsForAll(): bool
    {
        $where = [
            Where::eq('name', $this->selectedViewName),
            Where::isNull('nick'),
            Where::isNull('description'),
        ];
        return $this->model->loadWhere($where);
    }

    /**
     * Loads the display options specific to the user.
     * If they do not exist, look for the display options common to all users.
     * In either case, it indicates whether it has found a configuration.
     *
     * @return bool
     */
    private function loadPageOptionsForUser(): bool
    {
        $where = [
            Where::eq('name', $this->selectedViewName),
            Where::eq('nick', $this->selectedUser),
            Where::isNull('description'),
        ];
        if ($this->model->loadWhere($where)) {
            // Existen opciones para el usuario.
            return true;
        }

        if (false === $this->loadPageOptionsForAll()) {
            // No existe opciones generales. Asignamos las opciones por defecto de la vista xml al usuario.
            $this->model->nick = $this->selectedUser;
            return false;
        }

        // No existe opciones para el usuario. Clonamos las generales.
        $this->model->id = null;
        $this->model->nick = $this->selectedUser;
        return true;
    }

    /**
     * Carga la personalización con nombre seleccionada, comprobando que sea
     * de la vista y del usuario seleccionados.
     *
     * @return bool
     */
    private function loadPageOptionsNamed(): bool
    {
        if (false === $this->allowNamed()) {
            return false;
        }

        $where = [
            Where::eq('id', $this->selectedId),
            Where::eq('name', $this->selectedViewName),
            Where::eq('nick', $this->selectedUser),
            Where::isNotNull('description'),
        ];
        return $this->model->loadWhere($where);
    }

    private function setBackPage(): void
    {
        // check if the url is a real controller name
        $url = $this->request->queryOrInput('url', '');
        foreach (Page::all() as $page) {
            if (substr($url, 0, strlen($page->name)) === $page->name) {
                $this->backPage = $url;
                return;
            }
        }

        // set the default back page
        $this->backPage = $this->selectedViewName;
    }

    /**
     * @param array $column
     * @param string $name
     * @param string $key
     * @param bool $isWidget
     * @param bool $allowEmpty
     */
    private function setColumnOption(&$column, string $name, string $key, bool $isWidget, bool $allowEmpty): void
    {
        $newValue = Tools::noHtml($this->request->input($name . '-' . $key));
        if ($isWidget) {
            if (!empty($newValue) || $allowEmpty) {
                $column['children'][0][$key] = $newValue;
            }
            return;
        }

        if (!empty($newValue) || $allowEmpty) {
            $column[$key] = $newValue;
        }
    }

    /**
     * Aplica al modelo los valores de las columnas enviados en el formulario.
     */
    private function setColumnsFromForm(): void
    {
        foreach ($this->model->columns as $key1 => $group) {
            if ($group['tag'] === 'column') {
                $name = $group['name'];
                $this->setColumnOption($this->model->columns[$key1], $name, 'title', false, false);
                $this->setColumnOption($this->model->columns[$key1], $name, 'display', false, false);
                $this->setColumnOption($this->model->columns[$key1], $name, 'level', false, true);
                $this->setColumnOption($this->model->columns[$key1], $name, 'readonly', true, true);
                $this->setColumnOption($this->model->columns[$key1], $name, 'decimal', true, true);
                $this->setColumnOption($this->model->columns[$key1], $name, 'numcolumns', false, true);
                $this->setColumnOption($this->model->columns[$key1], $name, 'order', false, true);
                continue;
            }

            foreach ($group['children'] as $key2 => $col) {
                $name = $col['name'];
                $this->setColumnOption($this->model->columns[$key1]['children'][$key2], $name, 'title', false, false);
                $this->setColumnOption($this->model->columns[$key1]['children'][$key2], $name, 'display', false, false);
                $this->setColumnOption($this->model->columns[$key1]['children'][$key2], $name, 'level', false, true);
                $this->setColumnOption($this->model->columns[$key1]['children'][$key2], $name, 'readonly', true, true);
                $this->setColumnOption($this->model->columns[$key1]['children'][$key2], $name, 'decimal', true, true);
                $this->setColumnOption($this->model->columns[$key1]['children'][$key2], $name, 'numcolumns', false, true);
                $this->setColumnOption($this->model->columns[$key1]['children'][$key2], $name, 'order', false, true);
            }
        }
    }
}
