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

namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Tools;

/**
 * Controller to manage the data editing
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
abstract class EditController extends PanelController
{
    /**
     * Returns the class name of the model to use in the editView.
     */
    abstract public function getModelClassName(): string;

    /**
     * Pointer to the data model.
     *
     * @return mixed
     */
    public function getModel()
    {
        return $this->mainTab()->model;
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['showonmenu'] = false;
        return $data;
    }

    /**
     * Comprueba que el usuario puede acceder al registro principal antes de ejecutar la acción.
     * Las acciones previas se ejecutan antes de loadData() y algunas cargan el registro por su
     * cuenta, así que no basta con la comprobación de loadData().
     *
     * @param string $action
     */
    protected function assertActionOwnerData(string $action): void
    {
        if ('' === $action || false === $this->permissions->onlyOwnerData) {
            return;
        }

        // el registro principal se identifica por code en la url y, si la pestaña activa es
        // la principal, también por code o por su clave primaria en el formulario
        $model = $this->getModel();
        $codes = [$this->request->query('code')];
        if ($this->active === $this->mainTabName()) {
            $codes[] = $this->request->input('code');
            $codes[] = $this->request->input($model->primaryColumn());
        }

        foreach (array_unique(array_filter($codes, fn($code) => null !== $code && '' !== $code)) as $code) {
            $record = clone $model;
            if ($record->loadFromCode($code)) {
                $this->assertOwnerData($record);
            }
        }
    }

    /**
     * Create the view to display.
     */
    protected function createViews()
    {
        $viewName = 'Edit' . $this->getModelClassName();
        $modelName = $this->getModelClassName();
        $title = $this->getPageData()['title'];
        $viewIcon = $this->getPageData()['icon'];

        $this->addEditView($viewName, $modelName, $title, $viewIcon);
        $this->setSettings($viewName, 'btnPrint', true);
    }

    protected function exportAction()
    {
        // comprobamos permisos
        if (
            false === $this->activeTab()->settings['btnPrint'] ||
            false === $this->permissions->allowExport
        ) {
            Tools::log()->warning('no-print-permission');
            return;
        }

        $this->setTemplate(false);
        $this->exportManager->newDoc(
            $this->request->queryOrInput('option', ''),
            $this->title,
            (int)$this->request->input('idformat', ''),
            $this->request->input('langcode', '')
        );

        // recorremos las pestañas para ver qué imprimir
        foreach ($this->views as $name => $selectedView) {
            if (false === $selectedView->settings['active']) {
                continue;
            }

            // si tenemos una pestaña activa, excluimos las demás
            $activeTab = $this->request->inputOrQuery('activetab', '');
            if (!empty($activeTab) && $activeTab !== $name) {
                continue;
            }

            // mandamos imprimir
            $codes = $this->request->request->getArray('codes');
            if (false === $selectedView->export($this->exportManager, $codes)) {
                break;
            }
        }

        $this->exportManager->show($this->response);
    }

    /**
     * Loads the data to display.
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->mainTabName();
        switch ($viewName) {
            case $mainViewName:
                /**
                 * We need the identifier to load the model. It's almost always code,
                 * but sometimes it's not.
                 */
                $primaryKey = $this->request->input($view->model->primaryColumn());
                $code = $this->request->query('code', $primaryKey);
                $view->loadData($code);

                // el usuario puede acceder a este registro? si no, se interrumpe la petición
                $this->assertOwnerData($view->model);

                // Data not found?
                $action = $this->request->input('action', '');
                if ('' === $action && !empty($code) && false === $view->model->exists()) {
                    Tools::log()->warning('record-not-found');
                    break;
                }

                $this->title .= ' ' . $view->model->primaryDescription();
                break;
        }
    }
}
