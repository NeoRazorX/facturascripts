<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller for stock regularization. It serves to manage losses, breakages,
 * consumption, or simply, if you want to update the stock.
 *
 * @author Rafael San José <info@rsanjoseo.com>
 */
class EditRegularizacionStock extends ExtendedController\EditController
{

    /**
     * Returns the model name
     */
    public function getModelClassName()
    {
        return 'RegularizacionStock';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'stocks-regularization';
        $pagedata['menu'] = 'warehouse';
        $pagedata['icon'] = 'clipboard-list';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Run the actions that alter data before reading it
     *
     * @param BaseView $view
     * @param string   $action
     *
     * @return bool
     */
    protected function execPreviousAction($view, $action)
    {
        switch ($action) {
            case 'save':
                $data = $this->request->request->all();
                $view->loadFromData($data);
                if ($this->editAction($view) && ($view->getModel()->nick == null)) {
                    $model = $view->getModel();
                    $model->nick = $this->user->nick;
                    $model->save();
                }
                break;

            default:
                return parent::execPreviousAction($view, $action);
        }

        return false;
    }
}
