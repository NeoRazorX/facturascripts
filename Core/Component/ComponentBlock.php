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

use FacturaScripts\Core\Request;

/**
 * Contenedor con nombre que agrupa componentes en una pestaña dentro de un PanelController.
 *
 * Añade un bloque a cualquier controlador que use el trait HasComponentBlocks y luego
 * adjunta componentes a él. El bloque se encarga de poblar los valores de los componentes
 * desde un modelo en peticiones GET, y de procesar la petición en lote (con propagación
 * de errores en línea) en peticiones POST.
 *
 * El array público $settings expone 'active' (si la pestaña es visible) y 'card'
 * (si el contenido se envuelve en una card de Bootstrap) para la capa de plantillas Twig.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class ComponentBlock
{
    /** @var BaseComponent[] */
    private array $components = [];

    /** @var array<string, string[]> */
    private array $errors = [];

    private string $icon;
    private string $name;

    /** @var array{active: bool, card: bool} */
    public array $settings = ['active' => true, 'card' => true];

    private string $title;

    public function __construct(string $name, string $title, string $icon = 'fa-solid fa-puzzle-piece')
    {
        $this->name = $name;
        $this->title = $title;
        $this->icon = $icon;
    }

    public static function make(string $name, string $title, string $icon = 'fa-solid fa-puzzle-piece'): static
    {
        return new static($name, $title, $icon);
    }

    public function addComponent(BaseComponent $component): BaseComponent
    {
        $fieldname = $component->fieldname();

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $fieldname)) {
            throw new \InvalidArgumentException(
                "Invalid component fieldname '{$fieldname}': must start with a letter or underscore and contain only alphanumeric characters and underscores."
            );
        }

        if (isset($this->components[$fieldname])) {
            throw new \LogicException(
                "Duplicate component fieldname '{$fieldname}': a component with this name is already registered."
            );
        }

        $this->components[$fieldname] = $component;
        return $component;
    }

    public function component(string $fieldname): ?BaseComponent
    {
        return $this->components[$fieldname] ?? null;
    }

    public function components(): array
    {
        return $this->components;
    }

    public function removeComponent(string $fieldname): void
    {
        unset($this->components[$fieldname]);
    }

    public function populate(?object $model): void
    {
        if ($model === null) {
            return;
        }

        foreach ($this->components as $fieldname => $component) {
            if (property_exists($model, $fieldname)) {
                $component->setValue($model->{$fieldname});
            }
        }
    }

    public function process(Request $request, ?object $model = null): bool
    {
        $this->errors = [];

        foreach ($this->components as $fieldname => $component) {
            $result = $component->processRequest($request, $model);
            if (!$result['success']) {
                $this->errors[$fieldname] = $result['errors'];
                $component->setValidationErrors($result['errors']);
            }
        }

        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function errorsFor(string $fieldname): array
    {
        return $this->errors[$fieldname] ?? [];
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function icon(): string
    {
        return $this->icon;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function title(): string
    {
        return $this->title;
    }
}
