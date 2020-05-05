<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\ExtendedController\ListController;

/**
 * Controller to list the items in the Balance model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Fco. Antonio Moreno Pérez    <famphuelva@gmail.com>
 * @author Artex Trading sa             <jcuello@artextrading.com>
 */
class ListBalance extends ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'accounting-balances';
        $data['icon'] = 'fas fa-clipboard';
        return $data;
    }

    /**
     * Create and add view
     *
     * @param string $viewName
     * @param string $viewTitle
     */
    protected function addViewBalance(string $viewName, string $viewTitle)
    {
        $this->addView($viewName, 'Balance', $viewTitle);
        $this->addOrderBy($viewName, ['codbalance'], 'code');
        $this->addOrderBy($viewName, ['descripcion1'], 'description-1');
        $this->addOrderBy($viewName, ['descripcion2'], 'description-2');
        $this->addOrderBy($viewName, ['descripcion3'], 'description-3');
        $this->addOrderBy($viewName, ['descripcion4'], 'description-4');
        $this->addOrderBy($viewName, ['descripcion4ba'], 'description-4ba');
        $this->addSearchFields($viewName, [
            'codbalance', 'naturaleza', 'descripcion1', 'descripcion2',
            'descripcion3', 'descripcion4', 'descripcion4ba'
        ]);
    }

    /**
     * Create views
     */
    protected function createViews()
    {
        $this->addViewBalance('ListBalance-1', 'asset');
        $this->addViewBalance('ListBalance-2', 'liabilities');
        $this->addViewBalance('ListBalance-3', 'profit-and-loss');
        $this->addViewBalance('ListBalance-4', 'income-and-expenses');
    }

    /**
     * 
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $where = [];
        switch ($viewName) {
            case 'ListBalance-1':
                $where[] = new DataBaseWhere('naturaleza', 'A');
                $view->loadData(false, $where);
                break;

            case 'ListBalance-2':
                $where[] = new DataBaseWhere('naturaleza', 'P');
                $view->loadData(false, $where);
                break;

            case 'ListBalance-3':
                $where[] = new DataBaseWhere('naturaleza', 'PG');
                $view->loadData(false, $where);
                break;

            case 'ListBalance-4':
                $where[] = new DataBaseWhere('naturaleza', 'IG');
                $view->loadData(false, $where);
                break;

            default:
                parent::loadData($viewName, $view);
        }
    }
}
