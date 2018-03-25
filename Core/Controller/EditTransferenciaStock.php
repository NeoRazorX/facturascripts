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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Regularization of the stock of a warehouse of articles on a specific date.
 *
 * @author Rafael San José <info@rsanjoseo.com>
 */
class EditTransferenciaStock extends ExtendedController\PanelController
{

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addEditView('TransferenciaStock', 'EditTransferenciaStock', 'transfers-between-warehouses', 'fa-dolly');
        $this->addEditListView('LineaTransferenciaStock', 'EditLineaTransferenciaStock', 'transfer-between-warehouses-details');

        /// Disable column
        $this->views['EditLineaTransferenciaStock']->disableColumn('idtrans', true);
    }

    /**
     * Load view data procedure
     *
     * @param string                      $keyView
     * @param ExtendedController\EditView $view
     */
    protected function loadData($keyView, $view)
    {
        $limit = FS_ITEM_LIMIT;
        switch ($keyView) {
            case 'EditTransferenciaStock':
                $code = $this->request->get('code');
                $view->loadData($code);
                break;

            case 'EditLineaTransferenciaStock':
                $limit = 0;
                $idtrans = $this->getViewModelValue('EditTransferenciaStock', 'idtrans');
                $where = [new DataBaseWhere('idtrans', $idtrans)];
                $view->loadData(false, $where, [], 0, $limit);
                break;
        }
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
                if ($this->active == 'EditTransferenciaStock') {
                    $data = $this->request->request->all();
                    $view->loadFromData($data);
                    if ($this->editAction($view) && $view->getModel()->nick == null) {
                        $model = $view->getModel();
                        $model->nick = $this->user->nick;
                        $model->save();
                    }
                } else {
                    return parent::execPreviousAction($view, $action);
                }
                break;

            default:
                return parent::execPreviousAction($view, $action);
        }

        return false;
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'transfers-between-warehouses';
        $pagedata['icon'] = 'fa-dolly';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
