<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to manage the data editing
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
abstract class EditController extends PanelController
{

    /**
     * Returns the class name of the model to use in the editView.
     */
    abstract public function getModelClassName();

    /**
     * Starts all the objects and properties
     *
     * @param Base\Cache      $cache
     * @param Base\Translator $i18n
     * @param Base\MiniLog    $miniLog
     * @param string          $className
     */
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);
        $this->setTabsPosition('bottom');
    }

    /**
     * Create the view to display
     *
     * @return EditView
     */
    protected function createViews()
    {
        $modelName = '\\FacturaScripts\\Dinamic\\Model\\' . $this->getModelClassName();
        $viewName = 'Edit' . $this->getModelClassName();
        $title = $this->getPageData()['title'];
        $viewIcon = $this->getPageData()['icon'];
        $this->addEditView($modelName, $viewName, $title, $viewIcon);
    }

    /**
     * Loads the data to display
     *
     * @param string   $keyView
     * @param BaseView $view
     */
    protected function loadData($keyView, $view)
    {
        $code = $this->request->get('code');
        $view->loadData($code);
    }

    /**
     * Pointer to the data model
     *
     * @return mixed
     */
    public function getModel()
    {
        $viewKey = array_keys($this->views)[0];
        return $this->views[$viewKey]->getModel();
    }
}
