<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024-2026 Carlos García Gómez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\Block\MapBlock;
use FacturaScripts\Core\Template\EditBlockController;
use FacturaScripts\Dinamic\Model\PuntoInteresCiudad;

/**
 * Controlador para editar un único elemento del modelo PuntoInteresCiudad
 */
class EditPuntoInteresCiudad extends EditBlockController
{
    /** Zoom del mapa: suficiente para ver las calles que rodean al punto. */
    private const MAP_ZOOM = 16;

    public function getModelClassName(): string
    {
        return "PuntoInteresCiudad";
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data["menu"] = "admin";
        $data["title"] = "point-of-interest";
        $data["icon"] = "fa-solid fa-location-dot";
        return $data;
    }

    protected function createBlocks(): void
    {
        parent::createBlocks();

        // el mapa se pide aparte cuando entra en pantalla, con el punto como único marcador
        $this->page()->add(
            MapBlock::make('map', 'location', 'fa-solid fa-map-location-dot')
                ->setLazy()
                ->center(fn(PuntoInteresCiudad $point) => [$point->latitude, $point->longitude, self::MAP_ZOOM])
                ->markers(fn(PuntoInteresCiudad $point) => [
                    ['lat' => $point->latitude, 'lng' => $point->longitude, 'title' => $point->name],
                ])
        );
    }
}
