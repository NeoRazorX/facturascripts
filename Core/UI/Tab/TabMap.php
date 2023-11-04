<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\UI\Tab;

use FacturaScripts\Core\Template\UI\SectionTab;
use FacturaScripts\Dinamic\Lib\AssetManager;
use Symfony\Component\HttpFoundation\Request;

class TabMap extends SectionTab
{
    /** @var array */
    public $data = [];

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->icon = 'fa-solid fa-earth-americas';

        AssetManager::add('css', 'https://unpkg.com/maplibre-gl@2.4.0/dist/maplibre-gl.css');
        AssetManager::add('js', 'https://unpkg.com/maplibre-gl@2.4.0/dist/maplibre-gl.js');
        AssetManager::add('js', 'https://unpkg.com/mapkick/dist/mapkick.js');

        // creamos algunos datos de ejemplo
        foreach (range(1, rand(3, 50)) as $i) {
            $this->data[] = [
                'tooltip' => 'Marker ' . $i,
                'latitude' => rand(-8000000, 8000000) / 100000,
                'longitude' => rand(-9000000, 9000000) / 100000,
            ];

            $this->counter++;
        }
    }

    public function jsInitFunction(): string
    {
        return 'let map_' . $this->id() . ' = null;';
    }

    public function jsRedrawFunction(): string
    {
        return 'map_' . $this->id() . '.map.resize();';
    }

    public function load(Request $request): bool
    {
        return true;
    }

    public function render(string $context = ''): string
    {
        $name = 'map_' . $this->id();

        return '<div id="' . $name . '" style="height: 600px;"></div>' . "\n"
            . "<script>\n"
            . 'Mapkick.options = {style: "https://demotiles.maplibre.org/style.json"};' . "\n"
            . $name . ' = new Mapkick.Map("' . $name . '", ' . json_encode($this->data) . ', {zoom: 2, controls: true});' . "\n"
            . " </script > \n";
    }
}