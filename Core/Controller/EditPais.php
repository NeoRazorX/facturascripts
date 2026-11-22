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

use FacturaScripts\Core\Lib\Block\ListBlock;
use FacturaScripts\Core\Lib\Block\MapBlock;
use FacturaScripts\Core\Lib\Block\Tabs;
use FacturaScripts\Core\Template\EditBlockController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Ciudad;
use FacturaScripts\Dinamic\Model\Pais;
use FacturaScripts\Dinamic\Model\PuntoInteresCiudad;

/**
 * Controlador para editar un único elemento del modelo Pais
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class EditPais extends EditBlockController
{
    /** Máximo de marcadores de cada tipo en el mapa. */
    private const MAP_LIMIT = 500;

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

        $cities = fn(Pais $country) => 'SELECT idciudad FROM ciudades WHERE idprovincia IN ('
            . $provinces($country) . ')';

        $this->createHeader();

        // el mapa se pide aparte cuando entra en pantalla, con lo que tenga coordenadas
        $this->page()->add(
            MapBlock::make('map', 'location', 'fa-solid fa-map-location-dot')
                ->setLazy()
                ->center(fn(Pais $country) => [$country->latitude, $country->longitude, 5])
                ->markers(fn(Pais $country) => MapBlock::fromModels(
                    Ciudad::all(
                        MapBlock::withCoordinates([Where::in('idprovincia', $provinces($country))]),
                        ['ciudad' => 'ASC'],
                        0,
                        self::MAP_LIMIT
                    ),
                    'ciudad',
                    'cities'
                ))
                ->markers(fn(Pais $country) => MapBlock::fromModels(
                    PuntoInteresCiudad::all(
                        MapBlock::withCoordinates([Where::in('idciudad', $cities($country))]),
                        ['name' => 'ASC'],
                        0,
                        self::MAP_LIMIT
                    ),
                    'name',
                    'points-of-interest'
                ))
        );

        // un listado por pestaña: solo se cargan los datos de la que se abre
        $this->page()->add(
            Tabs::make('places')
                ->setLazy()
                ->tab(ListBlock::make('ListProvincia', 'Provincia', 'provinces', 'fa-solid fa-map-signs')
                    ->addOrderBy(['provincia'], 'name')
                    ->addSearchFields(['provincia', 'codisoprov', 'alias'])
                    ->disableColumn('country')
                    ->where($byCountry))
                ->tab(ListBlock::make('ListCiudad', 'Ciudad', 'cities', 'fa-solid fa-city')
                    ->addOrderBy(['ciudad'], 'name')
                    ->addSearchFields(['ciudad', 'alias'])
                    ->where(fn(Pais $country) => [Where::in('idprovincia', $provinces($country))]))
        );
    }

    protected function createHeader(): void
    {
        $this->header()
            ->status(fn(Pais $country) => $country->isDefault() ? [Tools::trans('default'), 'warning'] : null);
    }
}
