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
 * Interfaz que deben implementar todos los controladores de FacturaScripts.
 * La clase base abstracta Core/Base/Controller.php ya la implementa; los
 * controladores concretos (ListController, EditController, PanelController,
 * ApiController...) extienden esa clase base.
 */
interface ControllerInterface
{
    /**
     * Inicializa el controlador.
     *
     * @param string $className Nombre de la clase del controlador.
     * @param string $url       URL canónica del controlador (p. ej., 'ListFacturaCliente').
     */
    public function __construct(string $className, string $url = '');

    /**
     * Devuelve los metadatos de la página usados para construir el menú y
     * gestionar los permisos. Claves reconocidas:
     *
     *   - 'title'      (string)  Clave de traducción del título.
     *   - 'icon'       (string)  Clase FontAwesome (p. ej., 'fa-solid fa-file').
     *   - 'menu'       (string)  Sección del menú (p. ej., 'sales', 'purchases').
     *   - 'submenu'    (string)  Submenú dentro de la sección (opcional).
     *   - 'showonmenu' (bool)    False para ocultar la página del menú.
     *   - 'ordernum'   (int)     Posición relativa dentro del menú.
     *
     * @return array<string, mixed>
     */
    public function getPageData(): array;

    /**
     * Procesa la petición HTTP: valida permisos, ejecuta la acción recibida,
     * carga los datos necesarios y genera la respuesta (HTML, JSON, CSV...).
     */
    public function run(): void;
}
