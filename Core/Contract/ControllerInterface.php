<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Contract;

/**
 * Define el contrato básico de los controladores ejecutados por el kernel.
 */
interface ControllerInterface
{
    /**
     * Inicializa el controlador asociado a una ruta.
     *
     * @param string $className Nombre de la clase final del controlador.
     * @param string $url Ruta solicitada por el usuario.
     */
    public function __construct(string $className, string $url = '');

    /**
     * Devuelve los metadatos de la página, como el nombre, título, icono y ubicación en el menú.
     * Este método se consulta durante la construcción y no debe depender de que el usuario esté autenticado.
     */
    public function getPageData(): array;

    /**
     * Ejecuta el ciclo principal del controlador para procesar la petición actual.
     * El kernel invoca este método una vez después de construir el controlador.
     */
    public function run(): void;
}
