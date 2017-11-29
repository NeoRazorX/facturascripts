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

use FacturaScripts\Core\Base\Database;
use FacturaScripts\Core\Base\ExtendedController;
use FacturaScripts\Core\Model\PresupuestoCliente;
use FacturaScripts\Core\Model\PedidoCliente;
use FacturaScripts\Core\Model\AlbaranCliente;
use FacturaScripts\Core\Model\FacturaCliente;

/**
 * Controller to edit a single item from the Agente model
 *
 * @author Raul
 *  Edit Agente class based upon Editcliente's functionality
 */
class EditAgente extends ExtendedController\PanelController
{

    /**
     * Returns the model name
     */
    public function getModelName()
    {
        return 'FacturaScripts\Core\Model\Agente';
    }

    /**
     * Load Views
     */
    protected function createViews()
    {
        $this->addEditView('FacturaScripts\Core\Model\Agente', 'EditAgente', 'agent');
        $this->addListView('\FacturaScripts\Core\Model\PresupuestoCliente', 'EditAgentePresupuestos', 'budgets');
        $this->addListView('\FacturaScripts\Core\Model\PedidoCliente', 'EditAgentePedidos', 'orders');
        $this->addListView('\FacturaScripts\Core\Model\AlbaranCliente', 'EditAgenteAlbaranes', 'delivery-notes');
        $this->addListView('\FacturaScripts\Core\Model\FacturaCliente', 'EditAgenteFacturas', 'invoice', 'fa-book');
    }

    /**
     * Load view data procedure
     *
     * @param string $keyView
     * @param ExtendedController\EditView $view
     */
    protected function loadData($keyView, $view)
    {
        $code = $this->request->get('code');
        switch ($keyView) {
            case 'EditAgente':
                $view->loadData($code);
                break;
            case 'EditAgentePresupuestos':
            case 'EditAgentePedidos':
            case 'EditAgenteAlbaranes':
            case 'EditAgenteFacturas':
                $where = [new DataBase\DataBaseWhere('codagente', $code)];
                $view->loadData($where);
                break;
        }
    }

    /**
     * Returns the text for the data panel footer
     *
     * @return string
     */
    public function getPanelFooter()
    {
        $model = $this->getModel();
        return $this->i18n->trans('discharge-date', [$model->f_alta]);
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'agent';
        $pagedata['menu'] = 'admin';
        $pagedata['icon'] = 'fa-id-badge';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
