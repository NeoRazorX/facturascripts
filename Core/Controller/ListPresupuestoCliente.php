<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\Lib\ExtendedController\ProvinceCityFilterTrait;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\ExtendedController\ListBusinessDocument;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;

/**
 * Controlador para listar los elementos del modelo PresupuestoCliente
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Raul Jimenez                 <raul.jimenez@nazcanetworks.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class ListPresupuestoCliente extends ListBusinessDocument
{
    use ProvinceCityFilterTrait;

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'estimations';
        $data['icon'] = 'fa-regular fa-file-powerpoint';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewsPresupuestos();

        if ($this->permissions->onlyOwnerData === false) {
            $this->createViewLines('ListLineaPresupuestoCliente', 'LineaPresupuestoCliente');
        }
    }

    protected function createViewsPresupuestos(string $viewName = 'ListPresupuestoCliente'): void
    {
        $this->createViewSales($viewName, 'PresupuestoCliente', 'estimations');
        $this->addOrderBy($viewName, ['finoferta'], 'expiration');

        // agrupamos las acciones secundarias en un dropdown
        $this->tab($viewName)->addButtonGroup([
            'name' => 'doc-actions',
            'icon' => 'fa-solid fa-circle-check',
            'label' => 'actions'
        ]);
        $this->addButtonApproveDocument($viewName, 'doc-actions');
        $this->addButtonGroupDocument($viewName, 'doc-actions');

        $paises = Paises::codeModel();
        $this->addFilterSelect($viewName, 'country', 'country', 'codpais', $paises);
        $this->addFilterSelectAuto($viewName, 'provincia', 'province', 'provincia', 'provincias');
        $this->addFilterSelectAuto($viewName, 'ciudad', 'city', 'ciudad', 'ciudades');
    }

    /**
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
        $model = new PresupuestoCliente();

        // select the available expired status
        $expiredStatus = null;
        foreach ($model->getAvailableStatus() as $status) {
            if (!$status->activo) {
                continue;
            }

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
            Where::eq('editable', true),
            Where::isNotNull('finoferta')
        ];
        foreach ($model->all($where, ['finoferta' => 'ASC']) as $item) {
            if (time() < strtotime($item->finoferta)) {
                continue;
            }

            // no expiramos presupuestos ajenos cuando el usuario solo ve los suyos
            if (false === $this->checkOwnerData($item)) {
                continue;
            }

            $item->idestado = $expiredStatus;
            $item->save();
        }
    }
}
