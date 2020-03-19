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

use FacturaScripts\Dinamic\Lib\ExtendedController\ListBusinessDocument;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;

/**
 * Controller to list the items in the PresupuestoCliente model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Artex Trading sa             <jcuello@artextrading.com>
 * @author Raul Jimenez                 <raul.jimenez@nazcanetworks.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class ListPresupuestoCliente extends ListBusinessDocument
{

    /**
     * Runs the controller's private logic.
     *
     * @param Response                   $response
     * @param User                       $user
     * @param Base\ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        $this->checkExpiredBudgets();// 
        parent::privateCore($response, $user, $permissions);
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'estimations';
        $data['icon'] = 'fas fa-copy';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewSales('ListPresupuestoCliente', 'PresupuestoCliente', 'estimations');
        $this->addButtonGroupDocument('ListPresupuestoCliente');
        $this->addButtonApproveDocument('ListPresupuestoCliente');

        $this->createViewLines('ListLineaPresupuestoCliente', 'LineaPresupuestoCliente');
    }

    protected function checkExpiredBudgets()
    {
        $presupuesto = new PresupuestoCliente;        
        foreach ($presupuesto->getAvaliableStatus() as $status) {
            if ($status->idestado == 23 && !$status->editable && empty($status->generadoc)) {
                $newStatus = $status->idestado;
                break;
            }
            if (!$status->editable && empty($status->generadoc)) {
                $newStatus = $status->idestado;
            }
        }
        
        $where = [new DataBaseWhere('editable', true)];
        foreach ($presupuesto->all($where) as $value) {
            if (time() >= \strtotime($value->finoferta)) {
                $value->idestado = $newStatus;
                $value->save();
            }
        }
    }
}
