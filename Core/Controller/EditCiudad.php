<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Ciudad;
use FacturaScripts\Dinamic\Model\PuntoInteresCiudad;

/**
 * Controlador para editar un único elemento del modelo Ciudad
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Frank Aguirre        <faguirre@soenac.com>
 */
class EditCiudad extends EditBlockController
{
    /** Máximo de marcadores en el mapa. */
    private const MAP_LIMIT = 500;

    public function getModelClassName(): string
    {
        return 'Ciudad';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'city';
        $data['icon'] = 'fa-solid fa-city';
        return $data;
    }

    protected function createBlocks(): void
    {
        parent::createBlocks();

        $byCity = fn(Ciudad $city) => [Where::eq('idciudad', $city->idciudad)];
        // también por provincia y país, para que el botón nuevo los lleve ya rellenos
        $zipCodes = function (Ciudad $city) use ($byCity): array {
            $where = $byCity($city);
            $where[] = Where::eq('idprovincia', $city->idprovincia);
            $province = $city->getProvince();
            if ($province) {
                $where[] = Where::eq('codpais', $province->codpais);
            }
            return $where;
        };

        // el mapa se pide aparte cuando entra en pantalla, con lo que tenga coordenadas
        $this->page()->add(
            MapBlock::make('map', 'location', 'fa-solid fa-map-location-dot')
                ->setLazy()
                ->center(fn(Ciudad $city) => [$city->latitude, $city->longitude, 12])
                ->markers(fn(Ciudad $city) => MapBlock::fromModels(
                    PuntoInteresCiudad::all(
                        MapBlock::withCoordinates($byCity($city)),
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
                ->tab(ListBlock::make('ListCodigoPostal', 'CodigoPostal', 'zip-codes', 'fa-solid fa-map-pin')
                    ->addOrderBy(['number'], 'number')
                    ->addSearchFields(['number'])
                    ->disableColumn('country')
                    ->disableColumn('province')
                    ->disableColumn('city')
                    ->where($zipCodes))
                ->tab(ListBlock::make(
                    'ListPuntoInteresCiudad',
                    'PuntoInteresCiudad',
                    'points-of-interest',
                    'fa-solid fa-location-dot'
                )
                    ->addOrderBy(['name'], 'name')
                    ->addSearchFields(['name', 'alias'])
                    ->disableColumn('city')
                    ->where($byCity))
        );
    }
}
