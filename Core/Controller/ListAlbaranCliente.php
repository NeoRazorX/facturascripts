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

/**
 * Controller to list the items in the AlbaranCliente model
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Cristo M. Estévez Hernández   <cristom.estevez@gmail.com>
 */
class ListAlbaranCliente extends ListBusinessDocument
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'delivery-notes';
        $data['icon'] = 'fa-solid fa-dolly-flatbed';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewsAlbaranes();

        if ($this->permissions->onlyOwnerData === false) {
            $this->createViewLines('ListLineaAlbaranCliente', 'LineaAlbaranCliente');
        }
    }

    protected function createViewsAlbaranes(string $viewName = 'ListAlbaranCliente'): void
    {
        $this->createViewSales($viewName, 'AlbaranCliente', 'delivery-notes');

        // añadimos botones
        $this->addButtonGroupDocument($viewName);
        $this->addButtonApproveDocument($viewName);

        $paises = Paises::codeModel();
        $this->addFilterSelect($viewName, 'country', 'country', 'codpais', $paises);
        // filtro de provincias
        $this->addFilterSelectMix($viewName, 'provincia', 'province', 'provincia', 'provincias');
        // filtro de ciudades
        $this->addFilterSelectMix($viewName, 'ciudad', 'city', 'ciudad', 'ciudades');
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
