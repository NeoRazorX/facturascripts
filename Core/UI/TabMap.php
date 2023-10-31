<?php

namespace FacturaScripts\Core\UI;

use FacturaScripts\Core\Lib\AssetManager;
use FacturaScripts\Core\Template\SectionTab;

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
                'latitude' => rand(-90, 90),
                'longitude' => rand(-90, 90),
            ];

            $this->counter++;
        }
    }

    public function render(): string
    {
        return '<script>'
            . 'Mapkick.options = {style: "https://demotiles.maplibre.org/style.json"}'
            . '</script>'
            . '<div id="map" style="height: 600px;"></div>'
            . '<script>'
            . 'new Mapkick.Map("map", ' . json_encode($this->cursor) . ', {zoom: 1, controls: true})'
            . '</script>';
    }
}