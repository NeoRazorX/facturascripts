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

namespace FacturaScripts\Core\Lib\UI\Validation;

/**
 * Acumulador de errores de validación de un formulario, indexados por nombre
 * lógico de campo. Lo produce UIForm::validate() y lo consumen tanto el
 * re-render (errores en línea por campo) como el envelope JSON (mapa errors).
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
final class ErrorBag
{
    /** @var array<string, string[]> nombre de campo → mensajes */
    private array $errors = [];

    public function add(string $field, string $message): self
    {
        $this->errors[$field][] = $message;
        return $this;
    }

    public function addMany(string $field, array $messages): self
    {
        foreach ($messages as $message) {
            $this->add($field, $message);
        }
        return $this;
    }

    /** @return string[] */
    public function get(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /** @return array<string, string[]> */
    public function all(): array
    {
        return $this->errors;
    }

    public function isEmpty(): bool
    {
        return empty($this->errors);
    }

    public function merge(ErrorBag $other): self
    {
        foreach ($other->all() as $field => $messages) {
            $this->addMany($field, $messages);
        }
        return $this;
    }
}
