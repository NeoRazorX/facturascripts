<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\Model\Provincia;
use FacturaScripts\Dinamic\Lib\ExtendedController\ListBusinessDocument;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;

/**
 * Controller to list the items in the PresupuestoCliente model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Raul Jimenez                 <raul.jimenez@nazcanetworks.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class ListPresupuestoCliente extends ListBusinessDocument
{
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

        // añadimos botones
        $this->addButtonGroupDocument($viewName);
        $this->addButtonApproveDocument($viewName);

        $paises = Paises::codeModel();
        $this->addFilterSelect($viewName, 'country', 'country', 'codpais', $paises);
        $this->addFilterAutocomplete($viewName, 'provincia', 'province', 'provincia', 'provincias');
        $this->addFilterAutocomplete($viewName, 'ciudad', 'city', 'ciudad', 'ciudades');
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
            new DataBaseWhere('editable', true),
            new DataBaseWhere('finoferta', null, 'IS NOT')
        ];
        foreach ($model->all($where, ['finoferta' => 'ASC']) as $item) {
            if (time() < strtotime($item->finoferta)) {
                continue;
            }

            $item->idestado = $expiredStatus;
            $item->save();
        }
    }

    protected function autocompleteAction(): array
    {
        $data = $this->requestGet(['source', 'fieldcode', 'fieldtitle', 'strict', 'term']);
        if ($data['source'] === 'provincias') {
            $codpais = $this->request->input('filtercountry');

            $where = [];
            if (empty($codpais) === false) {
                $where[] = new DataBaseWhere('codpais', $codpais);
            }

            $result = [];
            foreach ($this->codeModel->search('provincias', $data['fieldcode'], $data['fieldtitle'], $data['term'], $where) as $value) {
                $result[] = ['key' => $value->code, 'value' => $value->description];
            }

            return $result;
        } elseif ($data['source'] === 'ciudades') {
            $codprovincia = $this->request->input('filterprovincia');

            $where = [];
            if (empty($codprovincia) === false) {
                $provincias = Provincia::all([new DataBaseWhere('provincia', $codprovincia)]);
                if (empty($provincias)) {
                    return [];
                }

                $where[] = new DataBaseWhere('idprovincia', $provincias[0]->idprovincia);
            }

            $result = [];
            foreach ($this->codeModel->search('ciudades', $data['fieldcode'], $data['fieldtitle'], $data['term'], $where) as $value) {
                $result[] = ['key' => $value->code, 'value' => $value->description];
            }

            return $result;
        }

        return parent::autocompleteAction();
    }
}
