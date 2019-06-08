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
 * Controller to edit a single item from the Agente model
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @author Raul
 */
class EditAgente extends EditController
{

    /**
     * Returns the class name of the model to use in the editView.
     *
     * @return string
     */
    public function getModelClassName()
    {
        return 'Agente';
    }

    /**
     * Returns basic page attributes.
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'agent';
        $data['icon'] = 'fas fa-id-badge';
        return $data;
    }

    protected function addContactView($viewName = 'ListContacto')
    {
        $this->addListView($viewName, 'Contacto', 'addresses-and-contacts', 'fas fa-address-book');

        /// sort options
        $this->views[$viewName]->addOrderBy(['fechaalta'], 'date');
        $this->views[$viewName]->addOrderBy(['descripcion'], 'descripcion', 2);

        /// search columns
        $this->views[$viewName]->searchFields[] = 'apellidos';
        $this->views[$viewName]->searchFields[] = 'descripcion';
        $this->views[$viewName]->searchFields[] = 'direccion';
        $this->views[$viewName]->searchFields[] = 'email';
        $this->views[$viewName]->searchFields[] = 'nombre';

        /// Disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
    }

    /**
     * Load Views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->addListView('ListFacturaCliente', 'FacturaCliente', 'invoices', 'fas fa-copy');
        $this->addListView('ListAlbaranCliente', 'AlbaranCliente', 'delivery-notes', 'fas fa-copy');
        $this->addListView('ListPedidoCliente', 'PedidoCliente', 'orders', 'fas fa-copy');
        $this->addListView('ListPresupuestoCliente', 'PresupuestoCliente', 'estimations', 'fas fa-copy');
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
            case 'EditAgente':
                parent::loadData($viewName, $view);
                break;

            case 'ListAlbaranCliente':
            case 'ListFacturaCliente':
            case 'ListPedidoCliente':
            case 'ListPresupuestoCliente':
                $codagente = $this->getViewModelValue('EditAgente', 'codagente');
                $where = [new DataBaseWhere('codagente', $codagente)];
                $view->loadData('', $where);
                break;
        }
    }

    /**
     *
     * @param BaseView $view
     */
    protected function setCustomWidgetValues($view)
    {
        /// Model exists?
        if (!$view->model->exists()) {
            $view->disableColumn('fiscal-id');
            return;
        }

        /// load agent contact dada
        $view->model->loadContactData();

        /// Search for agent contacts and load contacts widget
        $codagente = $this->getViewModelValue($view->name, 'codagente');
        $where = [new DataBaseWhere('codagente', $codagente)];
        $contacts = $this->codeModel->all('contactos', 'idcontacto', 'descripcion', false, $where);
        $columnContacts = $view->columnForName('contact');
        $columnContacts->widget->setValuesFromCodeModel($contacts);
    }
}
