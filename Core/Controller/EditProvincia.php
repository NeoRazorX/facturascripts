<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2026 Carlos García Gómez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\Provincia;
use FacturaScripts\Dinamic\Model\PuntoInteresCiudad;

/**
 * Controlador para la edición de un registro del modelo de Provincia
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 */
class EditProvincia extends EditBlockController
{
    /** Máximo de marcadores de cada tipo en el mapa. */
    private const MAP_LIMIT = 500;

    public function getModelClassName(): string
    {
        return 'Provincia';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'province';
        $data['icon'] = 'fa-solid fa-map-signs';
        return $data;
    }

    protected function createBlocks(): void
    {
        parent::createBlocks();

        $byProvince = fn(Provincia $province) => [Where::eq('idprovincia', $province->idprovincia)];
        $byCity = fn(Provincia $province) => [
            Where::in('idciudad', 'SELECT idciudad FROM ciudades WHERE idprovincia = '
                . $this->db()->var2str($province->idprovincia)),
        ];

        // el mapa se pide aparte cuando entra en pantalla, con lo que tenga coordenadas
        $this->page()->add(
            MapBlock::make('map', 'location', 'fa-solid fa-map-location-dot')
                ->setLazy()
                ->center(fn(Provincia $province) => [$province->latitude, $province->longitude, 8])
                ->markers(fn(Provincia $province) => MapBlock::fromModels(
                    Ciudad::all(
                        MapBlock::withCoordinates($byProvince($province)),
                        ['ciudad' => 'ASC'],
                        0,
                        self::MAP_LIMIT
                    ),
                    'ciudad',
                    'cities'
                ))
                ->markers(fn(Provincia $province) => MapBlock::fromModels(
                    PuntoInteresCiudad::all(
                        MapBlock::withCoordinates($byCity($province)),
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
                ->tab(ListBlock::make('ListCiudad', 'Ciudad', 'cities', 'fa-solid fa-city')
                    ->addOrderBy(['ciudad'], 'name')
                    ->addSearchFields(['ciudad', 'alias'])
                    ->disableColumn('province')
                    ->where($byProvince))
                ->tab(ListBlock::make('ListCodigoPostal', 'CodigoPostal', 'zip-codes', 'fa-solid fa-map-pin')
                    ->addOrderBy(['number'], 'number')
                    ->addSearchFields(['number'])
                    ->disableColumn('country')
                    ->disableColumn('province')
                    ->where($byProvince))
        );
    }
}
