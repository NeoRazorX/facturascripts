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

use Exception;

/**
 * Define el contrato de los controladores que gestionan las excepciones capturadas por el kernel.
 */
interface ErrorControllerInterface
{
    /**
     * Inicializa el controlador con la excepción que debe gestionar.
     *
     * @param Exception $exception Excepción que ha interrumpido la ejecución de la petición.
     * @param string $url Ruta asociada a la petición que produjo el error.
     */
    public function __construct(Exception $exception, string $url = '');

    /**
     * Genera y envía la respuesta correspondiente al error, ya sea HTML, JSON o una redirección.
     * El kernel invoca este método después de seleccionar el controlador adecuado para la excepción.
     */
    public function run(): void;
}
