<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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


namespace FacturaScripts\Core\Lib\Block;

use Closure;
use FacturaScripts\Core\Template\BlockController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;

/**
 * Bloque con un mapa. Los marcadores los calculan funciones que reciben el registro
 * principal de la página, así que solo se consultan cuando el bloque se carga.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class MapBlock extends Block
{
    public const TILES_ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>';
    public const TILES_URL = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

    /** @var Closure|null */
    private $center;

    /** @var array|null */
    private $centerPoint;

    /** @var int */
    private $height = 380;

    /** @var array */
    private $points = [];

    /** @var Closure[] */
    private $sources = [];

    public function assets(): array
    {
        return [
            'css' => ['node_modules/leaflet/dist/leaflet.css'],
            'js' => ['node_modules/leaflet/dist/leaflet.js', 'Dinamic/Assets/JS/BlockMap.js'],
        ];
    }

    /**
     * Punto en el que se centra el mapa cuando no hay marcadores. La función recibe el
     * registro principal y devuelve [latitud, longitud] o [latitud, longitud, zoom].
     *
     * @return static
     */
    public function center(Closure $center)
    {
        $this->center = $center;

        return $this;
    }

    /**
     * Datos que necesita el script del mapa.
     */
    public function config(): array
    {
        // cada instalación puede usar su propio servidor de teselas
        $url = (string)Tools::settings('default', 'maptilesurl', '');
        $custom = '' !== $url;

        return [
            'attribution' => $custom ?
                (string)Tools::settings('default', 'maptilesattribution', '') :
                self::TILES_ATTRIBUTION,
            'center' => $this->centerPoint,
            'markers' => $this->points,
            'tiles' => $custom ? $url : self::TILES_URL,
        ];
    }

    public function count(): int
    {
        return count($this->points);
    }

    /**
     * Convierte registros en marcadores: usa sus campos latitude y longitude, su url y,
     * como título, el campo indicado. El grupo es la clave de traducción de su tipo.
     */
    public static function fromModels(array $models, string $titleField, string $group): array
    {
        $markers = [];
        foreach ($models as $model) {
            $markers[] = [
                'lat' => $model->latitude,
                'lng' => $model->longitude,
                'title' => $model->{$titleField},
                'url' => $model->url(),
                'group' => Tools::trans($group),
            ];
        }

        return $markers;
    }

    public function height(): int
    {
        return $this->height;
    }

    public function load(BlockController $controller, bool $target): void
    {
        $record = $controller->record();

        $this->points = [];
        foreach ($this->sources as $source) {
            foreach ($source($record) as $marker) {
                $point = $this->point($marker);
                if (null !== $point) {
                    $this->points[] = $point;
                }
            }
        }

        $center = null === $this->center ? null : ($this->center)($record);
        $this->centerPoint = null;
        if (is_array($center) && self::validCoordinates($center[0] ?? null, $center[1] ?? null)) {
            $this->centerPoint = [(float)$center[0], (float)$center[1], (int)($center[2] ?? 0)];
        }
    }

    /**
     * @param string $name
     * @param string $title clave de traducción del título
     * @param string $icon
     *
     * @return static
     */
    public static function make(string $name, string $title = '', string $icon = '')
    {
        $block = new static($name);

        return $block->setTitle($title)->setIcon($icon);
    }

    /**
     * Añade marcadores. La función recibe el registro principal y devuelve una lista de
     * marcadores: ['lat' => , 'lng' => , 'title' => , 'url' => , 'group' => , 'color' => ].
     * Solo lat y lng son obligatorios; los marcadores sin coordenadas válidas se descartan.
     * Se puede llamar varias veces, también desde las extensiones de plugins.
     *
     * @return static
     */
    public function markers(Closure $source)
    {
        $this->sources[] = $source;

        return $this;
    }

    /**
     * @return static
     */
    public function setHeight(int $height)
    {
        $this->height = max(120, $height);

        return $this;
    }

    public function template(): string
    {
        return 'Block/MapBlock.html.twig';
    }

    public static function validCoordinates($latitude, $longitude): bool
    {
        return is_numeric($latitude) && is_numeric($longitude)
            && abs((float)$latitude) <= 90 && abs((float)$longitude) <= 180;
    }

    /**
     * Añade a un filtro la condición de tener coordenadas.
     */
    public static function withCoordinates(array $where): array
    {
        $where[] = Where::isNotNull('latitude');
        $where[] = Where::isNotNull('longitude');

        return $where;
    }

    private function point($marker): ?array
    {
        if (false === is_array($marker) || false === self::validCoordinates($marker['lat'] ?? null, $marker['lng'] ?? null)) {
            return null;
        }

        return [
            'lat' => (float)$marker['lat'],
            'lng' => (float)$marker['lng'],
            'title' => (string)($marker['title'] ?? ''),
            'url' => (string)($marker['url'] ?? ''),
            'group' => (string)($marker['group'] ?? ''),
            'color' => (string)($marker['color'] ?? ''),
        ];
    }
}
