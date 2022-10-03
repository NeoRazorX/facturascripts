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

use FacturaScripts\Dinamic\Lib\ExtendedController\ListBusinessDocument;

/**
 * Controller to list the items in the PresupuestoProveedor model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Raul Jimenez                 <raul.jimenez@nazcanetworks.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class ListPresupuestoProveedor extends ListBusinessDocument
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'purchases';
        $data['title'] = 'estimations';
        $data['icon'] = 'far fa-file-powerpoint';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $mainViewName = 'ListPresupuestoProveedor';
        $this->createViewPurchases($mainViewName, 'PresupuestoProveedor', 'estimations');
        $this->addButtonGroupDocument($mainViewName);
        $this->addButtonApproveDocument($mainViewName);
        $this->views[$mainViewName]->addColor('idestado', '13', 'success', 'approved');
        $this->views[$mainViewName]->addColor('idestado', '14', 'danger', 'cancelled');
        $this->views[$mainViewName]->addColor('editable', '0', 'warning', 'non-editable-document');
        $this->views[$mainViewName]->addColor('femail', 'notnull:', 'info', 'email-sent');

        if ($this->permissions->onlyOwnerData === false) {
            $this->createViewLines('ListLineaPresupuestoProveedor', 'LineaPresupuestoProveedor');
        }
    }
}
