<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Controller to manage the data editing
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
abstract class EditController extends PanelController
{

    /**
     * Returns the class name of the model to use in the editView.
     */
    abstract public function getModelClassName();

    /**
     * Pointer to the data model.
     *
     * @return mixed
     */
    public function getModel()
    {
        $viewName = array_keys($this->views)[0];
        return $this->views[$viewName]->model;
    }

    /**
     * Create the view to display.
     */
    protected function createViews()
    {
        $modelName = $this->getModelClassName();
        $viewName = 'Edit' . $this->getModelClassName();
        $title = $this->getPageData()['title'];
        $viewIcon = $this->getPageData()['icon'];

        $this->addEditView($viewName, $modelName, $title, $viewIcon);
    }

    /**
     * Loads the data to display.
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        /**
         * We need the identifier to load the model. It's almost always code,
         * but sometimes it's not.
         */
        $primaryKey = $this->request->request->get($view->model->primaryColumn());
        $code = $this->request->query->get('code', $primaryKey);
        $view->loadData($code);

        /// data not found?
        $action = $this->request->request->get('action', '');
        $mainViewName = 'Edit' . $this->getModelClassName();
        if (!empty($code) && !$view->model->exists() && $viewName === $mainViewName && '' === $action) {
            $this->miniLog->warning($this->i18n->trans('record-not-found'));
        }
    }
}
