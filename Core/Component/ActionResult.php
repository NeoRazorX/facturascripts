<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Component;

/**
 * Valor de retorno para los manejadores de eventos de componentes (p. ej. 'save' o 'delete').
 *
 * Cuando exit es true el controlador detiene el renderizado y, o bien redirige (si redirect
 * está definido), o bien suprime la plantilla por completo. withRedirect() establece ambos
 * campos en una sola llamada. Los manejadores que solo necesitan registrar un aviso y
 * permanecer en la misma página deben devolver ActionResult::make() sin ningún encadenamiento.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class ActionResult
{
    public bool $exit = false;
    public bool $stop = false;
    public string $redirect = '';
    public string $message = '';

    public static function make(): static
    {
        return new static();
    }

    public function exit(): static
    {
        $this->exit = true;
        return $this;
    }

    public function stop(): static
    {
        $this->stop = true;
        return $this;
    }

    public function withRedirect(string $url): static
    {
        $this->redirect = $url;
        $this->exit = true;
        return $this;
    }

    public function withMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }
}
