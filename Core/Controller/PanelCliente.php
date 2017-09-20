<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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

use FacturaScripts\Core\Base\ExtendedController;

/**
 * Description of PanelSettings
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class PanelCliente extends ExtendedController\PanelController
{

    protected function createViews()
    {
        $this->addEditView('FacturaScripts\Core\Model\Cliente', 'EditCliente', 'Cliente');
        $this->addEditView('FacturaScripts\Core\Model\Fabricante', 'EditFabricante', 'Fabricante');
        $this->addEditView('FacturaScripts\Core\Model\Pais', 'EditPais', 'Pais');
        $this->addListView('FacturaScripts\Core\Model\Cliente', 'ListCliente', 'Lista de Clientes');
    }

    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'customers';
        $pagedata['icon'] = 'fa-users';
        $pagedata['showonmenu'] = FALSE;

        return $pagedata;
    }
    
    public function getPanelHeader()
    {
        return $this->i18n->trans('options');
    }
}
