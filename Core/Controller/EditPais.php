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

use FacturaScripts\Core\Lib\Block\Columns;
use FacturaScripts\Core\Lib\Block\ListBlock;
use FacturaScripts\Core\Lib\Block\Tabs;
use FacturaScripts\Core\Template\EditBlockController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Pais;

/**
 * Controlador para editar un único elemento del modelo Pais
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class EditPais extends EditBlockController
{
    public function getModelClassName(): string
    {
        return 'Pais';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'country';
        $data['icon'] = 'fa-solid fa-globe-americas';
        return $data;
    }

    protected function createBlocks(): void
    {
        parent::createBlocks();

        $byCountry = fn(Pais $country) => [Where::eq('codpais', $country->codpais)];
        $provinces = fn(Pais $country) => 'SELECT idprovincia FROM provincias WHERE codpais = '
            . $this->db()->var2str($country->codpais);

        $this->createHeader();

        // provincias y códigos postales, uno al lado del otro
        $this->page()->add(
            Columns::make('geography')
                ->col(7, ListBlock::make('ListProvincia', 'Provincia', 'provinces', 'fa-solid fa-map-signs')
                    ->addOrderBy(['provincia'], 'name')
                    ->addSearchFields(['provincia', 'codisoprov', 'alias'])
                    ->disableColumn('country')
                    ->where($byCountry))
                ->col(5, ListBlock::make('ListCodigoPostal', 'CodigoPostal', 'zip-codes', 'fa-solid fa-map-pin')
                    ->addOrderBy(['number'], 'number')
                    ->addSearchFields(['number'])
                    ->disableColumn('country')
                    ->where($byCountry))
        );

        // ciudades y puntos de interés en pestañas: solo se cargan los datos de la que se abre
        $this->page()->add(
            Tabs::make('places')
                ->setLazy()
                ->tab(ListBlock::make('ListCiudad', 'Ciudad', 'cities', 'fa-solid fa-city')
                    ->addOrderBy(['ciudad'], 'name')
                    ->addSearchFields(['ciudad', 'alias'])
                    ->where(fn(Pais $country) => [Where::in('idprovincia', $provinces($country))]))
                ->tab(ListBlock::make('ListPuntoInteresCiudad', 'PuntoInteresCiudad', 'points-of-interest', 'fa-solid fa-location-dot')
                    ->addOrderBy(['name'], 'name')
                    ->addSearchFields(['name', 'alias'])
                    ->where(fn(Pais $country) => [
                        Where::in('idciudad', 'SELECT idciudad FROM ciudades WHERE idprovincia IN (' . $provinces($country) . ')')
                    ]))
        );
    }

    protected function createHeader(): void
    {
        $this->header()
            ->status(fn(Pais $country) => $country->isDefault() ? [Tools::trans('default'), 'success'] : null)
            ->stat('provinces', ['block' => 'ListProvincia'])
            ->stat('zip-codes', ['block' => 'ListCodigoPostal'])
            ->stat('cities', ['block' => 'ListCiudad'])
            ->stat('points-of-interest', ['block' => 'ListPuntoInteresCiudad'])
            ->dropdown('location', ['label' => 'location', 'icon' => 'fa-solid fa-map-location-dot'])
            ->button('open-map', [
                'dropdown' => 'location',
                'type' => 'link',
                'label' => 'OpenStreetMap',
                'icon' => 'fa-solid fa-map',
                'target' => '_blank',
                'url' => fn(Pais $country) => 'https://www.openstreetmap.org/?mlat=' . $country->latitude
                    . '&mlon=' . $country->longitude . '#map=5/' . $country->latitude . '/' . $country->longitude,
                // sin coordenadas no hay nada que mostrar en el mapa
                'visible' => fn(Pais $country) => false === empty($country->latitude)
                    && false === empty($country->longitude),
            ]);
    }
}
