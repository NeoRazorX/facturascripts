<?php

namespace FacturaScripts\Core\UI;

use FacturaScripts\Core\Template\SectionTab;
use FacturaScripts\Dinamic\Lib\AssetManager;

class TabMap extends SectionTab
{
    public $cursor = [];

    public function __construct()
    {
        $this->icon = 'fa-solid fa-earth-americas';

        AssetManager::add('css', 'https://unpkg.com/maplibre-gl@2.4.0/dist/maplibre-gl.css');
        AssetManager::add('js', 'https://unpkg.com/maplibre-gl@2.4.0/dist/maplibre-gl.js');
        AssetManager::add('js', 'https://unpkg.com/mapkick/dist/mapkick.js');

        // creamos algunos datos de ejemplo
        foreach (range(1, rand(3, 50)) as $i) {
            $this->cursor[] = [
                'tooltip' => 'Marker ' . $i,
                'latitude' => rand(-8000000, 8000000) / 100000,
                'longitude' => rand(-9000000, 9000000) / 100000,
            ];

            $this->counter++;
        }
    }

    public function jsInitFunction(): string
    {
        return 'let map_' . $this->name . ' = null;';
    }

    public function jsRedrawFunction(): string
    {
        return 'map_' . $this->name . '.map.resize();';
    }

    public function render(): string
    {
        $name = 'map_' . $this->name;

        return '<div id="' . $name . '" style="height: 600px;"></div>' . "\n"
            . "<script>\n"
            . 'Mapkick.options = {style: "https://demotiles.maplibre.org/style.json"};' . "\n"
            . $name . ' = new Mapkick.Map("' . $name . '", ' . json_encode($this->cursor) . ', {zoom: 2, controls: true});' . "\n"
            . " </script > \n";
    }
}