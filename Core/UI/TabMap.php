<?php

namespace FacturaScripts\Core\UI;

use FacturaScripts\Core\Lib\AssetManager;
use FacturaScripts\Core\Template\SectionTab;

class TabMap extends SectionTab
{
    public function __construct()
    {
        AssetManager::add('css', 'https://unpkg.com/maplibre-gl@2.4.0/dist/maplibre-gl.css');
        AssetManager::add('js', 'https://unpkg.com/maplibre-gl@2.4.0/dist/maplibre-gl.js');
        AssetManager::add('js', 'https://unpkg.com/mapkick/dist/mapkick.js');
    }

    public function render(): string
    {
        return '<script>'
            . 'Mapkick.options = {style: "https://demotiles.maplibre.org/style.json"}'
            . '</script>'
            . '<div id="map" style="height: 600px;"></div>'
            . '<script>'
            . 'new Mapkick.Map("map", [{latitude: 37.7829, longitude: -122.4190}])'
            . '</script>';
    }
}