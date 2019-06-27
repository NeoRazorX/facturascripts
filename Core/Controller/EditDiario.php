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
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Controller to edit a single item from the Diario model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Raul Jimenez         <raul.jimenez@nazcanetworks.com>
 */
class EditDiario extends EditController
{

    /**
     * Returns the model name.
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'Diario';
    }

    /**
     * Returns basic page attributes.
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'journal';
        $data['icon'] = 'fas fa-book';
        return $data;
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createAccountingView($viewName = 'ListAsiento')
    {
        $this->addListView($viewName, 'Asiento', 'accounting-entry');
        $this->views[$viewName]->addOrderBy(['fecha'], 'date', 2);
        $this->views[$viewName]->addOrderBy(['importe'], 'amount');
        $this->views[$viewName]->searchFields[] = 'concepto';

        /// disable columns
        $this->views[$viewName]->disableColumn('journal');
    }

    /**
     * Create tabs or views.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->createAccountingView();
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
            case 'ListAsiento':
                $iddiario = $this->getViewModelValue($this->getMainViewName(), 'iddiario');
                $where = [new DataBaseWhere('iddiario', $iddiario)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
