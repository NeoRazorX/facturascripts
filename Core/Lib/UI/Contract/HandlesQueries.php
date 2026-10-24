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

namespace FacturaScripts\Core\Lib\UI\Contract;

use FacturaScripts\Core\Request;

/**
 * Contrato para componentes que responden consultas de datos propias
 * (?_ui_query=accion&_ui_target=path): select2 remoto, autocomplete, pickers…
 *
 * La petición llega por la URL del propio controlador, por lo que está
 * protegida por la sesión y los permisos de la página.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
interface HandlesQueries
{
    /**
     * Responde una consulta de datos del componente.
     *
     * @param string $action nombre de la acción ('search' por defecto)
     * @return array respuesta serializable a JSON (p.ej. formato select2 {results: [{id, text}]})
     */
    public function handleQuery(string $action, Request $request): array;
}
