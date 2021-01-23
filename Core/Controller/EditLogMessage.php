<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Controller to edit a single item from the LogMessage model
 *
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 */
class EditLogMessage extends EditController
{

    /**
     * Returns the model name.
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'LogMessage';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'log';
        $data['icon'] = 'fas fa-file-medical-alt';
        return $data;
    }

    /**
     * Loads views.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        /// disable buttons
        $this->setSettings($this->getMainViewName(), 'btnNew', false);

        /// related logs tab
        $this->createViewsOtherLogs();
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsOtherLogs(string $viewName = 'ListLogMessage')
    {
        $this->addListView($viewName, 'LogMessage', 'related', 'fas fa-file-medical-alt');
        $this->views[$viewName]->addSearchFields(['ip', 'message', 'uri']);
        $this->views[$viewName]->addOrderBy(['time', 'id'], 'date', 2);
        $this->views[$viewName]->addOrderBy(['level'], 'level');

        /// disable buttons
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     * Load view data procedure
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListLogMessage':
                $code = $this->getViewModelValue($this->getMainViewName(), 'id');
                $ipAddress = $this->getViewModelValue($this->getMainViewName(), 'ip');
                $where = [
                    new DataBaseWhere('id', $code, '!='),
                    new DataBaseWhere('ip', $ipAddress)
                ];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
