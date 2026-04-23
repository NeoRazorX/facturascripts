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
 * Interfaz que deben implementar los controladores de error de FacturaScripts.
 * Permite a un plugin sustituir la página de error por defecto del core.
 */
interface ErrorControllerInterface
{
    /**
     * Recibe la excepción que originó el error y la URL que se intentaba cargar.
     *
     * @param Exception $exception Excepción capturada.
     * @param string    $url       URL solicitada en el momento del error.
     */
    public function __construct(Exception $exception, string $url = '');

    /**
     * Genera y envía la respuesta HTTP de error al usuario.
     * Debe renderizar la página de error con la información apropiada.
     */
    public function run(): void;
}
