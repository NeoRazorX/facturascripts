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

namespace FacturaScripts\Core\Lib\UI\Event;

use FacturaScripts\Core\Lib\UI\UIForm;
use FacturaScripts\Core\Lib\UI\UIPage;
use FacturaScripts\Core\Request;

/**
 * Contexto que recibe un handler de evento del sistema de componentes.
 *
 * Si el evento pertenece a un form, form() devuelve el formulario ya hidratado
 * con los valores del POST (y validado, si el evento lo requería).
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
final class UIEvent
{
    public function __construct(
        private readonly string $name,
        private readonly ?UIForm $form,
        private readonly UIPage $page,
        private readonly Request $request
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    /** Form del scope del evento, hidratado. Null en eventos de página. */
    public function form(): ?UIForm
    {
        return $this->form;
    }

    public function page(): UIPage
    {
        return $this->page;
    }

    public function request(): Request
    {
        return $this->request;
    }

    /** Atajo de form()->value(): valor actual de un campo del form del evento. */
    public function value(string $field): mixed
    {
        return $this->form?->value($field);
    }

    /** @return array<string, mixed> */
    public function values(): array
    {
        return $this->form?->values() ?? [];
    }
}
