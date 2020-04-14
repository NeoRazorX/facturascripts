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
use FacturaScripts\Dinamic\Lib\ExtendedController\ListBusinessDocument;
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
        /// main view/tab
        $mainViewName = 'ListPresupuestoCliente';
        $this->createViewSales($mainViewName, 'PresupuestoCliente', 'estimations');
        $this->views[$mainViewName]->addOrderBy(['finoferta'], 'expiration');
        $this->addButtonGroupDocument($mainViewName);
        $this->addButtonApproveDocument($mainViewName);

        /// lines view/tab
        $this->createViewLines('ListLineaPresupuestoCliente', 'LineaPresupuestoCliente');
    }

    /**
     * 
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        if (empty($action)) {
            $this->setExpiredItems();
        }

        return parent::execPreviousAction($action);
    }

    protected function setExpiredItems()
    {
        $presupuestoModel = new PresupuestoCliente;

        /// select the avaliable expired status
        $expiredStatus = null;
        foreach ($presupuestoModel->getAvaliableStatus() as $status) {
            if ($status->idestado == 23 && !$status->editable && empty($status->generadoc)) {
                $expiredStatus = $status->idestado;
                break;
            } elseif (false === $status->editable && empty($status->generadoc)) {
                $expiredStatus = $status->idestado;
            }
        }
        if (null === $expiredStatus) {
            return;
        }

        $where = [
            new DataBaseWhere('editable', true),
            new DataBaseWhere('finoferta', null, 'IS NOT')
        ];
        foreach ($presupuestoModel->all($where, ['finoferta' => 'ASC']) as $item) {
            if (\time() < \strtotime($item->finoferta)) {
                continue;
            }

            $item->idestado = $expiredStatus;
            $item->save();
        }
    }
}
