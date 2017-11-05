<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Base\ExtendedController;

/**
 * Interfaz para elementos visuales
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
interface VisualItemInterface
{

    /**
     * Carga la estructura de atributos en base a un archivo XML
     *
     * @param \SimpleXMLElement $items
     */
    public function loadFromXML($items);

    /**
     * Carga la estructura de atributos en base un archivo JSON
     *
     * @param array $items
     */
    public function loadFromJSON($items);

    /**
     * Genera el código html para visualizar la cabecera del elemento visual
     *
     * @param string $value
     */
    public function getHeaderHTML($value);
}
