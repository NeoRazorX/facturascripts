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

namespace FacturaScripts\Core\Base;

/**
 * Gestiona la barra de navegación
 * para saltar entre los datos de un modelo
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Pagination
{
    /**
     * Constantes para paginación
     */
    const FS_ITEM_LIMIT = 50;
    const FS_PAGE_MARGIN = 5;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        
    }

    /**
     * Devuelve el offset para el primer elemento del margen especificado
     * para la paginación
     *
     * @param int $offset
     *
     * @return int
     */
    private function getRecordMin($offset)
    {
        $result = $offset - (self::FS_ITEM_LIMIT * self::FS_PAGE_MARGIN);
        if ($result < 0) {
            $result = 0;
        }
        return $result;
    }

    /**
     * Devuelve el offset para el último elemento del margen especificado
     * para la paginación
     *
     * @param int $offset
     * @param int $count
     *
     * @return int
     */
    private function getRecordMax($offset, $count)
    {
        $result = $offset + (self::FS_ITEM_LIMIT * (self::FS_PAGE_MARGIN + 1));
        if ($result > $count) {
            $result = $count;
        }
        return $result;
    }

    /**
     * Devuelve un item de paginación
     * @param string $url
     * @param int $page
     * @param int $offset
     * @param string|bool $icon
     * @param bool $active
     * @return array
     */
    private function addPaginationItem($url, $page, $offset, $icon = false, $active = false)
    {
        /// ¿La url lleva #?
        if (strpos($url, '#') !== false) {
            $auxUrl = explode('#', $url);

            return [
                'url' => $auxUrl[0] . '&offset=' . $offset . '#' . $auxUrl[1],
                'icon' => $icon,
                'page' => $page,
                'active' => $active
            ];
        }

        return [
            'url' => $url . '&offset=' . $offset,
            'icon' => $icon,
            'page' => $page,
            'active' => $active
        ];
    }

    /**
     * Calcula el navegador entre páginas.
     * Permite saltar a:
     *      primera,
     *      mitad anterior,
     *      pageMargin x páginas anteriores
     *      página actual
     *      pageMargin x páginas posteriores
     *      mitad posterior
     *      última
     *
     * @param string $url
     * @param int $count
     * @param int $offset
     *
     * @return array
     *      url    => link a la página
     *      icon   => icono específico de bootstrap en vez de núm. página
     *      page   => número de página
     *      active => Indica si es el indicador activo
     */
    public function getPages($url, $count, $offset = 0)
    {
        $result = [];
        $recordMin = $this->getRecordMin($offset);
        $recordMax = $this->getRecordMax($offset, $count);
        $index = 0;

        // Añadimos la primera página, si no está incluida en el margen de páginas
        if ($offset > (self::FS_ITEM_LIMIT * self::FS_PAGE_MARGIN)) {
            $result[$index] = $this->addPaginationItem($url, 1, 0, 'fa-step-backward');
            $index++;
        }

        // Añadimos la página de en medio entre la primera y la página seleccionada,
        // si la página seleccionada es mayor que el margen de páginas
        $recordMiddleLeft = ($recordMin > self::FS_ITEM_LIMIT) ? ($offset / 2) : $recordMin;
        if ($recordMiddleLeft < $recordMin) {
            $page = floor($recordMiddleLeft / self::FS_ITEM_LIMIT);
            $result[$index] = $this->addPaginationItem($url, ($page + 1), ($page * self::FS_ITEM_LIMIT), 'fa-backward');
            $index++;
        }

        // Añadimos la página seleccionada y el margen de páginas a su izquierda y su derecha
        for ($record = $recordMin; $record < $recordMax; $record += self::FS_ITEM_LIMIT) {
            if (($record >= $recordMin && $record <= $offset) || ($record <= $recordMax && $record >= $offset)) {
                $page = ($record / self::FS_ITEM_LIMIT) + 1;
                $result[$index] = $this->addPaginationItem($url, $page, $record, false, ($record == $offset));
                $index++;
            }
        }

        // Añadimos la página de en medio entre la página seleccionada y la última,
        // si la página seleccionada es más pequeña que el márgen entre páginas
        $recordMiddleRight = $offset + (($count - $offset) / 2);
        if ($recordMiddleRight > $recordMax) {
            $page = floor($recordMiddleRight / self::FS_ITEM_LIMIT);
            $result[$index] = $this->addPaginationItem($url, ($page + 1), ($page * self::FS_ITEM_LIMIT), 'fa-forward');
            $index++;
        }

        // Añadimos la última página, si no está incluida en el margen de páginas
        if ($recordMax < $count) {
            $pageMax = floor($count / self::FS_ITEM_LIMIT);
            $result[$index] = $this->addPaginationItem($url, ($pageMax + 1), ($pageMax * self::FS_ITEM_LIMIT), 'fa-step-forward');
        }

        /// si solamente hay una página, no merece la pena mostrar un único botón
        return (count($result) > 1) ? $result : [];
    }
}
